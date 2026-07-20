<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * account_access is staff-only: students, parents, and alumni must never get a
 * row. Regression for the 2026-07-20 bug where editing a student (e.g. a
 * password reset) crashed with a NOT NULL violation on person_id — students
 * have no profiles row — rolling back the whole save.
 */
class UserManagementNonStaffAccessTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->admin = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => 'admin',
        ]);
    }

    private function makeStudent(): User
    {
        $user = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => 'student',
        ]);

        Student::create([
            'school_id' => $this->school->id,
            'user_id' => $user->id,
            'student_number' => 'S-'.uniqid(),
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
        ]);

        return $user;
    }

    public function test_admin_can_change_a_student_password_without_account_access(): void
    {
        $student = $this->makeStudent();

        $response = $this->actingAs($this->admin)->put(route('settings.users.update', $student), [
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'email' => $student->email,
            'role' => 'student',
            'password' => 'new-password-123',
        ]);

        $response->assertRedirect(route('settings.users.index'));
        $response->assertSessionHas('success');

        $this->assertTrue(Hash::check('new-password-123', $student->fresh()->password));
        $this->assertDatabaseMissing('account_access', ['user_id' => $student->id]);
    }

    public function test_editing_a_staff_user_still_syncs_account_access(): void
    {
        $teacher = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => 'teacher',
        ]);
        DB::table('profiles')->insert([
            'user_id' => $teacher->id,
            'school_id' => $this->school->id,
            'profile_type' => 'employee',
            'first_name' => $teacher->first_name,
            'last_name' => $teacher->last_name,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->put(route('settings.users.update', $teacher), [
            'first_name' => $teacher->first_name,
            'last_name' => $teacher->last_name,
            'email' => $teacher->email,
            'role' => 'teacher',
        ]);

        $response->assertRedirect(route('settings.users.index'));

        $this->assertDatabaseHas('account_access', [
            'user_id' => $teacher->id,
            'is_active' => 1,
            'role_snapshot' => 'Teacher',
        ]);
    }

    public function test_switching_staff_to_a_non_staff_role_retires_their_access(): void
    {
        $teacher = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => 'teacher',
        ]);
        $profileId = DB::table('profiles')->insertGetId([
            'user_id' => $teacher->id,
            'school_id' => $this->school->id,
            'profile_type' => 'employee',
            'first_name' => $teacher->first_name,
            'last_name' => $teacher->last_name,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $roleId = DB::table('roles')->insertGetId([
            'school_id' => $this->school->id,
            'name' => 'teacher',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('account_access')->insert([
            'user_id' => $teacher->id,
            'role_id' => $roleId,
            'person_id' => $profileId,
            'role_snapshot' => 'Teacher',
            'start_date' => now(),
            'assigned_by' => $this->admin->id,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->put(route('settings.users.update', $teacher), [
            'first_name' => $teacher->first_name,
            'last_name' => $teacher->last_name,
            'email' => $teacher->email,
            'role' => 'student',
        ]);

        $response->assertRedirect(route('settings.users.index'));

        $this->assertDatabaseMissing('account_access', [
            'user_id' => $teacher->id,
            'is_active' => 1,
        ]);
    }

    public function test_creating_a_student_via_user_management_creates_no_account_access(): void
    {
        $response = $this->actingAs($this->admin)->post(route('settings.users.store'), [
            'first_name' => 'New',
            'last_name' => 'Student',
            'email' => 'new.student@example.test',
            'password' => 'secret-123',
            'role' => 'student',
        ]);

        $response->assertSessionHas('success');

        $created = User::where('email', 'new.student@example.test')->first();
        $this->assertNotNull($created);
        $this->assertDatabaseMissing('account_access', ['user_id' => $created->id]);
    }
}
