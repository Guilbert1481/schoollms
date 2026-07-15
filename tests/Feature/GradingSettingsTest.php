<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 2b — per-level grading configuration. Principal owns basic-ed levels,
 * Dean owns higher-ed levels; the band is fixed by the route so neither role
 * can touch the other's. Grade weights are grade-critical, so this covers the
 * component sync and band isolation.
 */
class GradingSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function level(int $schoolId, string $name, int $seq, string $type): int
    {
        return DB::table('academic_levels')->insertGetId([
            'school_id' => $schoolId, 'name' => $name, 'sequence_order' => $seq, 'type' => $type,
        ]);
    }

    public function test_principal_saves_a_basic_scheme_with_components(): void
    {
        $school = School::factory()->create();
        $principal = User::factory()->create(['school_id' => $school->id, 'role' => 'principal']);
        $gradeId = $this->level($school->id, 'Grade 1', 1, 'basic');

        $this->actingAs($principal)->post(route('principal.settings.grading.update'), [
            'settings' => [
                $gradeId => [
                    'scale_type' => 'percentage',
                    'passing_mark' => '75',
                    'attendance_weight' => '10',
                    'components' => [
                        ['name' => 'Written Work', 'weight' => '30'],
                        ['name' => 'Performance Tasks', 'weight' => '40'],
                        ['name' => 'Quarterly Assessment', 'weight' => '20'],
                    ],
                ],
            ],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('grading_settings', [
            'school_id' => $school->id,
            'academic_level_id' => $gradeId,
            'scale_type' => 'percentage',
            'passing_mark' => 75,
            'attendance_weight' => 10,
        ]);
        $this->assertDatabaseCount('grade_components', 3);
        $this->assertDatabaseHas('grade_components', [
            'school_id' => $school->id,
            'name' => 'Written Work',
            'weight' => 30,
            'sort_order' => 0,
        ]);
    }

    public function test_saving_replaces_the_component_set(): void
    {
        $school = School::factory()->create();
        $principal = User::factory()->create(['school_id' => $school->id, 'role' => 'principal']);
        $gradeId = $this->level($school->id, 'Grade 1', 1, 'basic');

        $post = fn (array $components) => $this->actingAs($principal)->post(route('principal.settings.grading.update'), [
            'settings' => [$gradeId => ['scale_type' => 'percentage', 'passing_mark' => '75', 'components' => $components]],
        ])->assertRedirect();

        $post([['name' => 'A', 'weight' => '50'], ['name' => 'B', 'weight' => '50']]);
        $this->assertDatabaseCount('grade_components', 2);

        // Re-save with a single component — the old two must be replaced, not kept.
        $post([['name' => 'Only', 'weight' => '100']]);
        $this->assertDatabaseCount('grade_components', 1);
        $this->assertDatabaseHas('grade_components', ['name' => 'Only']);
        $this->assertDatabaseMissing('grade_components', ['name' => 'A']);
    }

    public function test_blank_component_rows_are_skipped(): void
    {
        $school = School::factory()->create();
        $principal = User::factory()->create(['school_id' => $school->id, 'role' => 'principal']);
        $gradeId = $this->level($school->id, 'Grade 1', 1, 'basic');

        $this->actingAs($principal)->post(route('principal.settings.grading.update'), [
            'settings' => [
                $gradeId => [
                    'scale_type' => 'percentage', 'passing_mark' => '75',
                    'components' => [
                        ['name' => 'Real', 'weight' => '100'],
                        ['name' => '', 'weight' => '0'], // blank — skipped
                    ],
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseCount('grade_components', 1);
    }

    public function test_principal_cannot_write_a_higher_ed_level(): void
    {
        $school = School::factory()->create();
        $principal = User::factory()->create(['school_id' => $school->id, 'role' => 'principal']);
        $gradeId = $this->level($school->id, 'Grade 1', 1, 'basic');
        $yearId = $this->level($school->id, 'Year 1', 1, 'higher');

        $this->actingAs($principal)->post(route('principal.settings.grading.update'), [
            'settings' => [
                $gradeId => ['scale_type' => 'percentage', 'passing_mark' => '75'],
                $yearId => ['scale_type' => 'gpa', 'passing_mark' => '3'], // crafted — out of band
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('grading_settings', ['academic_level_id' => $gradeId]);
        $this->assertDatabaseMissing('grading_settings', ['academic_level_id' => $yearId]);
    }

    public function test_dean_manages_higher_grading_and_principal_is_locked_out(): void
    {
        $school = School::factory()->create();
        $dean = User::factory()->create(['school_id' => $school->id, 'role' => 'dean']);
        $prin = User::factory()->create(['school_id' => $school->id, 'role' => 'principal']);
        $yearId = $this->level($school->id, 'Year 1', 1, 'higher');

        $this->actingAs($dean)->post(route('dean.settings.grading.update'), [
            'settings' => [$yearId => ['scale_type' => 'gpa', 'passing_mark' => '3']],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('grading_settings', [
            'academic_level_id' => $yearId, 'scale_type' => 'gpa',
        ]);

        $this->actingAs($prin)->get(route('dean.settings.grading'))->assertForbidden();
    }
}
