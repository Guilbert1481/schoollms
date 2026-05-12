<?php

namespace App\Http\Controllers\CourseArchitect;

use App\Http\Controllers\Controller;
use App\Models\Competency;
use App\Models\Lesson;
use App\Models\LessonResource;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LessonStudioController extends Controller
{
    /** Allowed upload extensions, mapped to a normalized file_type. */
    private const FILE_TYPES = [
        'mp4'  => 'video', 'mov' => 'video', 'webm' => 'video', 'mkv' => 'video',
        'pdf'  => 'pdf',
        'ppt'  => 'ppt',  'pptx' => 'ppt',
    ];

    /**
     * Hierarchical folder browser:
     *   Level 0 — Subjects
     *   Level 1 — Topics inside a Subject
     *   Level 2 — Lessons inside a Topic
     *   Level 3 — Competencies + lesson resources inside a Lesson
     */
    public function index(Request $request, $subject = null, $topic = null, $lesson = null)
    {
        $schoolId = $request->user()->school_id;

        // Resolve & authorize each parent in the chain.
        $subjectModel = $subject ? Subject::findOrFail($subject) : null;
        $topicModel   = $topic   ? Topic::where('id', $topic)
                                        ->where('subject_id', $subject)
                                        ->firstOrFail() : null;
        $lessonModel  = $lesson  ? Lesson::where('id', $lesson)
                                        ->where('topic_id', $topic)
                                        ->firstOrFail() : null;

        $level = $lessonModel ? 3 : ($topicModel ? 2 : ($subjectModel ? 1 : 0));

        $breadcrumbs = [['label' => 'Subjects', 'url' => route('course-architect.lesson-studio.index')]];
        if ($subjectModel) {
            $breadcrumbs[] = [
                'label' => $subjectModel->name,
                'url'   => route('course-architect.lesson-studio.subject', $subjectModel->id),
            ];
        }
        if ($topicModel) {
            $breadcrumbs[] = [
                'label' => $topicModel->name,
                'url'   => route('course-architect.lesson-studio.topic', [$subjectModel->id, $topicModel->id]),
            ];
        }
        if ($lessonModel) {
            $breadcrumbs[] = [
                'label' => $lessonModel->name,
                'url'   => route('course-architect.lesson-studio.lesson', [$subjectModel->id, $topicModel->id, $lessonModel->id]),
            ];
        }

        $folders   = [];
        $resources = collect();
        $columns   = [];
        $listColumns = [];
        $listData    = collect();
        $listTableKey = null;

        if ($level === 0) {
            $folders = DB::table('subjects')
                ->where('school_id', $schoolId)
                ->orderBy('name')
                ->get(['id', 'name', 'code'])
                ->map(fn ($s) => [
                    'id'       => $s->id,
                    'name'     => $s->name,
                    'subtitle' => $s->code,
                    'count'    => DB::table('topics')->where('subject_id', $s->id)->count(),
                    'url'      => route('course-architect.lesson-studio.subject', $s->id),
                ])->values()->all();

            $listTableKey = 'lesson_studio_subjects';
            $listColumns  = [
                ['key' => 'name',         'label' => 'Subject Name', 'raw' => true],
                ['key' => 'code',         'label' => 'Code'],
                ['key' => 'topics_count', 'label' => 'Topics'],
            ];
            $listData = collect($folders)->map(fn ($f) => (object) [
                'id'           => $f['id'],
                'name'         => '<a href="' . e($f['url']) . '" class="font-medium text-indigo-700 hover:underline inline-flex items-center gap-1.5">'
                                  . '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>'
                                  . e($f['name']) . '</a>',
                'code'         => $f['subtitle'] ?? '—',
                'topics_count' => $f['count'],
                '_url'         => $f['url'],
            ]);
        } elseif ($level === 1) {
            $folders = DB::table('topics')
                ->where('subject_id', $subjectModel->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'name'])
                ->map(fn ($t) => [
                    'id'       => $t->id,
                    'name'     => $t->name,
                    'subtitle' => null,
                    'count'    => DB::table('lessons')->where('topic_id', $t->id)->count(),
                    'url'      => route('course-architect.lesson-studio.topic', [$subjectModel->id, $t->id]),
                ])->values()->all();

            $listTableKey = 'lesson_studio_topics';
            $listColumns  = [
                ['key' => 'name',          'label' => 'Topic Name', 'raw' => true],
                ['key' => 'lessons_count', 'label' => 'Lessons'],
            ];
            $listData = collect($folders)->map(fn ($f) => (object) [
                'id'            => $f['id'],
                'name'          => '<a href="' . e($f['url']) . '" class="font-medium text-indigo-700 hover:underline inline-flex items-center gap-1.5">'
                                   . '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>'
                                   . e($f['name']) . '</a>',
                'lessons_count' => $f['count'],
                '_url'          => $f['url'],
            ]);
        } elseif ($level === 2) {
            $folders = DB::table('lessons')
                ->where('topic_id', $topicModel->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'name'])
                ->map(fn ($l) => [
                    'id'       => $l->id,
                    'name'     => $l->name,
                    'subtitle' => null,
                    'count'    => DB::table('lesson_resources')->where('lesson_id', $l->id)->count(),
                    'url'      => route('course-architect.lesson-studio.lesson', [$subjectModel->id, $topicModel->id, $l->id]),
                ])->values()->all();

            $listTableKey = 'lesson_studio_lessons';
            $listColumns  = [
                ['key' => 'name',            'label' => 'Lesson Name', 'raw' => true],
                ['key' => 'resources_count', 'label' => 'Resources'],
            ];
            $listData = collect($folders)->map(fn ($f) => (object) [
                'id'              => $f['id'],
                'name'            => '<a href="' . e($f['url']) . '" class="font-medium text-indigo-700 hover:underline inline-flex items-center gap-1.5">'
                                     . '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>'
                                     . e($f['name']) . '</a>',
                'resources_count' => $f['count'],
                '_url'            => $f['url'],
            ]);
        } else { // Level 3 — show table of competencies/resources for this lesson
            $resources = DB::table('lesson_resources as lr')
                ->leftJoin('competencies as c', 'c.id', '=', 'lr.competency_id')
                ->where('lr.school_id', $schoolId)
                ->where('lr.lesson_id', $lessonModel->id)
                ->orderBy('lr.id')
                ->get([
                    'lr.id',
                    'c.name as competency_name',
                    'lr.file_type',
                    'lr.file_path',
                ])
                ->values()
                ->map(function ($r, $i) {
                    $r->row_no = $i + 1;
                    $r->competency_display = $r->competency_name ?? '—';

                    $icon = ['video' => '🎬', 'pdf' => '📄', 'ppt' => '📊'][$r->file_type] ?? '📎';
                    $r->resource_html = $r->file_path
                        ? '<button type="button" onclick="openLessonPreview(' . (int) $r->id . ')" '
                            . 'class="inline-flex items-center gap-1 text-indigo-600 hover:underline cursor-pointer">'
                            . $icon . ' ' . strtoupper(e($r->file_type)) . '</button>'
                        : '—';
                    return $r;
                });

            $columns = [
                ['key' => 'row_no',             'label' => '#'],
                ['key' => 'competency_display', 'label' => 'Competency'],
                ['key' => 'resource_html',      'label' => 'Resource', 'raw' => true],
            ];
        }

        return view('course-architect.construction.lesson-studio', [
            'level'        => $level,
            'breadcrumbs'  => $breadcrumbs,
            'folders'      => $folders,
            'resources'    => $resources,
            'columns'      => $columns,
            'listColumns'  => $listColumns,
            'listData'     => $listData,
            'listTableKey' => $listTableKey,
            'subjectModel' => $subjectModel,
            'topicModel'   => $topicModel,
            'lessonModel'  => $lessonModel,
        ]);
    }

    /**
     * Create a folder appropriate to the current level:
     *   Level 1 (inside a Subject)  → Topic
     *   Level 2 (inside a Topic)    → Lesson
     *   Level 3 (inside a Lesson)   → Competency
     */
    public function createFolder(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'subject_id' => 'nullable|integer|exists:subjects,id',
            'topic_id'   => 'nullable|integer|exists:topics,id',
            'lesson_id'  => 'nullable|integer|exists:lessons,id',
        ]);

        $schoolId = $request->user()->school_id;

        if (! empty($data['lesson_id'])) {
            // Competency under a Lesson
            Competency::create([
                'school_id'  => $schoolId,
                'subject_id' => $data['subject_id'],
                'topic_id'   => $data['topic_id'],
                'lesson_id'  => $data['lesson_id'],
                'name'       => $data['name'],
            ]);
            $msg = 'Competency added.';
            $back = route('course-architect.lesson-studio.lesson', [
                $data['subject_id'], $data['topic_id'], $data['lesson_id'],
            ]);
        } elseif (! empty($data['topic_id'])) {
            // Lesson under a Topic
            Lesson::create([
                'school_id'  => $schoolId,
                'subject_id' => $data['subject_id'],
                'topic_id'   => $data['topic_id'],
                'name'       => $data['name'],
            ]);
            $msg = 'Lesson added.';
            $back = route('course-architect.lesson-studio.topic', [$data['subject_id'], $data['topic_id']]);
        } elseif (! empty($data['subject_id'])) {
            // Topic under a Subject
            Topic::create([
                'school_id'  => $schoolId,
                'subject_id' => $data['subject_id'],
                'name'       => $data['name'],
            ]);
            $msg = 'Topic added.';
            $back = route('course-architect.lesson-studio.subject', $data['subject_id']);
        } else {
            return back()->withErrors(['name' => 'Subjects are managed in the Master List, not here.']);
        }

        return redirect($back)->with('success', $msg);
    }

    /**
     * Delete a folder (Topic / Lesson / Competency) and all its children.
     * Cascades through descendants AND the lesson_resources stored under them,
     * removing orphaned uploaded files from disk.
     */
    public function destroyFolder(Request $request, string $type, int $id)
    {
        $schoolId = $request->user()->school_id;

        // Collect lesson_ids that will disappear so we can remove their files.
        $lessonIds = [];
        $back = route('course-architect.lesson-studio.index');

        if ($type === 'topic') {
            $topic = Topic::where('id', $id)->where('school_id', $schoolId)->firstOrFail();
            $lessonIds = Lesson::where('topic_id', $topic->id)->pluck('id')->all();
            $back = route('course-architect.lesson-studio.subject', $topic->subject_id);
            $msg  = 'Topic removed.';
        } elseif ($type === 'lesson') {
            $lesson = Lesson::where('id', $id)->where('school_id', $schoolId)->firstOrFail();
            $lessonIds = [$lesson->id];
            $back = route('course-architect.lesson-studio.topic', [$lesson->subject_id, $lesson->topic_id]);
            $msg  = 'Lesson removed.';
        } elseif ($type === 'competency') {
            $competency = Competency::where('id', $id)->where('school_id', $schoolId)->firstOrFail();
            $back = route('course-architect.lesson-studio.lesson', [
                $competency->subject_id, $competency->topic_id, $competency->lesson_id,
            ]);
            // Detach competency from any resources, then delete competency itself.
            LessonResource::where('competency_id', $competency->id)->update(['competency_id' => null]);
            $competency->delete();
            return redirect($back)->with('success', 'Competency removed.');
        }

        // For topic/lesson: remove resource files first, then DB rows (cascades via FK).
        if (! empty($lessonIds)) {
            $resources = LessonResource::whereIn('lesson_id', $lessonIds)
                ->whereNotNull('file_path')
                ->get(['file_path']);
            foreach ($resources as $r) {
                if (Storage::disk('public')->exists($r->file_path)) {
                    Storage::disk('public')->delete($r->file_path);
                }
            }
        }

        if ($type === 'topic') {
            $topic->delete();
        } elseif ($type === 'lesson') {
            $lesson->delete();
        }

        return redirect($back)->with('success', $msg);
    }

    /**
     * Persist a new ordering for Topics (level 1) or Lessons (level 2).
     * Body: { type: 'topic'|'lesson', ids: [int, ...] }
     */
    public function reorder(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:topic,lesson',
            'ids'  => 'required|array|min:1',
            'ids.*'=> 'integer',
        ]);

        $schoolId = $request->user()->school_id;
        $table    = $data['type'] === 'topic' ? 'topics' : 'lessons';

        DB::transaction(function () use ($table, $schoolId, $data) {
            foreach ($data['ids'] as $i => $id) {
                DB::table($table)
                    ->where('id', $id)
                    ->where('school_id', $schoolId)
                    ->update(['sort_order' => $i + 1]);
            }
        });

        return response()->json(['ok' => true]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject_id'    => 'required|integer|exists:subjects,id',
            'topic_id'      => 'required|integer|exists:topics,id',
            'lesson_id'     => 'required|integer|exists:lessons,id',
            'competency_id' => 'nullable|integer|exists:competencies,id',
            'file'          => 'nullable|file|max:204800|mimes:mp4,mov,webm,mkv,pdf,ppt,pptx',
        ]);

        $user = $request->user();
        $file = $request->file('file');

        $path = $type = $origName = $size = null;

        if ($file) {
            $ext = strtolower($file->getClientOriginalExtension());
            if (! isset(self::FILE_TYPES[$ext])) {
                return back()->withErrors(['file' => 'Unsupported file type.']);
            }
            $path     = $file->store("lesson-resources/{$user->school_id}", 'public');
            $type     = self::FILE_TYPES[$ext];
            $origName = $file->getClientOriginalName();
            $size     = $file->getSize();
        }

        LessonResource::create([
            'school_id'         => $user->school_id,
            'program_id'        => null,
            'subject_id'        => $data['subject_id'],
            'topic_id'          => $data['topic_id'],
            'lesson_id'         => $data['lesson_id'],
            'competency_id'     => $data['competency_id'] ?? null,
            'file_path'         => $path,
            'file_type'         => $type,
            'original_filename' => $origName,
            'file_size'         => $size,
            'created_by'        => $user->id,
        ]);

        return redirect()
            ->route('course-architect.lesson-studio.lesson', [
                $data['subject_id'], $data['topic_id'], $data['lesson_id'],
            ])
            ->with('success', 'Lesson resource added.');
    }

    public function destroy(Request $request, LessonResource $lessonResource)
    {
        if ($lessonResource->school_id !== $request->user()->school_id) {
            abort(403);
        }

        if ($lessonResource->file_path && Storage::disk('public')->exists($lessonResource->file_path)) {
            Storage::disk('public')->delete($lessonResource->file_path);
        }

        $lessonResource->delete();

        return redirect()
            ->route('course-architect.lesson-studio.lesson', [
                $lessonResource->subject_id, $lessonResource->topic_id, $lessonResource->lesson_id,
            ])
            ->with('success', 'Lesson resource removed.');
    }

    /** Returns JSON payload for in-modal preview. */
    public function preview(Request $request, LessonResource $lessonResource)
    {
        if ($lessonResource->school_id !== $request->user()->school_id) {
            abort(403);
        }

        return response()->json([
            'id'        => $lessonResource->id,
            'file_type' => $lessonResource->file_type,
            'file_url'  => $lessonResource->file_path
                ? asset('storage/' . $lessonResource->file_path)
                : null,
            'filename'  => $lessonResource->original_filename,
        ]);
    }

    /** Returns JSON payload for the edit modal. */
    public function edit(Request $request, LessonResource $lessonResource)
    {
        if ($lessonResource->school_id !== $request->user()->school_id) {
            abort(403);
        }

        return response()->json([
            'id'             => $lessonResource->id,
            'subject_id'     => $lessonResource->subject_id,
            'topic_id'       => $lessonResource->topic_id,
            'lesson_id'      => $lessonResource->lesson_id,
            'competency_id'  => $lessonResource->competency_id,
            'file_type'      => $lessonResource->file_type,
            'file_url'       => $lessonResource->file_path
                ? asset('storage/' . $lessonResource->file_path)
                : null,
            'filename'       => $lessonResource->original_filename,
        ]);
    }

    public function update(Request $request, LessonResource $lessonResource)
    {
        if ($lessonResource->school_id !== $request->user()->school_id) {
            abort(403);
        }

        $data = $request->validate([
            'subject_id'     => 'required|integer|exists:subjects,id',
            'topic_id'       => 'required|integer|exists:topics,id',
            'lesson_id'      => 'required|integer|exists:lessons,id',
            'competency_id'  => 'nullable|integer|exists:competencies,id',
            'file'           => 'nullable|file|max:204800|mimes:mp4,mov,webm,mkv,pdf,ppt,pptx',
        ]);

        $payload = [
            'subject_id'     => $data['subject_id'],
            'topic_id'       => $data['topic_id'],
            'lesson_id'      => $data['lesson_id'],
            'competency_id'  => $data['competency_id'] ?? null,
        ];

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $ext  = strtolower($file->getClientOriginalExtension());
            if (! isset(self::FILE_TYPES[$ext])) {
                return back()->withErrors(['file' => 'Unsupported file type.']);
            }

            // delete old file
            if ($lessonResource->file_path && Storage::disk('public')->exists($lessonResource->file_path)) {
                Storage::disk('public')->delete($lessonResource->file_path);
            }

            $payload['file_path']         = $file->store("lesson-resources/{$request->user()->school_id}", 'public');
            $payload['file_type']         = self::FILE_TYPES[$ext];
            $payload['original_filename'] = $file->getClientOriginalName();
            $payload['file_size']         = $file->getSize();
        }

        $lessonResource->update($payload);

        return redirect()
            ->route('course-architect.lesson-studio.lesson', [
                $lessonResource->subject_id, $lessonResource->topic_id, $lessonResource->lesson_id,
            ])
            ->with('success', 'Lesson resource updated.');
    }
}

