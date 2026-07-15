<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 2a — per-level attendance configuration. The Principal owns basic-ed
 * levels, the Dean owns higher-ed levels; the band is fixed by the route, so
 * neither role can read or write the other's levels. Config feeds attendance
 * status + (later) grade computation, so band isolation is the key guard.
 */
class AttendanceSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function level(int $schoolId, string $name, int $seq, string $type): int
    {
        return DB::table('academic_levels')->insertGetId([
            'school_id' => $schoolId, 'name' => $name, 'sequence_order' => $seq, 'type' => $type,
        ]);
    }

    public function test_principal_sees_only_basic_levels(): void
    {
        $school = School::factory()->create();
        $principal = User::factory()->create(['school_id' => $school->id, 'role' => 'principal']);
        $this->level($school->id, 'Grade 1', 1, 'basic');
        $this->level($school->id, 'Year 1', 1, 'higher');

        $this->actingAs($principal)
            ->get(route('principal.settings.attendance'))
            ->assertOk()
            ->assertSee('Grade 1')
            ->assertDontSee('Year 1');
    }

    public function test_principal_saves_basic_level_settings(): void
    {
        $school = School::factory()->create();
        $principal = User::factory()->create(['school_id' => $school->id, 'role' => 'principal']);
        $gradeId = $this->level($school->id, 'Grade 1', 1, 'basic');

        $this->actingAs($principal)
            ->post(route('principal.settings.attendance.update'), [
                'settings' => [
                    $gradeId => [
                        'capture_mode' => 'daily',
                        'allow_manual' => '1',
                        'allow_qr' => '1',
                        'late_after' => '07:45',
                        'grace_minutes' => '5',
                        'expected_days_per_period' => '100',
                        'qr_rotation_seconds' => '15',
                        'half_day_enabled' => '1',
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('attendance_settings', [
            'school_id' => $school->id,
            'academic_level_id' => $gradeId,
            'capture_mode' => 'daily',
            'allow_qr' => 1,
            'grace_minutes' => 5,
            'expected_days_per_period' => 100,
            'qr_rotation_seconds' => 15,
            'half_day_enabled' => 1,
        ]);
    }

    public function test_session_mode_drops_the_expected_days_denominator(): void
    {
        $school = School::factory()->create();
        $principal = User::factory()->create(['school_id' => $school->id, 'role' => 'principal']);
        $gradeId = $this->level($school->id, 'Grade 1', 1, 'basic');

        $this->actingAs($principal)->post(route('principal.settings.attendance.update'), [
            'settings' => [
                $gradeId => [
                    'capture_mode' => 'session',
                    'expected_days_per_period' => '100', // irrelevant for session — must be dropped
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('attendance_settings', [
            'academic_level_id' => $gradeId,
            'capture_mode' => 'session',
            'expected_days_per_period' => null,
        ]);
    }

    public function test_principal_cannot_write_a_higher_ed_level(): void
    {
        $school = School::factory()->create();
        $principal = User::factory()->create(['school_id' => $school->id, 'role' => 'principal']);
        $gradeId = $this->level($school->id, 'Grade 1', 1, 'basic');
        $yearId = $this->level($school->id, 'Year 1', 1, 'higher');

        $this->actingAs($principal)->post(route('principal.settings.attendance.update'), [
            'settings' => [
                $gradeId => ['capture_mode' => 'daily'],
                $yearId => ['capture_mode' => 'session'], // crafted — not in the principal's band
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('attendance_settings', ['academic_level_id' => $gradeId]);
        $this->assertDatabaseMissing('attendance_settings', ['academic_level_id' => $yearId]);
    }

    public function test_dean_manages_higher_levels(): void
    {
        $school = School::factory()->create();
        $dean = User::factory()->create(['school_id' => $school->id, 'role' => 'dean']);
        $yearId = $this->level($school->id, 'Year 1', 1, 'higher');

        $this->actingAs($dean)->post(route('dean.settings.attendance.update'), [
            'settings' => [
                $yearId => ['capture_mode' => 'session', 'allow_manual' => '1'],
            ],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('attendance_settings', [
            'school_id' => $school->id,
            'academic_level_id' => $yearId,
            'capture_mode' => 'session',
        ]);
    }

    public function test_each_role_is_locked_out_of_the_others_settings(): void
    {
        $school = School::factory()->create();
        $principal = User::factory()->create(['school_id' => $school->id, 'role' => 'principal']);
        $dean = User::factory()->create(['school_id' => $school->id, 'role' => 'dean']);

        $this->actingAs($principal)->get(route('dean.settings.attendance'))->assertForbidden();
        $this->actingAs($dean)->get(route('principal.settings.attendance'))->assertForbidden();
    }
}
