<?php

namespace App\Services\Questions;

use App\Models\AcademicLevel;
use App\Services\Tests\TeacherLevelScope;
use App\Support\EducationLevels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read model for the teacher's Question Bank page.
 *
 * Every row is derived from ONE teacher's own authored questions (the rows the
 * question builders saved with their teacher_id), so the level tabs, filter
 * options and table rows can never surface another teacher's (or another
 * school's) bank. Level tabs resolve through the same academic_level → root
 * mapping the Test Builder tags with, via TeacherLevelScope.
 */
class QuestionBankService
{
    public function __construct(private TeacherLevelScope $levelScope) {}

    /**
     * The teacher's own bank questions, newest first, flattened to the display
     * fields the table needs. Plain ->id/->question_text/… row objects.
     */
    public function questionsFor(int $userId, int $schoolId): Collection
    {
        $roots = EducationLevels::offeredRoots();
        $levelToRoot = $this->levelScope->levelToRootMap($schoolId, $roots);

        $levelNames = AcademicLevel::where('school_id', $schoolId)
            ->orderBy('sequence_order')
            ->pluck('name', 'id');

        $questions = DB::table('questions')
            ->where('school_id', $schoolId)
            ->where('teacher_id', $userId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get([
                'id', 'question_text', 'question_type', 'difficulty', 'points', 'keyword',
                'subject_id', 'topic_id', 'lesson_id', 'competency_id', 'academic_level_id', 'created_at',
            ]);

        $subjectNames = DB::table('subjects')
            ->where('school_id', $schoolId)
            ->pluck('name', 'id');

        $topicNames = DB::table('topics')
            ->whereIn('id', $questions->pluck('topic_id')->filter()->unique())
            ->pluck('name', 'id');

        $competencyNames = DB::table('competencies')
            ->whereIn('id', $questions->pluck('competency_id')->filter()->unique())
            ->pluck('name', 'id');

        $usage = DB::table('test_questions')
            ->whereIn('question_id', $questions->pluck('id'))
            ->selectRaw('question_id, COUNT(DISTINCT test_id) as tests')
            ->groupBy('question_id')
            ->pluck('tests', 'question_id');

        return $questions->map(function ($q) use (
            $levelToRoot, $levelNames, $subjectNames, $topicNames, $competencyNames, $usage
        ) {
            $levelId = (int) $q->academic_level_id;

            return (object) [
                'id' => (int) $q->id,
                'question_text' => (string) $q->question_text,
                'question_type' => (string) $q->question_type,
                'type_label' => self::typeLabel($q->question_type),
                'difficulty' => (string) ($q->difficulty ?? ''),
                'points' => $q->points !== null ? (int) $q->points : null,
                'root' => $levelToRoot[$levelId] ?? null,
                'level_id' => $levelId,
                'level_label' => (string) ($levelNames[$levelId] ?? '—'),
                'subject_id' => $q->subject_id ? (int) $q->subject_id : null,
                'subject_name' => (string) ($q->subject_id ? ($subjectNames[$q->subject_id] ?? '—') : '—'),
                'topic_id' => $q->topic_id ? (int) $q->topic_id : null,
                'topic_name' => (string) ($q->topic_id ? ($topicNames[$q->topic_id] ?? '—') : '—'),
                'competency_name' => (string) ($q->competency_id ? ($competencyNames[$q->competency_id] ?? '—') : '—'),
                'used_in' => (int) ($usage[$q->id] ?? 0),
                'created_label' => $q->created_at ? Carbon::parse($q->created_at)->format('M j, Y') : '—',
            ];
        });
    }

    /**
     * The level tabs for this teacher: offered roots (tree order) the teacher
     * either teaches/advises under or has authored a bank question in — never
     * the whole school's catalogue. Same ->id/->name shape
     * <x-table.level-tabs> expects.
     *
     * @param  Collection  $questions  output of questionsFor()
     */
    public function assignedRoots(int $userId, int $schoolId, Collection $questions): Collection
    {
        $roots = EducationLevels::offeredRoots();

        $present = $this->levelScope->sectionRoots($userId, $schoolId, $roots)
            ->merge($questions->pluck('root')->filter())
            ->map(fn ($id) => (int) $id)
            ->unique();

        return $roots->filter(fn ($r) => $present->contains((int) $r->id))->values();
    }

    /**
     * Grade/Year filter options: the school's academic-level vocabulary in
     * sequence order, limited to levels actually tagged on the given questions.
     *
     * @param  Collection  $questions  output of questionsFor()
     * @return array<int, string>
     */
    public function levelOptions(int $schoolId, Collection $questions): array
    {
        $present = $questions->pluck('level_id')->unique();

        return AcademicLevel::where('school_id', $schoolId)
            ->orderBy('sequence_order')
            ->pluck('name', 'id')
            ->filter(fn ($name, $id) => $present->contains((int) $id))
            ->all();
    }

    /** Friendly label for a stored question_type value. */
    public static function typeLabel(?string $type): string
    {
        return match ($type) {
            'multiple_choice', 'mcq' => 'Multiple Choice',
            'true_false' => 'True/False',
            'mtf' => 'Modified True/False',
            'fib' => 'Fill in the Blank',
            'identification' => 'Identification',
            'enumeration' => 'Enumeration',
            'matching' => 'Matching',
            'essay' => 'Essay',
            null, '' => '—',
            default => ucwords(str_replace('_', ' ', $type)),
        };
    }
}
