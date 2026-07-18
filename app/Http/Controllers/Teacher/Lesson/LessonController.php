<?php

namespace App\Http\Controllers\Teacher\Lesson;

use App\Http\Controllers\Controller;
use App\Models\Competency;
use App\Models\Lesson;
use App\Models\LessonResource;
use App\Models\Subject;
use App\Models\TeacherHiddenCompetency;
use App\Models\Topic;
use App\Support\SubjectScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Teacher → Lessons. Lists the real lessons the teacher can author, grouped by
 * the subjects assigned to them (teacher_subjects), and lets them add a lesson
 * under a topic. Lessons live in the shared Subject → Topic → Lesson hierarchy,
 * so this reuses those tables rather than inventing a parallel store.
 */
class LessonController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // The teacher's own subjects: school + academic/training scope + assigned.
        $subjects = SubjectScope::forTeacher(Subject::query(), $user)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        // Real lessons under those subjects, with their topic name.
        $lessons = Lesson::query()
            ->whereIn('lessons.subject_id', $subjects->pluck('id'))
            ->leftJoin('topics', 'topics.id', '=', 'lessons.topic_id')
            ->orderBy('topics.sort_order')
            ->orderBy('lessons.sort_order')
            ->orderBy('lessons.name')
            ->get([
                'lessons.id',
                'lessons.subject_id',
                'lessons.name',
                'lessons.is_active',
                'lessons.updated_at',
                'lessons.created_by',
                'topics.name as topic_name',
            ]);

        // Competencies the Course Architect authored under those lessons. Scoped
        // by school (Competency has no tenant trait) and to the teacher's lessons.
        $competencies = Competency::query()
            ->whereIn('lesson_id', $lessons->pluck('id'))
            ->where('school_id', $user->school_id)
            ->where('is_active', true)
            ->orderBy('sequence')
            ->orderBy('name')
            ->get(['id', 'subject_id', 'topic_id', 'lesson_id', 'code', 'name', 'description', 'bloom_level', 'created_by']);

        // Competencies this teacher has hidden from their own view.
        $hiddenIds = TeacherHiddenCompetency::where('user_id', $user->id)
            ->pluck('competency_id')->all();

        // Attached media (public-disk files) grouped by competency, for the kebab
        // "View" action and the view modal.
        $resourcesByCompetency = LessonResource::whereIn('competency_id', $competencies->pluck('id'))
            ->whereNotNull('file_path')
            ->get(['competency_id', 'file_type', 'original_filename', 'file_path'])
            ->groupBy('competency_id');

        // The teacher's Grade level per subject (subject → their class → section
        // year level → academic_level), used to pre-fill the "Assessment Level"
        // when authoring a question from a competency. Null when not derivable.
        $levelBySubject = [];
        foreach ($subjects as $s) {
            $yearLevel = DB::table('classes as c')
                ->join('sections as sec', 'sec.id', '=', 'c.section_id')
                ->where('c.subject_id', $s->id)
                ->where('c.teacher_id', $user->id)
                ->value('sec.year_level');

            $levelBySubject[$s->id] = $yearLevel === null ? null
                : DB::table('academic_levels')
                    ->where('school_id', $user->school_id)
                    ->where('type', 'basic')
                    ->where('sequence_order', $yearLevel)
                    ->value('id');
        }

        // Group lessons under each subject so the page mirrors "your subjects",
        // and hang each lesson's competencies off it for the expand-on-click list.
        $groups = $subjects->map(fn ($s) => [
            'id' => $s->id,
            'code' => $s->code,
            'name' => $s->name,
            'lessons' => $lessons->where('subject_id', $s->id)->map(fn ($l) => [
                'id' => $l->id,
                'name' => $l->name,
                'topic' => $l->topic_name,
                'is_active' => (bool) $l->is_active,
                'updated' => optional($l->updated_at)->diffForHumans(),
                'updated_ts' => $l->updated_at ? $l->updated_at->getTimestamp() : 0,
                // "Mine" = the teacher authored this lesson; else it's the shared
                // (Course Architect) curriculum shown under "Default".
                'mine' => $l->created_by !== null && (int) $l->created_by === (int) $user->id,
                'competencies' => $competencies->where('lesson_id', $l->id)->map(fn ($c) => [
                    'id' => $c->id,
                    'subject_id' => $c->subject_id,
                    'topic_id' => $c->topic_id,
                    'lesson_id' => $c->lesson_id,
                    'academic_level_id' => $levelBySubject[$c->subject_id] ?? null,
                    'code' => $c->code,
                    'name' => $c->name,
                    'description' => $c->description,
                    'bloom' => $c->bloom_level,
                    'mine' => $c->created_by !== null && (int) $c->created_by === (int) $user->id,
                    'hidden' => in_array($c->id, $hiddenIds, true),
                    'resources' => ($resourcesByCompetency[$c->id] ?? collect())->map(fn ($r) => [
                        'type' => $r->file_type,
                        'name' => $r->original_filename,
                        'url' => Storage::disk('public')->url($r->file_path),
                    ])->values(),
                ])->values(),
            ])->values(),
        ])->values();

        return view('teacher.lessons.index', [
            'groups' => $groups,
            'subjects' => $subjects,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        // Defense in depth: a teacher may only author under their own subjects,
        // even if the subject_id is crafted past the (already scoped) dropdown.
        $assignedIds = SubjectScope::restrictToTeacher(Subject::query(), $user)->pluck('id')->all();

        $data = $request->validate([
            'subject_id' => ['required', 'integer', Rule::in($assignedIds)],
            'topic' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
        ], [
            'subject_id.in' => 'You are not assigned to that subject.',
        ]);

        $schoolId = $user->school_id;

        // Reuse an existing topic of the same name under this subject, else make one.
        $topic = Topic::firstOrCreate([
            'school_id' => $schoolId,
            'subject_id' => $data['subject_id'],
            'name' => trim($data['topic']),
        ]);

        Lesson::create([
            'school_id' => $schoolId,
            'subject_id' => $data['subject_id'],
            'topic_id' => $topic->id,
            'name' => trim($data['name']),
            'description' => $data['description'] ?? null,
            'created_by' => $user->id,
        ]);

        // Land back on "Mine" so the just-created lesson is visible.
        return redirect()
            ->route('teacher.lessons.index')
            ->with('success', 'Lesson added.')
            ->with('mode', 'mine');
    }
}
