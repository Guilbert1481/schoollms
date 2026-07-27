<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Seeds the lessons (each with one competency) for the cardiovascular Med-Surg
 * topics under NCM 112.
 *
 * Depends on Ncm112MedSurgTopicsSeeder having created the topics. For each of the
 * four topics with prepared content, this creates a Lesson row per lesson and a
 * Competency row bound to it. Topics without content here are left untouched.
 *
 * Idempotent: lessons resolve by (school_id, topic_id, name); competencies by
 * (school_id, lesson_id, name). Safe to re-run. A topic that is missing is
 * skipped with a warning rather than aborting the run.
 *
 *     php artisan db:seed --class=Ncm112CardiovascularLessonsSeeder
 */
class Ncm112CardiovascularLessonsSeeder extends Seeder
{
    private const SCHOOL_ID = 1;

    private const SUBJECT_CODE = 'NCM-112';

    public function run(): void
    {
        $subjectId = DB::table('subjects')
            ->where('school_id', self::SCHOOL_ID)
            ->where('code', self::SUBJECT_CODE)
            ->where('is_basic_ed', 0)
            ->value('id');

        if (! $subjectId) {
            throw new RuntimeException('NCM-112 subject not found. Run BsNursingCurriculumSeeder first.');
        }

        $now = now();
        $lessonCount = 0;
        $compCount = 0;
        $skipped = [];

        foreach ($this->content() as $topicName => $lessons) {
            $topicId = DB::table('topics')
                ->where('school_id', self::SCHOOL_ID)
                ->where('subject_id', $subjectId)
                ->where('name', $topicName)
                ->value('id');

            if (! $topicId) {
                $skipped[] = $topicName;

                continue;
            }

            $order = 0;
            foreach ($lessons as [$lessonName, $competency]) {
                $order++;

                $lessonId = DB::table('lessons')
                    ->where('school_id', self::SCHOOL_ID)
                    ->where('topic_id', $topicId)
                    ->where('name', $lessonName)
                    ->value('id');

                $lessonData = [
                    'subject_id' => $subjectId,
                    'sort_order' => $order,
                    'sequence' => $order,
                    'is_active' => 1,
                    'updated_at' => $now,
                ];

                if ($lessonId) {
                    DB::table('lessons')->where('id', $lessonId)->update($lessonData);
                } else {
                    $lessonId = DB::table('lessons')->insertGetId($lessonData + [
                        'school_id' => self::SCHOOL_ID,
                        'topic_id' => $topicId,
                        'name' => $lessonName,
                        'created_at' => $now,
                    ]);
                    $lessonCount++;
                }

                $compData = [
                    'subject_id' => $subjectId,
                    'topic_id' => $topicId,
                    'bloom_level' => $this->bloom($competency),
                    'sequence' => $order,
                    'is_active' => 1,
                    'updated_at' => $now,
                ];

                $existingComp = DB::table('competencies')
                    ->where('school_id', self::SCHOOL_ID)
                    ->where('lesson_id', $lessonId)
                    ->where('name', $competency)
                    ->value('id');

                if ($existingComp) {
                    DB::table('competencies')->where('id', $existingComp)->update($compData);
                } else {
                    DB::table('competencies')->insert($compData + [
                        'school_id' => self::SCHOOL_ID,
                        'lesson_id' => $lessonId,
                        'name' => $competency,
                        'created_at' => $now,
                    ]);
                    $compCount++;
                }
            }
        }

        $this->command?->info(sprintf(
            'Seeded %d lessons and %d competencies under NCM 112.%s',
            $lessonCount,
            $compCount,
            $skipped ? ' Skipped (topic missing): '.implode(', ', $skipped).'.' : '',
        ));
    }

    /** Map the leading verb of a competency to a Bloom's level. */
    protected function bloom(string $competency): string
    {
        $first = strtolower(strtok($competency, ' '));
        $map = [
            'define' => 'understand', 'explain' => 'understand', 'describe' => 'understand',
            'identify' => 'understand', 'recognize' => 'understand', 'classify' => 'understand',
            'trace' => 'apply', 'apply' => 'apply', 'perform' => 'apply', 'obtain' => 'apply',
            'calculate' => 'apply', 'provide' => 'apply', 'correctly' => 'apply', 'safely' => 'apply',
            'differentiate' => 'analyze', 'compare' => 'analyze', 'interpret' => 'analyze',
        ];

        return $map[$first] ?? 'understand';
    }

