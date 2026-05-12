<?php

namespace Database\Seeders;

use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\Program;
use App\Models\Subject;
use App\Models\Section;
use App\Models\Term;
use App\Models\User;
use App\Models\ClassModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds a working Higher-Ed Irregular path for BSED-Math:
 *  - One active curriculum.
 *  - ~6 curriculum_subjects mapped to existing GEN-ED / PROF-ED rows.
 *  - One section (Year 1 block) for the active term.
 *  - One class per curriculum subject, with code/room/schedule/capacity so
 *    the public class picker has data to render.
 */
class BsedMathCurriculumSeeder extends Seeder
{
    public function run(): void
    {
        $program = Program::where('school_id', 1)
            ->where(function ($q) {
                $q->where('code', 'BSED - Math')->orWhere('code', 'BSED-Math');
            })->first()
            ?? Program::where('school_id', 1)->where('id', 1)->first();

        if (!$program) {
            $this->command?->error('BSED-Math program not found. Aborting.');
            return;
        }

        $term = Term::where('school_id', 1)->where('is_active', true)
            ->orderByDesc('is_current')->orderByDesc('id')->first();
        if (!$term) {
            $this->command?->error('No active term found. Aborting.');
            return;
        }

        $teacher = User::where('school_id', 1)->where('role', 'teacher')->first();
        if (!$teacher) {
            $this->command?->error('No teacher user found. Aborting.');
            return;
        }

        DB::transaction(function () use ($program, $term, $teacher) {
            // 1) Curriculum
            $curriculum = Curriculum::firstOrCreate(
                [
                    'school_id'  => $program->school_id,
                    'program_id' => $program->id,
                    'version'    => '2024',
                ],
                [
                    'name'             => 'BSEd Math Curriculum 2024',
                    'terms_per_year'   => 2,
                    'has_summer_term'  => true,
                    'is_active'        => true,
                    'effective_from'   => now()->startOfYear()->toDateString(),
                    'description'      => 'Bachelor of Secondary Education major in Mathematics.',
                ]
            );

            // 2) Curriculum subjects — map a handful of existing GEN-ED + PROF-ED
            //    subjects across years 1–2.
            $subjectMap = [
                ['code' => 'GEN-US-101',   'year' => 1, 'sem' => 1, 'core' => true],
                ['code' => 'GEN-MMW-102',  'year' => 1, 'sem' => 1, 'core' => true],
                ['code' => 'GEN-PC-103',   'year' => 1, 'sem' => 2, 'core' => true],
                ['code' => 'GEN-FITD-104', 'year' => 1, 'sem' => 2, 'core' => true],
                ['code' => 'PROF-CAL-101', 'year' => 2, 'sem' => 1, 'core' => true],
            ];

            $subjects = [];
            foreach ($subjectMap as $row) {
                $subj = Subject::where('school_id', $program->school_id)
                    ->where('code', $row['code'])->first();
                if (!$subj) continue;

                CurriculumSubject::firstOrCreate(
                    [
                        'curriculum_id' => $curriculum->id,
                        'subject_id'    => $subj->id,
                    ],
                    [
                        'year_level'  => $row['year'],
                        'semester'    => $row['sem'],
                        'is_core'     => $row['core'],
                        'is_elective' => false,
                        'units'       => $subj->units ?? 3,
                    ]
                );
                $subjects[] = $subj;
            }

            // 3) Section (Year 1 block) for the active term.
            $section = Section::firstOrCreate(
                [
                    'school_id'  => $program->school_id,
                    'program_id' => $program->id,
                    'term_id'    => $term->id,
                    'name'       => 'BSED-Math 1A',
                ],
                [
                    'year_level' => 1,
                    'capacity'   => 40,
                    'is_active'  => true,
                    'status'     => 'active',
                ]
            );

            // 4) Classes — one per subject, with rich scheduling metadata.
            $rooms     = ['Rm 101', 'Rm 102', 'Rm 201', 'Rm 202', 'Rm 301'];
            $schedules = [
                'MWF 7:30 AM – 8:30 AM',
                'MWF 8:30 AM – 9:30 AM',
                'TTh 9:00 AM – 10:30 AM',
                'TTh 10:30 AM – 12:00 PM',
                'MWF 1:00 PM – 2:00 PM',
            ];

            foreach ($subjects as $i => $subj) {
                ClassModel::firstOrCreate(
                    [
                        'school_id'  => $program->school_id,
                        'subject_id' => $subj->id,
                        'term_id'    => $term->id,
                        'section_id' => $section->id,
                    ],
                    [
                        'teacher_id'   => $teacher->id,
                        'code'         => sprintf('%s-1A', $subj->code),
                        'room'         => $rooms[$i % count($rooms)],
                        'schedule'     => $schedules[$i % count($schedules)],
                        'capacity'     => 40,
                        'max_students' => 40,
                        'is_active'    => true,
                        'is_open'      => true,
                    ]
                );
            }
        });

        $this->command?->info('Seeded BSED-Math curriculum, subjects, section, and classes.');
    }
}
