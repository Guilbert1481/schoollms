<?php

namespace App\Http\Controllers\Staff\ProgramHead;

use App\Http\Controllers\Controller;
use App\Models\Competency;
use App\Models\Lesson;
use App\Models\LessonResource;
use App\Models\Program;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Program Head's Lesson Studio — folder-style browser for the curriculum tree:
 *
 *   Level 0 — Subjects (only those mapped to programs assigned to this PH)
 *   Level 1 — Topics inside a Subject
 *   Level 2 — Lessons inside a Topic
 *   Level 3 — Competencies inside a Lesson
 *
 * Unlike Course Architect (read-only browser, manages resources only), the
 * Program Head can create/delete at every level and reorder Topics/Lessons.
 */
class LessonStudioController extends Controller
{
    public const CATEGORIES = ['gen_ed', 'prof_ed', 'major', 'pe', 'nstp', 'internship'];

    /** Subject IDs mapped to programs the current Program Head owns. */
    private function scopedSubjectIds(): \Illuminate\Support\Collection
    {
        $programIds = Program::where('program_head_id', auth()->id())->pluck('id');

        return DB::table('program_subjects')
            ->whereIn('program_id', $programIds)
            ->distinct()
            ->pluck('subject_id');
    }

    public function index(Request $request, $subject = null, $topic = null, $lesson = null)
    {
        $schoolId   = $request->user()->school_id;
        $subjectIds = $this->scopedSubjectIds();

        $subjectModel = $subject
            ? Subject::whereIn('id', $subjectIds)->where('id', $subject)->firstOrFail()
            : null;
        $topicModel = $topic
            ? Topic::where('id', $topic)->where('subject_id', $subject)->firstOrFail()
            : null;
        $lessonModel = $lesson
            ? Lesson::where('id', $lesson)->where('topic_id', $topic)->firstOrFail()
            : null;

        $level = $lessonModel ? 3 : ($topicModel ? 2 : ($subjectModel ? 1 : 0));

        $breadcrumbs = [['label' => 'Subjects', 'url' => route('program_head.subjects.index')]];
        if ($subjectModel) {
            $breadcrumbs[] = [
                'label' => $subjectModel->name,
                'url'   => route('program_head.lesson-studio.subject', $subjectModel->id),
            ];
        }
        if ($topicModel) {
            $breadcrumbs[] = [
                'label' => $topicModel->name,
                'url'   => route('program_head.lesson-studio.topic', [$subjectModel->id, $topicModel->id]),
            ];
        }
        if ($lessonModel) {
            $breadcrumbs[] = [
                'label' => $lessonModel->name,
                'url'   => route('program_head.lesson-studio.lesson', [$subjectModel->id, $topicModel->id, $lessonModel->id]),
            ];
        }

        $folders      = [];
        $listColumns  = [];
        $listData     = collect();
        $listTableKey = null;
        $childLabel   = $level === 0 ? 'topic' : ($level === 1 ? 'lesson' : 'competency');

        // Status filter (only meaningful at L0)
        $status = $request->query('status', 'all');
        if (! in_array($status, ['all', 'active', 'inactive'], true)) {
            $status = 'all';
        }

        if ($level === 0) {
            $programIds = Program::where('program_head_id', auth()->id())->pluck('id');

            // Per-subject active flag derived from program_subjects rows for this PH's programs.
            // A subject is "Active" only if at least one of its mappings is_active = 1.
            $activityMap = DB::table('program_subjects')
                ->whereIn('program_id', $programIds)
                ->whereIn('subject_id', $subjectIds)
                ->select('subject_id', DB::raw('MAX(is_active) AS any_active'))
                ->groupBy('subject_id')
                ->pluck('any_active', 'subject_id');

            $rows = Subject::whereIn('id', $subjectIds)
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'description', 'category']);

            // Filter by computed mapping status before mapping into folder/list shape.
            if ($status === 'active') {
                $rows = $rows->filter(fn ($s) => (int) ($activityMap[$s->id] ?? 0) === 1)->values();
            } elseif ($status === 'inactive') {
                $rows = $rows->filter(fn ($s) => (int) ($activityMap[$s->id] ?? 0) === 0)->values();
            }

            $folders = $rows->map(fn ($s) => [
                'id'          => $s->id,
                'name'        => $s->name,
                'subtitle'    => $s->code,
                'count'       => DB::table('topics')->where('subject_id', $s->id)->count(),
                'url'         => route('program_head.lesson-studio.subject', $s->id),
                'is_active'   => (bool) ($activityMap[$s->id] ?? 0),
                'code'        => $s->code,
                'description' => $s->description,
                'category'    => $s->category,
            ])->values()->all();