    /**
     * Lessons and their single competency, grouped by topic name. Topic names must
     * match those created by Ncm112MedSurgTopicsSeeder.
     *
     * @return array<string, array<int, array{0: string, 1: string}>>
     */
    protected function content(): array
    {
        return [
            'Cardiac Fundamentals' => [
                ['Blood Flow Through the Heart', 'Trace the flow of blood through the heart and lungs.'],
                ['Location & Layers of the Heart', 'Describe the location of the heart and the three layers of its wall.'],
                ['Chambers & Valves', 'Identify the heart chambers and valves and their functions.'],
                ['Coronary Circulation', 'Describe the coronary arteries and the myocardial regions they supply.'],
                ['Electrical Conduction System', 'Trace the cardiac electrical conduction pathway.'],
                ['Cardiac Physical Assessment', 'Perform a focused cardiovascular physical assessment.'],
                ['The Apical Pulse', 'Correctly locate and assess the apical pulse.'],
                ['Cardiac Health History', 'Obtain a focused cardiac history, including chest pain (OPQRST) and dyspnea.'],
            ],
            'Cardiovascular Diagnostic Procedures' => [
                ['Cardiovascular Risk Factors', 'Identify modifiable and non-modifiable cardiovascular risk factors.'],
                ['Cardiac Markers & Serum Enzymes', 'Interpret cardiac markers and serum enzymes and their nursing implications.'],
                ['Blood Chemistry, Electrolytes & Lipids', 'Interpret the blood chemistry, electrolyte, and lipid studies relevant to cardiac care.'],
                ['Coagulation & Hematologic Studies', 'Interpret the coagulation and hematologic studies used in cardiac care.'],
                ['Cardiac Stress Testing', 'Describe exercise and pharmacologic stress testing and the related nursing care.'],
                ['Echocardiography', 'Compare transthoracic, transesophageal, and 2-D echocardiography and their nursing care.'],
                ['Cardiac Catheterization & Perfusion Imaging', 'Describe cardiac catheterization and myocardial perfusion imaging and their nursing care.'],
                ['Advanced Imaging: CT, PET, MRA', 'Describe cardiac CT, PET, and MRA and their nursing considerations.'],
                ['The Electrocardiogram — Basics', 'Explain ECG paper, depolarization and repolarization, and the conduction basis of the ECG.'],
                ['ECG Waves, Complexes & Intervals', 'Identify the ECG waves, complexes, and intervals and their normal values.'],
                ['Determining Heart Rate', 'Calculate heart rate from an ECG rhythm strip.'],
                ['Rhythms & Dysrhythmias', 'Recognize normal sinus rhythm and common dysrhythmias.'],
            ],
            'Cardiomyopathy' => [
                ['Overview & Terminology', "Define cardiomyopathy and explain how it impairs the heart's pumping ability."],
                ['Types of Cardiomyopathy', 'Differentiate the main types of cardiomyopathy.'],
                ['Pathophysiology & Compensation', "Explain the pathophysiology of dilated cardiomyopathy and the body's compensatory mechanisms."],
                ['Clinical Manifestations', 'Identify the clinical manifestations of cardiomyopathy.'],
                ['Diagnostic Evaluation', 'Interpret the diagnostic tests used to evaluate cardiomyopathy.'],
                ['Pharmacologic Management', 'Describe the ABCD drug therapy used in cardiomyopathy.'],
                ['Lifestyle Modification & Nursing Care', 'Provide lifestyle education (DRESS) and priority nursing care.'],
            ],
            'Congestive Heart Failure (CHF)' => [
                ['Overview & Pathophysiology', 'Explain the pathophysiology of heart failure and the factors that determine cardiac output.'],
                ['Etiology & Risk Factors', 'Identify the causes and risk factors of heart failure.'],
                ['Types & Classification', 'Differentiate the types of heart failure and classify severity using the NYHA scale.'],
                ['Clinical Manifestations', 'Differentiate the acute and chronic clinical manifestations of heart failure.'],
                ['Diagnostic Studies', 'Interpret the diagnostic studies used to identify heart failure and its cause.'],
                ['Medical & Collaborative Management', 'Describe the collaborative management of acute and chronic heart failure.'],
                ['Pharmacologic Management', 'Safely administer and monitor the drug therapy for heart failure.'],
                ['Nursing Management', 'Apply the nursing process to the care of a client with heart failure.'],
                ['Health Education & Discharge Planning', 'Provide health education and discharge planning for the client with heart failure.'],
            ],
        ];
    }
}
