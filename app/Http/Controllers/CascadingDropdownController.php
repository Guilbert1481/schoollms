<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Topic;
use App\Models\Lesson;
use App\Models\Competency;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CascadingDropdownController extends Controller
{
    /**
     * =========================
     * PROGRAMS (school-scoped)
     * =========================
     */
    public function programs(): JsonResponse
    {
        $schoolId = auth()->user()->school_id ?? null;

        return response()->json(
            DB::table('programs')
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->orderBy('name')
                ->get(['id', 'code', 'name'])
                ->map(fn ($p) => ['id' => $p->id, 'name' => $p->code . ' — ' . $p->name])
        );
    }

    /**
     * =========================
     * PROGRAM → SUBJECTS (via program_subjects pivot)
     * =========================
     */
    public function programSubjects(int $programId): JsonResponse
    {
        $rows = DB::table('program_subjects as ps')
            ->join('subjects as s', 's.id', '=', 'ps.subject_id')
            ->where('ps.program_id', $programId)
            ->orderBy('ps.year_level')
            ->orderBy('ps.semester_number')
            ->orderBy('s.name')
            ->get([
                's.id',
                's.code',
                's.name',
                'ps.year_level',
                'ps.semester_number',
            ]);

        $semesterLabel = [1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th', 5 => 'Summer'];

        return response()->json($rows->map(fn ($r) => [
            'id'   => $r->id,
            'name' => sprintf(
                '%s — %s (Y%d %s)',
                $r->code,
                $r->name,
                (int) $r->year_level,
                $semesterLabel[(int) $r->semester_number] ?? $r->semester_number
            ),
        ]));
    }

    /**
     * =========================
     * SUBJECTS (school-scoped, with code label for searchable selects)
     * =========================
     */
    public function subjects(): JsonResponse
    {
        $schoolId = auth()->user()->school_id ?? null;

        return response()->json(
            Subject::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->orderBy('code')
                ->get(['id', 'code', 'name'])
                ->map(fn ($s) => [
                    'id'    => $s->id,
                    'code'  => $s->code,
                    'name'  => $s->name,
                    'label' => $s->code . ' — ' . $s->name,
                ])
        );
    }

    /**
     * =========================
     * SUBJECT → LINKED PROGRAMS (with year + semester)
     * =========================
     */
    public function subjectPrograms(Subject $subject): JsonResponse
    {
        $rows = DB::table('program_subjects as ps')
            ->join('programs as p', 'p.id', '=', 'ps.program_id')
            ->where('ps.subject_id', $subject->id)
            ->orderBy('p.code')
            ->orderBy('ps.year_level')
            ->orderBy('ps.semester_number')
            ->get([
                'p.id',
                'p.code',
                'p.name',
                'ps.year_level',
                'ps.semester_number',
            ]);

        $semLabel = [1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th', 5 => 'Summer'];

        return response()->json($rows->map(fn ($r) => [
            'id'         => $r->id,
            'code'       => $r->code,
            'name'       => $r->name,
            'year_level' => (int) $r->year_level,
            'semester'   => $semLabel[(int) $r->semester_number] ?? (string) $r->semester_number,
            'badge'      => sprintf(
                '%s: Yr %d, Sem %s',
                $r->code,
                (int) $r->year_level,
                $semLabel[(int) $r->semester_number] ?? $r->semester_number
            ),
        ]));
    }

    /**
     * =========================
     * SUBJECT → TOPICS
     * =========================
     */
    public function topics(Subject $subject): JsonResponse
    {
        return response()->json(
            Topic::where('subject_id', $subject->id)
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
        );
    }

    /**
     * =========================
     * TOPIC → LESSONS
     * =========================
     */
    /**
 * SUBJECT → TOPICS
 */
    public function lessons($topicId): JsonResponse
    {
        // The if check is good for safety
        if (!is_numeric($topicId)) {
            return response()->json([], 200);
        }

        // This fetches lessons where the topic_id matches the URL parameter
        return response()->json(
            Lesson::where('topic_id', $topicId)
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
        );
    }


    /**
     * =========================
     * LESSON → COMPETENCIES
     * =========================
     */
    public function competencies(Lesson $lesson): JsonResponse
    {
        return response()->json(
            Competency::where('lesson_id', $lesson->id)
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
        );
    }
}