            $listTableKey = 'ph_lesson_studio_subjects';
            $listColumns  = [
                ['key' => 'name',         'label' => 'Subject Name', 'raw' => true],
                ['key' => 'code',         'label' => 'Code'],
                ['key' => 'topics_count', 'label' => 'Topics'],
                ['key' => 'status',       'label' => 'Status', 'raw' => true],
            ];
            $listData = collect($folders)->map(fn ($f) => (object) [
                'id'           => $f['id'],
                'name'         => $this->folderLink($f['url'], $f['name']),
                'code'         => $f['subtitle'] ?? '—',
                'topics_count' => $f['count'],
                'status'       => $this->statusBadge((bool) $f['is_active']),
                '_url'         => $f['url'],
            ]);
        } elseif ($level === 1) {
            $folders = DB::table('topics')
                ->where('subject_id', $subjectModel->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'name', 'description'])
                ->map(fn ($t) => [
                    'id'          => $t->id,
                    'name'        => $t->name,
                    'subtitle'    => null,
                    'count'       => DB::table('lessons')->where('topic_id', $t->id)->count(),
                    'url'         => route('program_head.lesson-studio.topic', [$subjectModel->id, $t->id]),
                    'description' => $t->description ?? null,
                ])->values()->all();

            $listTableKey = 'ph_lesson_studio_topics';
            $listColumns  = [
                ['key' => 'name',          'label' => 'Topic Name', 'raw' => true],
                ['key' => 'lessons_count', 'label' => 'Lessons'],
            ];
            $listData = collect($folders)->map(fn ($f) => (object) [
                'id'            => $f['id'],
                'name'          => $this->folderLink($f['url'], $f['name']),
                'lessons_count' => $f['count'],
                '_url'          => $f['url'],
            ]);
        } elseif ($level === 2) {
            $folders = DB::table('lessons')
                ->where('topic_id', $topicModel->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'name', 'description'])
                ->map(fn ($l) => [
                    'id'          => $l->id,
                    'name'        => $l->name,
                    'subtitle'    => null,
                    'count'       => DB::table('competencies')->where('lesson_id', $l->id)->count(),
                    'url'         => route('program_head.lesson-studio.lesson', [$subjectModel->id, $topicModel->id, $l->id]),
                    'description' => $l->description ?? null,
                ])->values()->all();

            $listTableKey = 'ph_lesson_studio_lessons';
            $listColumns  = [
                ['key' => 'name',               'label' => 'Lesson Name', 'raw' => true],
                ['key' => 'competencies_count', 'label' => 'Competencies'],
            ];
            $listData = collect($folders)->map(fn ($f) => (object) [
                'id'                 => $f['id'],
                'name'                => $this->folderLink($f['url'], $f['name']),
                'competencies_count' => $f['count'],
                '_url'               => $f['url'],
            ]);
        } else {
            // Level 3 — Competencies. Reorderable via the `sequence` column.
            $folders = DB::table('competencies')
                ->where('lesson_id', $lessonModel->id)
                ->orderByRaw('CASE WHEN sequence IS NULL THEN 1 ELSE 0 END, sequence')
                ->orderBy('id')
                ->get(['id', 'name', 'description'])
                ->map(fn ($c) => [
                    'id'          => $c->id,
                    'name'        => $c->name,
                    'subtitle'    => null,
                    'count'       => 0,
                    'url'         => '#',
                    'description' => $c->description ?? null,
                ])->values()->all();

            $listTableKey = 'ph_lesson_studio_competencies';
            $listColumns  = [
                ['key' => 'name', 'label' => 'Competency Name'],
            ];
            $listData = collect($folders)->map(fn ($f) => (object) [
                'id'   => $f['id'],
                'name' => $f['name'],
                '_url' => null,
            ]);
        }

        return view('program_head.lesson-studio.index', [
            'level'        => $level,
            'breadcrumbs'  => $breadcrumbs,
            'folders'      => $folders,
            'childLabel'   => $childLabel,
            'listColumns'  => $listColumns,
            'listData'     => $listData,
            'listTableKey' => $listTableKey,
            'subjectModel' => $subjectModel,
            'topicModel'   => $topicModel,
            'lessonModel'  => $lessonModel,
            'categories'   => self::CATEGORIES,
            'status'       => $status,
        ]);
    }

    /** Renders a small colored pill for active/inactive status. */
    private function statusBadge(bool $active): string
    {
        if ($active) {
            return '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">Active</span>';
        }
        return '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 ring-1 ring-rose-200">Inactive</span>';
    }

    /** Produces the clickable folder-icon link used by the shareable list view. */
    private function folderLink(string $url, string $name): string
    {
        return '<a href="' . e($url) . '" class="font-medium text-indigo-700 hover:underline inline-flex items-center gap-1.5">'
            . '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>'
            . e($name) . '</a>';
    }

    /**
     * Create a folder at the appropriate level:
     *   No parent       → Subject
     *   subject_id only → Topic
     *   topic_id        → Lesson
     *   lesson_id       → Competency
     */
    public function createFolder(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'nullable|string|max:32',
            'description' => 'nullable|string|max:1000',
            'category'    => 'nullable|in:' . implode(',', self::CATEGORIES),
            'subject_id'  => 'nullable|integer|exists:subjects,id',
            'topic_id'    => 'nullable|integer|exists:topics,id',
            'lesson_id'   => 'nullable|integer|exists:lessons,id',
        ]);

        $user     = $request->user();
        $schoolId = $user->school_id;

        if (! empty($data['lesson_id'])) {
            Competency::create([
                'school_id'  => $schoolId,
                'subject_id' => $data['subject_id'],
                'topic_id'   => $data['topic_id'],
                'lesson_id'  => $data['lesson_id'],
                'name'       => $data['name'],
            ]);
            $back = route('program_head.lesson-studio.lesson', [
                $data['subject_id'], $data['topic_id'], $data['lesson_id'],
            ]);
            return redirect($back)->with('success', 'Competency added.');
        }

        if (! empty($data['topic_id'])) {
            Lesson::create([
                'school_id'  => $schoolId,
                'subject_id' => $data['subject_id'],
                'topic_id'   => $data['topic_id'],
                'name'       => $data['name'],
            ]);
            $back = route('program_head.lesson-studio.topic', [$data['subject_id'], $data['topic_id']]);
            return redirect($back)->with('success', 'Lesson added.');
        }

        if (! empty($data['subject_id'])) {
            Topic::create([
                'school_id'  => $schoolId,
                'subject_id' => $data['subject_id'],
                'name'       => $data['name'],
            ]);
            return redirect(route('program_head.lesson-studio.subject', $data['subject_id']))
                ->with('success', 'Topic added.');
        }

        // Top level — Subject
        if (empty($data['code'])) {
            return back()->withErrors(['code' => 'Subject code is required.'])->withInput();
        }

        $subject = Subject::create([
            'school_id'   => $schoolId,
            'name'        => $data['name'],
            'code'        => $data['code'],
            'description' => $data['description'] ?? null,
            'category'    => $data['category'] ?? 'major',
            'is_active'   => 1,
            'scope'       => 'academic',
            'created_by'  => $user->id,
        ]);

        // Auto-map the new subject to all programs this Program Head owns,
        // otherwise it would not appear in the scoped list.
        $programIds = Program::where('program_head_id', $user->id)->pluck('id');
        foreach ($programIds as $pid) {
            DB::table('program_subjects')->insertOrIgnore([
                'program_id' => $pid,
                'subject_id' => $subject->id,
                'is_active'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect(route('program_head.subjects.index'))->with('success', 'Subject added.');
    }

    /**
     * Delete a folder of any type and cascade through its children.
     * Removes any uploaded resource files belonging to lessons that disappear.
     */
    public function destroyFolder(Request $request, string $type, int $id)
    {
        $schoolId = $request->user()->school_id;
        $back     = route('program_head.subjects.index');
        $msg      = 'Removed.';

        if ($type === 'subject') {
            $subject = Subject::where('id', $id)->where('school_id', $schoolId)->firstOrFail();

            $lessonIds = Lesson::where('subject_id', $subject->id)->pluck('id')->all();
            $this->purgeResourceFiles($lessonIds);

            // Detach pivot mappings, then delete subject (FK cascade handles topics/lessons).
            DB::table('program_subjects')->where('subject_id', $subject->id)->delete();
            $subject->delete();
            $msg = 'Subject removed.';
        } elseif ($type === 'topic') {
            $topic = Topic::where('id', $id)->where('school_id', $schoolId)->firstOrFail();
            $lessonIds = Lesson::where('topic_id', $topic->id)->pluck('id')->all();
            $this->purgeResourceFiles($lessonIds);
            $back = route('program_head.lesson-studio.subject', $topic->subject_id);
            $topic->delete();
            $msg  = 'Topic removed.';
        } elseif ($type === 'lesson') {
            $lesson = Lesson::where('id', $id)->where('school_id', $schoolId)->firstOrFail();
            $this->purgeResourceFiles([$lesson->id]);
            $back = route('program_head.lesson-studio.topic', [$lesson->subject_id, $lesson->topic_id]);
            $lesson->delete();
            $msg  = 'Lesson removed.';
        } elseif ($type === 'competency') {
            $competency = Competency::where('id', $id)->where('school_id', $schoolId)->firstOrFail();
            $back = route('program_head.lesson-studio.lesson', [
                $competency->subject_id, $competency->topic_id, $competency->lesson_id,
            ]);
            LessonResource::where('competency_id', $competency->id)->update(['competency_id' => null]);
            $competency->delete();
            $msg = 'Competency removed.';
        } else {
            abort(404);
        }

        return redirect($back)->with('success', $msg);
    }

    /** Removes uploaded resource files for the given lesson ids before cascading deletes. */
    private function purgeResourceFiles(array $lessonIds): void
    {
        if (empty($lessonIds)) {
            return;
        }
        $resources = LessonResource::whereIn('lesson_id', $lessonIds)
            ->whereNotNull('file_path')
            ->get(['file_path']);
        foreach ($resources as $r) {
            if (Storage::disk('public')->exists($r->file_path)) {
                Storage::disk('public')->delete($r->file_path);
            }
        }
    }

    /** Reorder Topics, Lessons, or Competencies via drag-and-drop. */
    public function reorder(Request $request)
    {
        $data = $request->validate([
            'type'  => 'required|in:topic,lesson,competency',
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $schoolId = $request->user()->school_id;

        // Competencies use the `sequence` column; topics/lessons use `sort_order`.
        $config = match ($data['type']) {
            'topic'      => ['table' => 'topics',       'column' => 'sort_order'],
            'lesson'     => ['table' => 'lessons',      'column' => 'sort_order'],
            'competency' => ['table' => 'competencies', 'column' => 'sequence'],
        };

        DB::transaction(function () use ($config, $schoolId, $data) {
            foreach ($data['ids'] as $i => $id) {
                DB::table($config['table'])
                    ->where('id', $id)
                    ->where('school_id', $schoolId)
                    ->update([$config['column'] => $i + 1]);
            }
        });

        return response()->json(['ok' => true]);
    }

    /**
     * Update an existing folder. Subject accepts name+code+description+category+is_active;
     * other types only accept name (and description where applicable).
     */
    public function updateFolder(Request $request, string $type, int $id)
    {
        $schoolId = $request->user()->school_id;

        if ($type === 'subject') {
            $data = $request->validate([
                'name'        => 'required|string|max:255',
                'code'        => 'required|string|max:32',
                'description' => 'nullable|string|max:1000',
                'category'    => 'nullable|in:' . implode(',', self::CATEGORIES),
            ]);
            $subject = Subject::where('id', $id)->where('school_id', $schoolId)->firstOrFail();
            // Note: subjects.is_active is NOT updated here — the per-program active flag
            // lives in program_subjects.is_active and is driven by Admissions' enrollment
            // session. Program Heads cannot toggle activation from this screen.
            $subject->update([
                'name'        => $data['name'],
                'code'        => $data['code'],
                'description' => $data['description'] ?? null,
                'category'    => $data['category'] ?? $subject->category,
            ]);
            return back()->with('success', 'Subject updated.');
        }

        if ($type === 'topic') {
            $data = $request->validate([
                'name'        => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
            ]);
            $topic = Topic::where('id', $id)->where('school_id', $schoolId)->firstOrFail();
            $topic->update($data);
            return back()->with('success', 'Topic updated.');
        }

        if ($type === 'lesson') {
            $data = $request->validate([
                'name'        => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
            ]);
            $lesson = Lesson::where('id', $id)->where('school_id', $schoolId)->firstOrFail();
            $lesson->update($data);
            return back()->with('success', 'Lesson updated.');
        }

        if ($type === 'competency') {
            $data = $request->validate([
                'name'        => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
            ]);
            $competency = Competency::where('id', $id)->where('school_id', $schoolId)->firstOrFail();
            $competency->update($data);
            return back()->with('success', 'Competency updated.');
        }

        abort(404);
    }
}
