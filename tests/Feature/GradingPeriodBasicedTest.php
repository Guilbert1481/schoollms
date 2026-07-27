<?php

namespace Tests\Feature;

use App\Models\GradeComponent;
use App\Models\GradeSetting;
use App\Models\GradingSetting;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Principal → Settings → Grades → Division: per-school naming of the basic-ed
 * grading periods, plus the reusable <x-grading_period_basiced> picker that
 * every consumer renders.
 *
 * The period stays an ordinal (1..N) throughout the grade pipeline — these
 * settings only change how it reads. Coverage: defaults, the save path, the
 * 1..MAX_PERIODS validation envelope, tenant scoping, the role gate, and that
 * the component renders the configured names (guards the Blade x-tag footgun).
 */
class GradingPeriodBasicedTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_are_the_four_quarters(): void
    {
        $this->assertSame(
            [1 => '1st Quarter', 2 => '2nd Quarter', 3 => '3rd Quarter', 4 => '4th Quarter'],
            GradeSetting::defaultPeriods(),
        );

        $setting = GradeSetting::forSchool(School::factory()->create()->id);
        $this->assertSame('Quarter', $setting->periodLabel());
        $this->assertSame(GradeSetting::defaultPeriods(), $setting->periods());
    }

    public function test_principal_saves_a_label_and_names(): void
    {
        $school = School::factory()->create();
        $principal = User::factory()->create(['school_id' => $school->id, 'role' => 'principal']);

        $this->actingAs($principal)->post(route('principal.settings.grades.division'), [
            'period_label' => 'Term',
            'period_names' => ['First Term', 'Second Term', 'Third Term'],
        ])->assertRedirect()->assertSessionHas('success')->assertSessionHas('grades_tab', 'division');

        $setting = GradeSetting::forSchool($school->id);
        $this->assertSame('Term', $setting->periodLabel());
        $this->assertSame(
            [1 => 'First Term', 2 => 'Second Term', 3 => 'Third Term'],
            $setting->periods(),
        );
    }

    public function test_names_are_trimmed_and_capped_at_the_max(): void
    {
        // periods() drops blanks and never returns more than MAX_PERIODS, even if
        // a stored payload somehow holds more (defence in depth alongside validation).
        $setting = new GradeSetting([
            'period_names' => ['  1st  ', '', '2nd', '3rd', '4th', '5th'],
        ]);

        $this->assertSame(
            [1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th'],
            $setting->periods(),
        );
    }

    public function test_validation_rejects_an_empty_name(): void
    {
        $school = School::factory()->create();
        $principal = User::factory()->create(['school_id' => $school->id, 'role' => 'principal']);

        $this->actingAs($principal)->post(route('principal.settings.grades.division'), [
            'period_label' => 'Quarter',
            'period_names' => ['1st Quarter', ''],
        ])->assertSessionHasErrors('period_names.1');
    }

    public function test_validation_rejects_more_than_the_max_periods(): void
    {
        $school = School::factory()->create();
        $principal = User::factory()->create(['school_id' => $school->id, 'role' => 'principal']);

        $this->actingAs($principal)->post(route('principal.settings.grades.division'), [
            'period_label' => 'Quarter',
            'period_names' => ['1', '2', '3', '4', '5'],
        ])->assertSessionHasErrors('period_names');

        // Nothing persisted from the rejected request.
        $this->assertDatabaseMissing('grade_settings', ['school_id' => $school->id]);
    }

    public function test_validation_requires_a_label(): void
    {
        $school = School::factory()->create();
        $principal = User::factory()->create(['school_id' => $school->id, 'role' => 'principal']);

        $this->actingAs($principal)->post(route('principal.settings.grades.division'), [
            'period_names' => ['1st Quarter'],
        ])->assertSessionHasErrors('period_label');
    }

    public function test_the_setting_is_scoped_to_its_own_school(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        $principalA = User::factory()->create(['school_id' => $schoolA->id, 'role' => 'principal']);

        $this->actingAs($principalA)->post(route('principal.settings.grades.division'), [
            'period_label' => 'Term',
            'period_names' => ['First Term', 'Second Term'],
        ])->assertRedirect();

        // School B is untouched and still reads the defaults.
        $this->assertSame(GradeSetting::defaultPeriods(), GradeSetting::forSchool($schoolB->id)->periods());
    }

    public function test_a_non_principal_cannot_change_the_setting(): void
    {
        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);

        $this->actingAs($teacher)->post(route('principal.settings.grades.division'), [
            'period_label' => 'Term',
            'period_names' => ['First Term'],
        ])->assertForbidden();

        $this->assertDatabaseMissing('grade_settings', ['school_id' => $school->id, 'period_label' => 'Term']);
    }

    public function test_component_renders_the_configured_period_names(): void
    {
        $school = School::factory()->create();
        $setting = GradeSetting::forSchool($school->id);
        $setting->update(['period_label' => 'Term', 'period_names' => ['First Term', 'Second Term', 'Third Term']]);

        $html = Blade::render(
            '<x-grading_period_basiced name="period" :selected="2" :setting="$s" />',
            ['s' => $setting->fresh()],
        );

        // The noun caption + configured option labels render, and the selected
        // ordinal is marked — the whole component compiles (x-tag footgun guard).
        $this->assertStringContainsString('Term', $html);
        $this->assertStringContainsString('First Term', $html);
        $this->assertStringContainsString('Third Term', $html);
        $this->assertStringContainsString('name="period"', $html);
        $this->assertMatchesRegularExpression('/<option value="2"[^>]*selected[^>]*>Second Term<\/option>/', $html);
    }

    public function test_component_falls_back_to_defaults_without_a_setting(): void
    {
        $html = Blade::render('<x-grading_period_basiced name="grading_period" :selected="1" />');

        $this->assertStringContainsString('1st Quarter', $html);
        $this->assertStringContainsString('4th Quarter', $html);
        $this->assertStringContainsString('name="grading_period"', $html);
    }

    public function test_period_count_reflects_the_configuration(): void
    {
        $school = School::factory()->create();

        // No row yet → the default count.
        $this->assertSame(4, GradeSetting::periodCountForSchool($school->id));

        GradeSetting::forSchool($school->id)->update(['period_names' => ['A', 'B', 'C']]);
        $this->assertSame(3, GradeSetting::periodCountForSchool($school->id));
    }

    public function test_all_blank_names_fall_back_to_defaults(): void
    {
        // A non-empty-but-all-blank array must never yield an empty period list.
        $setting = new GradeSetting(['period_names' => ['', '  ', "\t"]]);

        $this->assertSame(GradeSetting::defaultPeriods(), $setting->periods());
    }

    public function test_reducing_periods_below_posted_grades_is_rejected(): void
    {
        $school = School::factory()->create();
        $principal = User::factory()->create(['school_id' => $school->id, 'role' => 'principal']);

        // A posted 4th-period grade already exists on the record for this school.
        $student = Student::create([
            'school_id' => $school->id, 'student_number' => 'S-'.uniqid(),
            'first_name' => 'A', 'last_name' => 'B',
        ]);
        $nodeId = DB::table('education_nodes')->insertGetId(['name' => 'Grade 5', 'node_type' => 'grade']);
        $subjectId = DB::table('subjects')->insertGetId(['school_id' => $school->id, 'code' => 'MATH', 'name' => 'Math']);
        DB::table('report_card_grades')->insert([
            'school_id' => $school->id, 'student_id' => $student->id,
            'education_node_id' => $nodeId, 'subject_id' => $subjectId,
            'academic_year_id' => null, 'grading_period' => 4, 'final_grade' => 88.00,
        ]);

        // Shrinking to 2 periods would orphan the posted 4th-period grade — reject it.
        $this->actingAs($principal)->post(route('principal.settings.grades.division'), [
            'period_label' => 'Quarter',
            'period_names' => ['1st', '2nd'],
        ])->assertSessionHasErrors('period_names');

        // The config was NOT reduced — it still reads the (default) full set.
        $this->assertSame(GradeSetting::defaultPeriods(), GradeSetting::forSchool($school->id)->periods());
    }

    public function test_reducing_periods_below_draft_component_scores_is_rejected(): void
    {
        $school = School::factory()->create();
        $principal = User::factory()->create(['school_id' => $school->id, 'role' => 'principal']);
        $student = Student::create([
            'school_id' => $school->id, 'student_number' => 'S-'.uniqid(),
            'first_name' => 'A', 'last_name' => 'B',
        ]);

        // A grade component to hang the draft score on (component_scores FK target).
        $levelId = DB::table('academic_levels')->insertGetId([
            'school_id' => $school->id, 'name' => 'Grade 5', 'sequence_order' => 5, 'type' => 'basic',
        ]);
        $setting = GradingSetting::create([
            'school_id' => $school->id, 'academic_level_id' => $levelId,
            'scale_type' => 'percentage', 'passing_mark' => 75, 'attendance_weight' => 0,
        ]);
        $componentId = GradeComponent::create([
            'school_id' => $school->id, 'grading_setting_id' => $setting->id,
            'name' => 'WW', 'weight' => 100, 'sort_order' => 0,
        ])->id;

        // A DRAFT component score at period 3 — never posted, so report_card_grades
        // is empty. The old guard (report_card_grades only) would miss this.
        DB::table('component_scores')->insert([
            'school_id' => $school->id, 'student_id' => $student->id,
            'grade_component_id' => $componentId, 'grading_period' => 3, 'score' => 85,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Reducing to 2 periods would strand the period-3 draft — reject it.
        $this->actingAs($principal)->post(route('principal.settings.grades.division'), [
            'period_label' => 'Quarter',
            'period_names' => ['1st', '2nd'],
        ])->assertSessionHasErrors('period_names');

        $this->assertSame(GradeSetting::defaultPeriods(), GradeSetting::forSchool($school->id)->periods());
    }

    public function test_duplicate_period_names_are_rejected(): void
    {
        $school = School::factory()->create();
        $principal = User::factory()->create(['school_id' => $school->id, 'role' => 'principal']);

        // Two periods sharing a name would render as indistinguishable options.
        $this->actingAs($principal)->post(route('principal.settings.grades.division'), [
            'period_label' => 'Quarter',
            'period_names' => ['Midterm', 'midterm', 'Final'],
        ])->assertSessionHasErrors('period_names.0');

        $this->assertDatabaseMissing('grade_settings', ['school_id' => $school->id, 'period_label' => 'Quarter']);
    }
}
