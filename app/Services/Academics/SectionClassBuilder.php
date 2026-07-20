<?php

namespace App\Services\Academics;

use App\Models\Section;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Builds the classes of a basic-ed (grade-level) section: one class per
 * active grade_level_subject of the section's grade, each with the teacher
 * chosen by the registrar.
 *
 * Classes upsert on (school, subject, term, section) — the same unique key
 * the Registrar Teaching Assignments page uses — so building a section that
 * already has some classes only fills the gaps / replaces teachers, never
 * duplicates.
 */
class SectionClassBuilder
{
    /**
     * The section's learning areas with any existing class + teacher, for the
     * builder form. Each row: subject_id, code, name, class_id|null,
     * teacher_id|null.
     */
    public function subjectsFor(Section $section)
    {
        return DB::table('grade_level_subjects as gls')
            ->join('subjects as s', 's.id', '=', 'gls.subject_id')
            ->leftJoin('classes as c', function ($join) use ($section) {
                $join->on('c.subject_id', '=', 's.id')
                    ->where('c.section_id', $section->id)
                    ->where('c.term_id', $section->term_id)
                    ->where('c.school_id', $section->school_id);
            })
            ->where('gls.education_node_id', $section->education_node_id)
            ->where('gls.is_active', 1)
            ->where('s.school_id', $section->school_id)
            ->orderBy('s.name')
            ->get(['s.id as subject_id', 's.code', 's.name', 'c.id as class_id', 'c.teacher_id']);
    }

    /**
     * Upsert one class per chosen (subject → teacher). Subjects not in the
     * grade's curriculum are rejected; teachers must be this school's
     * teachers, and a teacher with a qualified-subject list must be qualified
     * for the subject (same capability rule as Teaching Assignments).
     *
     * @param  array<int, int>  $teacherBySubject  subject_id => teacher user id
     * @return array{created: int, updated: int}
     */
    public function build(Section $section, array $teacherBySubject): array
    {
        $allowedSubjects = $this->subjectsFor($section)->pluck('subject_id')->map(fn ($i) => (int) $i);

        $teacherIds = collect($teacherBySubject)->filter()->map(fn ($i) => (int) $i)->unique()->values();
        $validTeachers = DB::table('users')
            ->where('school_id', $section->school_id)
            ->where('role', 'teacher')
            ->whereIn('id', $teacherIds)
            ->pluck('id')
            ->map(fn ($i) => (int) $i);

        // userId => [qualified subject ids]; empty list = unrestricted.
        $qualified = [];
        foreach (DB::table('teacher_subjects as ts')
            ->join('teacher_profiles as tp', 'tp.id', '=', 'ts.teacher_id')
            ->whereIn('tp.user_id', $teacherIds)
            ->get(['tp.user_id', 'ts.subject_id']) as $r) {
            $qualified[(int) $r->user_id][] = (int) $r->subject_id;
        }

        $created = 0;
        $updated = 0;

        foreach ($teacherBySubject as $subjectId => $teacherId) {
            $subjectId = (int) $subjectId;
            $teacherId = (int) $teacherId;
            if (! $teacherId) {
                continue; // no teacher chosen for this learning area yet
            }

            if (! $allowedSubjects->contains($subjectId)) {
                throw ValidationException::withMessages([
                    'teachers' => "Subject #{$subjectId} is not part of this grade's curriculum.",
                ]);
            }
            if (! $validTeachers->contains($teacherId)) {
                throw ValidationException::withMessages([
                    'teachers' => 'One of the chosen teachers is not a teacher of this school.',
                ]);
            }
            $list = $qualified[$teacherId] ?? [];
            if ($list !== [] && ! in_array($subjectId, $list, true)) {
                throw ValidationException::withMessages([
                    'teachers' => 'One of the chosen teachers is not assigned to teach that subject.',
                ]);
            }

            $match = [
                'school_id' => $section->school_id,
                'subject_id' => $subjectId,
                'term_id' => $section->term_id,
                'section_id' => $section->id,
            ];
            $existing = DB::table('classes')->where($match)->first(['id', 'teacher_id']);

            if ($existing) {
                if ((int) $existing->teacher_id !== $teacherId) {
                    DB::table('classes')->where('id', $existing->id)
                        ->update(['teacher_id' => $teacherId, 'updated_at' => now()]);
                    $updated++;
                }

                continue;
            }

            DB::table('classes')->insert($match + [
                'teacher_id' => $teacherId,
                'code' => $this->code($subjectId, $section),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $created++;
        }

        return ['created' => $created, 'updated' => $updated];
    }

    private function code(int $subjectId, Section $section): string
    {
        $subject = DB::table('subjects')->where('id', $subjectId)->value('code') ?: 'SUBJ';

        return substr("{$subject}-{$section->name}", 0, 64);
    }
}
