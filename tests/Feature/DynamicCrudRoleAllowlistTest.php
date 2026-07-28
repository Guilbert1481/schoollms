<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The generic CRUD endpoints (school/system/dynamic/*) used to sit behind ONE
 * flat list of 15 roles covering every registered table. That let a teacher,
 * course_architect or subject_coordinator update `subjects` — and, while they
 * were registered, `topics` and `lessons` — straight from the endpoint, walking
 * around the ownership rules the curricula panels and the Lesson Studio enforce
 * on their own screens.
 *
 * Now each table declares its own `roles` allowlist and the endpoints fail
 * closed. These tests pin that shut.
 */
class DynamicCrudRoleAllowlistTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'Nursing Practice',
            'code' => 'NCM-101',
            'is_active' => true,
        ]);
    }

    private function user(string $role): User
    {
        return User::factory()->create([
            'school_id' => $this->school->id,
            'role' => $role,
        ]);
    }

    private function updateSubject(User $as, string $name)
    {
        return $this->actingAs($as)->put(route('school.system.dynamic.update'), [
            'table' => 'subjects',
            'id' => $this->subject->id,
            'name' => $name,
        ]);
    }

    /* ── The roles that lost access ─────────────────────────────────────── */

    public static function excludedRoles(): array
    {
        return [
            'teacher' => ['teacher'],
            'course_architect' => ['course_architect'],
            'subject_coordinator' => ['subject_coordinator'],
            'registrar' => ['registrar'],
            'program_head' => ['program_head'],
            'guidance_counselor' => ['guidance_counselor'],
            'finance_manager' => ['finance_manager'],
        ];
    }

    /**
     * @dataProvider excludedRoles
     */
    public function test_role_cannot_update_a_subject_through_the_generic_endpoint(string $role): void
    {
        $this->updateSubject($this->user($role), 'Hijacked')->assertForbidden();

        $this->assertSame('Nursing Practice', $this->subject->fresh()->name);
    }

    /* ── The roles that legitimately keep it ────────────────────────────── */

    public function test_principal_can_still_update_a_subject(): void
    {
        $this->updateSubject($this->user('principal'), 'Renamed by principal')
            ->assertRedirect();

        $this->assertSame('Renamed by principal', $this->subject->fresh()->name);
    }

    public function test_dean_can_still_update_a_subject(): void
    {
        $this->updateSubject($this->user('dean'), 'Renamed by dean')
            ->assertRedirect();

        $this->assertSame('Renamed by dean', $this->subject->fresh()->name);
    }

    public function test_admin_can_still_update_a_subject(): void
    {
        $this->updateSubject($this->user('admin'), 'Renamed by admin')
            ->assertRedirect();

        $this->assertSame('Renamed by admin', $this->subject->fresh()->name);
    }

    /* ── Per-table, not per-route: a role gets only its own tables ──────── */

    public function test_principal_cannot_update_a_table_it_does_not_own(): void
    {
        $officeId = DB::table('offices')->insertGetId([
            'school_id' => $this->school->id,
            'name' => 'Registrar Office',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // `offices` is admin/superadmin only — the principal is on the ROUTE's
        // role list but not on this TABLE's, which is the whole point.
        $this->actingAs($this->user('principal'))
            ->put(route('school.system.dynamic.update'), [
                'table' => 'offices',
                'id' => $officeId,
                'name' => 'Hijacked',
            ])
            ->assertForbidden();

        $this->assertSame('Registrar Office', DB::table('offices')->where('id', $officeId)->value('name'));
    }

    public function test_dean_can_update_curriculums_but_not_departments(): void
    {
        $dean = $this->user('dean');
        $departmentId = DB::table('departments')->insertGetId([
            'school_id' => $this->school->id,
            'name' => 'College of Nursing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($dean)
            ->put(route('school.system.dynamic.update'), [
                'table' => 'departments',
                'id' => $departmentId,
                'name' => 'Hijacked',
            ])
            ->assertForbidden();

        $this->assertSame(
            'College of Nursing',
            DB::table('departments')->where('id', $departmentId)->value('name')
        );
    }

    /* ── topics / lessons are unregistered entirely ─────────────────────── */

    public function test_topics_and_lessons_are_not_reachable_by_anyone(): void
    {
        foreach (['topics', 'lessons'] as $table) {
            foreach (['admin', 'superadmin', 'dean', 'principal'] as $role) {
                $this->actingAs($this->user($role))
                    ->put(route('school.system.dynamic.update'), [
                        'table' => $table,
                        'id' => 1,
                        'name' => 'Hijacked',
                    ])
                    ->assertNotFound();
            }
        }
    }

    /* ── Fails closed ───────────────────────────────────────────────────── */

    public function test_a_registered_table_without_a_roles_key_is_refused(): void
    {
        // Simulate a newly registered table whose owner was never declared.
        config(['tables.tables.subjects' => ['columns' => ['name']]]);

        $this->updateSubject($this->user('admin'), 'Hijacked')->assertForbidden();

        $this->assertSame('Nursing Practice', $this->subject->fresh()->name);
    }

    public function test_unregistered_tables_are_still_404_not_403(): void
    {
        $this->actingAs($this->user('admin'))
            ->put(route('school.system.dynamic.update'), [
                'table' => 'users',
                'id' => 1,
                'role' => 'superadmin',
            ])
            ->assertNotFound();
    }

    /* ── Tenant scoping still holds on top of the role check ────────────── */

    public function test_another_schools_subject_is_untouched(): void
    {
        $otherSchool = School::factory()->create();
        $otherSubject = Subject::create([
            'school_id' => $otherSchool->id,
            'name' => 'Foreign Subject',
            'code' => 'FOR-1',
            'is_active' => true,
        ]);

        $this->actingAs($this->user('principal'))
            ->put(route('school.system.dynamic.update'), [
                'table' => 'subjects',
                'id' => $otherSubject->id,
                'name' => 'Hijacked',
            ]);

        $this->assertSame('Foreign Subject', $otherSubject->fresh()->name);
    }
}
