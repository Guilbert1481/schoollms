<?php

namespace Tests\Feature;

use App\Mail\ParentPortalCredentialsMail;
use App\Models\ParentStudentLink;
use App\Models\RegistrarSetting;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Parent portal access control: the dashboard shows only THIS parent's
 * children (active link + active approved school), foreign children 404
 * (IDOR), disabled links and deactivated schools hide children, the
 * one-time-password gate forces a password change, the slug login page
 * accepts cross-school parents with a child at that school, and the
 * registrar panel manages links only within its own school.
 */
class ParentPortalAccessTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $parentA;
    private User $parentB;
    private int $childA1;
    private int $childA2;
    private int $childB1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();

        $this->parentA = User::factory()->create(['school_id' => null, 'role' => 'parent', 'email' => 'a@parents.test']);
        $this->parentB = User::factory()->create(['school_id' => null, 'role' => 'parent', 'email' => 'b@parents.test']);

        $this->childA1 = $this->student('Alice', 'PA-001');
        $this->childA2 = $this->student('Andy', 'PA-002');
        $this->childB1 = $this->student('Bella', 'PB-001');

        $this->link($this->parentA, $this->childA1);
        $this->link($this->parentA, $this->childA2);
        $this->link($this->parentB, $this->childB1);
    }

    /** Insert a row, auto-filling NOT NULL columns that have no default. */
    private function insertWithDefaults(string $table, array $values): int
    {
        $cols = DB::select(
            'select column_name, data_type, is_nullable, column_default, extra
             from information_schema.columns
             where table_schema = database() and table_name = ?',
            [$table]
        );
        foreach ($cols as $c) {
            $c = array_change_key_case((array) $c, CASE_LOWER);
            if (array_key_exists($c['column_name'], $values)
                || strtoupper((string) $c['is_nullable']) === 'YES'
                || $c['column_default'] !== null
                || str_contains(strtolower((string) $c['extra']), 'auto_increment')) {
                continue;
            }
            $values[$c['column_name']] = match (true) {
                in_array(strtolower((string) $c['data_type']), ['int', 'bigint', 'smallint', 'tinyint', 'decimal', 'double', 'float']) => 0,
                strtolower((string) $c['data_type']) === 'date' => date('Y-m-d'),
                in_array(strtolower((string) $c['data_type']), ['datetime', 'timestamp']) => date('Y-m-d H:i:s'),
                strtolower((string) $c['data_type']) === 'json' => '[]',
                default => '',
            };
        }

        return (int) DB::table($table)->insertGetId($values);
    }

    private function student(string $firstName, string $number, ?int $schoolId = null): int
    {
        return $this->insertWithDefaults('students', [
            'school_id' => $schoolId ?? $this->school->id,
            'first_name' => $firstName, 'last_name' => 'Portal',
            'student_number' => $number, 'status' => 'active',
        ]);
    }

    private function link(User $parent, int $studentId, string $status = 'active'): ParentStudentLink
    {
        return ParentStudentLink::create([
            'parent_user_id' => $parent->id,
            'student_id'     => $studentId,
            'relationship'   => 'mother',
            'access_status'  => $status,
        ]);
    }

    public function test_dashboard_lists_only_this_parents_children(): void
    {
        $this->actingAs($this->parentA)
            ->get(route('parent.dashboard'))
            ->assertOk()
            ->assertSee('Alice Portal')
            ->assertSee('Andy Portal')
            ->assertDontSee('Bella Portal');
    }

    public function test_selecting_another_familys_child_is_a_404(): void
    {
        $this->actingAs($this->parentA)
            ->post(route('parent.children.select', $this->childB1))
            ->assertNotFound();
    }

    public function test_selecting_a_nonexistent_child_is_the_same_404(): void
    {
        $this->actingAs($this->parentA)
            ->post(route('parent.children.select', 999999))
            ->assertNotFound();
    }

    public function test_disabled_link_hides_the_child(): void
    {
        ParentStudentLink::where('parent_user_id', $this->parentA->id)
            ->where('student_id', $this->childA2)
            ->update(['access_status' => 'disabled']);

        $this->actingAs($this->parentA)
            ->get(route('parent.dashboard'))
            ->assertOk()
            ->assertSee('Alice Portal')
            ->assertDontSee('Andy Portal');

        $this->actingAs($this->parentA)
            ->post(route('parent.children.select', $this->childA2))
            ->assertNotFound();
    }

    public function test_deactivated_school_hides_its_child_but_not_siblings(): void
    {
        $otherSchool = School::factory()->create();
        $childC = $this->student('Cora', 'PC-001', (int) $otherSchool->id);
        $this->link($this->parentA, $childC);

        $otherSchool->update(['is_active' => false]);

        $this->actingAs($this->parentA)
            ->get(route('parent.dashboard'))
            ->assertOk()
            ->assertSee('Alice Portal')
            ->assertDontSee('Cora Portal');
    }

    public function test_non_parent_roles_cannot_reach_parent_routes(): void
    {
        $student = User::factory()->create(['school_id' => $this->school->id, 'role' => 'student']);

        $this->actingAs($student)
            ->get(route('parent.dashboard'))
            ->assertForbidden();
    }

    public function test_one_time_password_gate_forces_password_change_then_releases(): void
    {
        $this->parentA->password_change_required = true;
        $this->parentA->save();

        $this->actingAs($this->parentA)
            ->get(route('parent.dashboard'))
            ->assertRedirect(route('settings.profile', ['tab' => 'security']));

        // The escape hatch itself stays reachable, and changing the password releases the gate.
        $this->actingAs($this->parentA)->get(route('settings.profile'))->assertOk();
        $this->actingAs($this->parentA)
            ->post(route('settings.profile.password'), [
                'current_password'      => 'password', // factory default
                'password'              => 'my-own-secret-1',
                'password_confirmation' => 'my-own-secret-1',
            ])
            ->assertSessionHas('status');

        $this->assertFalse((bool) $this->parentA->fresh()->password_change_required);
        $this->actingAs($this->parentA)->get(route('parent.dashboard'))->assertOk();
    }

    public function test_school_login_page_accepts_parent_with_child_at_that_school(): void
    {
        $response = $this->post(route('school.login.post', ['slug' => $this->school->slug]), [
            'email'    => 'a@parents.test',
            'password' => 'password', // factory default
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->parentA);
    }

    public function test_school_login_page_rejects_parent_with_no_child_there(): void
    {
        $stranger = School::factory()->create();

        $this->post(route('school.login.post', ['slug' => $stranger->slug]), [
            'email'    => 'a@parents.test',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_registrar_panel_toggle_link_and_reissue(): void
    {
        Mail::fake();
        $registrar = User::factory()->create(['school_id' => $this->school->id, 'role' => 'registrar']);
        $link = ParentStudentLink::where('parent_user_id', $this->parentA->id)
            ->where('student_id', $this->childA1)->firstOrFail();

        $this->actingAs($registrar)
            ->get(route('registrar.settings.parent-portal.index'))
            ->assertOk()
            ->assertSee('a@parents.test')
            ->assertSee('Automatic parent account creation');

        $this->actingAs($registrar)
            ->put(route('registrar.settings.parent-portal.update'), [])
            ->assertSessionHas('success');
        $this->assertFalse((bool) RegistrarSetting::forSchool((int) $this->school->id)->parent_portal_auto_provision);

        $this->actingAs($registrar)
            ->post(route('registrar.settings.parent-portal.links.toggle', $link))
            ->assertSessionHas('success');
        $this->assertSame('disabled', $link->fresh()->access_status);

        $this->actingAs($registrar)
            ->post(route('registrar.settings.parent-portal.links.reissue', $link))
            ->assertSessionHas('success');
        $this->assertTrue((bool) $this->parentA->fresh()->password_change_required);
        Mail::assertSent(ParentPortalCredentialsMail::class, fn ($mail) => $mail->hasTo('a@parents.test'));
    }

    public function test_parent_cannot_self_edit_their_email(): void
    {
        // The email is the provisioning trust anchor: a parent squatting an
        // unclaimed guardian email would silently inherit that child at the
        // next gate clear. Self-service email edits must be ignored for parents.
        $this->actingAs($this->parentA)
            ->post(route('settings.profile.update'), [
                'first_name' => 'Anne',
                'last_name'  => 'Parent',
                'email'      => 'victim-guardian@example.test',
            ])
            ->assertSessionHasNoErrors();

        $fresh = $this->parentA->fresh();
        $this->assertSame('a@parents.test', $fresh->email);   // unchanged
        $this->assertSame('Anne', $fresh->first_name);        // other fields still editable
    }

    public function test_registrar_of_another_school_cannot_touch_the_link(): void
    {
        $otherSchool = School::factory()->create();
        $foreignRegistrar = User::factory()->create(['school_id' => $otherSchool->id, 'role' => 'registrar']);
        $link = ParentStudentLink::where('parent_user_id', $this->parentA->id)
            ->where('student_id', $this->childA1)->firstOrFail();

        $this->actingAs($foreignRegistrar)
            ->post(route('registrar.settings.parent-portal.links.toggle', $link))
            ->assertForbidden();
        $this->assertSame('active', $link->fresh()->access_status);
    }
}
