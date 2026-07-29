<?php

namespace Tests\Feature;

use App\Models\GradeSetting;
use App\Models\School;
use App\Models\Student;
use App\Services\Academics\ReportCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The basic-ed Report Card must average EVERY configured grading period. A prior
 * hardcoded [1,2,3] silently dropped the 4th quarter from each subject Final, the
 * period averages, the General Average, and the Promoted/Retained standing — so a
 * strong Q4 could not lift a failing standing, and the Q4 column never rendered.
 * The service now drives its periods from the school's GradeSetting::periods().
 */
class ReportCardPeriodsTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_card_counts_every_configured_quarter_including_the_fourth(): void
    {
        $school = School::factory()->create();
        // Default config = 4 quarters; default passing threshold = 75.
        GradeSetting::forSchool($school->id);

        $ayId = DB::table('academic_years')->insertGetId([
            'school_id' => $school->id, 'name' => '2026-2027', 'is_active' => true,
        ]);
        $termId = DB::table('terms')->insertGetId([
            'school_id' => $school->id, 'academic_year' => '2026-2027', 'enrollment_type' => 'x',
            'term' => 'FY', 'name' => 'Basic Ed', 'education_level' => 'basic_ed',
            'start_date' => '2026-06-01', 'end_date' => '2027-03-31',
        ]);
        $nodeId = DB::table('education_nodes')->insertGetId(['name' => 'Grade 5', 'node_type' => 'grade']);
        $subjectId = DB::table('subjects')->insertGetId(['school_id' => $school->id, 'code' => 'SCI', 'name' => 'Science']);
        DB::table('grade_level_subjects')->insert([
            'education_node_id' => $nodeId, 'subject_id' => $subjectId, 'is_active' => 1,
        ]);

        $student = Student::create([
            'school_id' => $school->id, 'student_number' => 'S-'.uniqid(),
            'first_name' => 'Q', 'last_name' => 'Four',
        ]);
        DB::table('student_enrollments')->insert([
            'school_id' => $school->id, 'student_id' => $student->id,
            'academic_year_id' => $ayId, 'term_id' => $termId,
            'education_node_id' => $nodeId, 'status' => 'enrolled',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Q1–Q3 = 70, Q4 = 90. Correct year-end final = (70+70+70+90)/4 = 75 → Passed/Promoted.
        // The old [1,2,3] logic averaged only 210/3 = 70 → Failed/Retained.
        foreach ([1 => 70, 2 => 70, 3 => 70, 4 => 90] as $period => $grade) {
            DB::table('report_card_grades')->insert([
                'school_id' => $school->id, 'student_id' => $student->id,
                'education_node_id' => $nodeId, 'subject_id' => $subjectId,
                'academic_year_id' => $ayId, 'grading_period' => $period, 'final_grade' => $grade,
            ]);
        }

        $report = app(ReportCardService::class)->build($student);

        // All four configured quarters are present as columns.
        $this->assertSame([1, 2, 3, 4], $report['periods']);

        // The 4th quarter is counted in the subject Final and the General Average.
        $row = $report['rows']->firstWhere('subject_id', $subjectId);
        $this->assertNotNull($row);
        $this->assertSame('75', $row['final']);          // not '70'
        $this->assertSame('Passed', $row['final_remark']);
        $this->assertSame(75.0, (float) $report['general_average']);
        $this->assertSame('Promoted', $report['remark']); // not 'Retained'
    }
}
