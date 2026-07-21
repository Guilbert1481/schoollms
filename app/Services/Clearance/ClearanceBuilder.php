<?php

namespace App\Services\Clearance;

use App\Models\Clearance;
use App\Models\ClearanceSignatory;
use App\Models\StudentEnrollment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Creates a student's clearance: resolves the school's configured signatories
 * (seeding the defaults on first use), filters them by the enrollment's
 * education level, expands the Subject Teachers signatory into one row per
 * (subject, teacher) of the enrollment, and snapshots every label so later
 * signatory edits never rewrite history.
 */
class ClearanceBuilder
{
    public const DEFAULTS = [
        ['name' => 'Finance / Cashier', 'type' => ClearanceSignatory::TYPE_DEPARTMENT],
        ['name' => 'Registrar', 'type' => ClearanceSignatory::TYPE_DEPARTMENT],
        ['name' => 'Guidance', 'type' => ClearanceSignatory::TYPE_DEPARTMENT],
        ['name' => 'Librarian', 'type' => ClearanceSignatory::TYPE_DEPARTMENT],
        ['name' => 'Subject Teachers', 'type' => ClearanceSignatory::TYPE_SUBJECT_TEACHERS],
    ];

    /**
     * The school's signatories applicable to the given level, seeding the
     * default set when the school has none yet. (Deleting every signatory
     * re-seeds the defaults on next use — deliberate: an empty clearance
     * would sign off nobody, which is never what a school means.)
     *
     * @return Collection<int, ClearanceSignatory>
     */
    public function signatoriesFor(int $schoolId, bool $basicEd): Collection
    {
        $this->seedDefaults($schoolId);

        return ClearanceSignatory::query()
            ->where('school_id', $schoolId)
            ->whereIn('applies_to', [ClearanceSignatory::APPLIES_BOTH, $basicEd ? ClearanceSignatory::APPLIES_BASIC : ClearanceSignatory::APPLIES_HIGHER])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function seedDefaults(int $schoolId): void
    {
        if (ClearanceSignatory::query()->where('school_id', $schoolId)->exists()) {
            return;
        }

        foreach (self::DEFAULTS as $i => $default) {
            ClearanceSignatory::create($default + [
                'school_id' => $schoolId,
                'applies_to' => ClearanceSignatory::APPLIES_BOTH,
                'sort_order' => $i,
            ]);
        }
    }

    /** Create the clearance and generate its sign-off rows, atomically. */
    public function start(StudentEnrollment $enrollment, string $purpose, ?string $note): Clearance
    {
        $schoolId = (int) $enrollment->school_id;
        $basicEd = $enrollment->isBasicEd();

        return DB::transaction(function () use ($enrollment, $purpose, $note, $schoolId, $basicEd) {
            $clearance = Clearance::create([
                'school_id' => $schoolId,
                'student_id' => $enrollment->student_id,
                'student_enrollment_id' => $enrollment->id,
                'purpose' => $purpose,
                'note' => $note,
                'status' => Clearance::STATUS_PENDING,
            ]);

            foreach ($this->signatoriesFor($schoolId, $basicEd) as $signatory) {
                if ($signatory->type === ClearanceSignatory::TYPE_SUBJECT_TEACHERS) {
                    foreach ($this->subjectTeacherRows($enrollment) as $row) {
                        $clearance->items()->create($row + [
                            'school_id' => $schoolId,
                            'clearance_signatory_id' => $signatory->id,
                        ]);
                    }
                } else {
                    $clearance->items()->create([
                        'school_id' => $schoolId,
                        'clearance_signatory_id' => $signatory->id,
                        'label' => $signatory->name,
                    ]);
                }
            }

            return $clearance;
        });
    }

    /**
     * One row per (subject, teacher) of the enrollment. Higher-ed walks the
     * per-subject enrollments; basic ed takes every class of the advisory
     * section. classes.teacher_id holds a users id, so names resolve there.
     *
     * @return array<int, array{label: string, subject_id: ?int, teacher_user_id: ?int}>
     */
    private function subjectTeacherRows(StudentEnrollment $enrollment): array
    {
        $classes = DB::table('classes as c')
            ->join('subjects as s', 's.id', '=', 'c.subject_id')
            ->leftJoin('users as t', 't.id', '=', 'c.teacher_id')
            ->when(
                $enrollment->isBasicEd() && $enrollment->section_id,
                fn ($q) => $q->where('c.section_id', $enrollment->section_id),
                fn ($q) => $q->whereIn('c.id', DB::table('student_enrollment_subjects')
                    ->where('student_enrollment_id', $enrollment->id)
                    ->whereNotNull('class_id')
                    ->pluck('class_id'))
            )
            ->where('c.school_id', $enrollment->school_id)
            ->orderBy('s.name')
            ->get(['s.id as subject_id', 's.name as subject_name', 't.id as teacher_id', 't.first_name', 't.last_name']);

        return $classes
            ->unique(fn ($c) => $c->subject_id.'|'.($c->teacher_id ?? 0))
            ->map(function ($c) {
                $teacher = trim(($c->first_name ?? '').' '.($c->last_name ?? ''));

                return [
                    'label' => $c->subject_name.' — '.($teacher !== '' ? $teacher : 'Subject Teacher'),
                    'subject_id' => (int) $c->subject_id,
                    'teacher_user_id' => $c->teacher_id ? (int) $c->teacher_id : null,
                ];
            })
            ->values()
            ->all();
    }
}
