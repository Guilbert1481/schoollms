<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\School;
use App\Models\SchoolRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Per-school role subscriptions: the superadmin picks which catalog roles a
 * school may use when registering/editing a partner (config/roles.php ->
 * school_roles), and the school admin's User Management is gated to exactly
 * those roles — both in the UI (dropdown / Roles tab) and, critically, on the
 * server so a crafted POST cannot mint or assign an unsubscribed role.
 *
 * This is an entitlement/UX gate layered on top of the role: route middleware
 * (ADR-0002); it is not itself the access-control boundary.
 */
class RoleSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    /** Subscribe a school to the given role keys and materialise their role rows. */
    private function subscribe(School $school, array $enabled, array $disabled = []): void
    {
        foreach ($enabled as $key) {
            SchoolRole::create(['school_id' => $school->id, 'role_key' => $key, 'is_enabled' => true]);
            Role::create(['school_id' => $school->id, 'name' => $key]);
        }

        foreach ($disabled as $key) {
            SchoolRole::create(['school_id' => $school->id, 'role_key' => $key, 'is_enabled' => false]);
            Role::create(['school_id' => $school->id, 'name' => $key]);
        }
    }

    public function test_enabled_role_keys_returns_only_enabled_intersected_with_catalog(): void
    {
        $school = School::factory()->create();
        $this->subscribe($school, enabled: ['admin', 'teacher'], disabled: ['dean']);
        // A stale key no longer in the catalog must never leak through.
        SchoolRole::create(['school_id' => $school->id, 'role_key' => 'ghost_role', 'is_enabled' => true]);

        $keys = $school->enabledRoleKeys();
        sort($keys);

        $this->assertSame(['admin', 'teacher'], $keys);
    }

    public function test_school_with_no_subscription_rows_falls_back_to_full_catalog(): void
    {
        $school = School::factory()->create();

        $this->assertSame(SchoolRole::catalogKeys(), $school->enabledRoleKeys());
    }

    public function test_user_management_only_exposes_subscribed_roles(): void
    {
        $school = School::factory()->create();
        $this->subscribe($school, enabled: ['admin', 'teacher', 'student'], disabled: ['dean']);
        $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('settings.users.index'))
            ->assertOk()
            ->assertSee('Teacher')
            ->assertSee('Student')
            ->assertDontSee('Dean');
    }

    public function test_creating_a_user_with_an_unsubscribed_role_is_rejected(): void
    {
        $school = School::factory()->create();
        $this->subscribe($school, enabled: ['admin', 'teacher'], disabled: ['dean']);
        $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('settings.users.store'), [
                'first_name' => 'Nope',
                'last_name'  => 'Dean',
                'email'      => 'nope.dean@example.test',
                'password'   => 'secret123',
                'role'       => 'dean', // subscribed as DISABLED — must be refused
            ])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'nope.dean@example.test']);
    }

    public function test_creating_a_user_with_a_subscribed_role_succeeds(): void
    {
        $school = School::factory()->create();
        $this->subscribe($school, enabled: ['admin', 'teacher']);
        $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('settings.users.store'), [
                'first_name' => 'Tina',
                'last_name'  => 'Teacher',
                'email'      => 'tina.teacher@example.test',
                'password'   => 'secret123',
                'role'       => 'teacher',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email'     => 'tina.teacher@example.test',
            'role'      => 'teacher',
            'school_id' => $school->id,
        ]);
    }

    public function test_admin_cannot_mint_an_unsubscribed_role_via_store_role(): void
    {
        $school = School::factory()->create();
        $this->subscribe($school, enabled: ['admin', 'teacher']);
        $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('roles.store'), [
                'role_name'    => 'Dean',
                'is_head_role' => '0',
            ])
            ->assertSessionHasErrors('roles');

        $this->assertDatabaseMissing('roles', ['school_id' => $school->id, 'name' => 'dean']);
    }

    public function test_registering_a_partner_persists_the_selected_role_subscription(): void
    {
        $super = User::factory()->create(['role' => 'superadmin', 'school_id' => null]);

        $this->actingAs($super)
            ->post(route('superadmin.schools.store'), [
                'school_name' => 'Little Sprouts Academy',
                'code'        => 'LSA',
                'slug'        => 'little-sprouts',
                'type'        => 'school',
                'first_name'  => 'Ada',
                'last_name'   => 'Admin',
                'email'       => 'ada.admin@example.test',
                'password'    => 'secret123',
                'password_confirmation' => 'secret123',
                'roles'       => ['teacher', 'student', 'principal'], // basic-ed subset
            ])
            ->assertRedirect(route('superadmin.schools.index'));

        $school = School::where('slug', 'little-sprouts')->firstOrFail();

        // 'admin' is auto-included (the primary admin account uses it).
        $keys = $school->enabledRoleKeys();
        sort($keys);
        $this->assertSame(['admin', 'principal', 'student', 'teacher'], $keys);

        // Higher-ed roles were NOT selected → no subscription row at all.
        $this->assertDatabaseMissing('school_roles', ['school_id' => $school->id, 'role_key' => 'dean']);

        // Each enabled role got a materialised `roles` row for assignment.
        $this->assertDatabaseHas('roles', ['school_id' => $school->id, 'name' => 'principal']);
    }
}
