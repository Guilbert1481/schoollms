<?php

namespace Tests\Feature;

use App\Models\College;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The Assign Dean dropdown (Admin > Assignment Management > College) must list
 * every dean of the admin's school. Regression for the 2026-07-27 report where
 * a dean without a profiles row silently vanished from the dropdown: the query
 * inner-joined profiles to build the display name, so profile-less deans were
 * dropped and an assigned profile-less dean rendered as "Unassigned".
 */
class CollegeDeanDropdownTest extends TestCase
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

    private function makeDean(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'school_id' => $this->school->id,
            'role' => 'dean',
        ], $overrides));
    }

    private function addProfile(User $user, string $firstName, string $lastName): void
    {
        DB::table('profiles')->insert([
            'user_id' => $user->id,
            'school_id' => $user->school_id,
            'profile_type' => 'employee',
            'first_name' => $firstName,
            'last_name' => $lastName,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function dropdownDeans(): \Illuminate\Support\Collection
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.assignments.indexColleges'));

        $response->assertOk();

        return collect($response->viewData('deans'));
    }

    public function test_dean_without_a_profiles_row_still_appears(): void
    {
        $withProfile = $this->makeDean();
        $this->addProfile($withProfile, 'Jeri', 'Mei');

        $withoutProfile = $this->makeDean([
            'first_name' => 'Ramon',
            'last_name' => 'Villar',
        ]);

        $deans = $this->dropdownDeans();

        $this->assertEqualsCanonicalizing(
            [$withProfile->id, $withoutProfile->id],
            $deans->pluck('id')->all()
        );
        $this->assertContains('Ramon Villar', $deans->pluck('name')->all());
    }

    public function test_profile_name_is_preferred_over_account_name(): void
    {
        $dean = $this->makeDean([
            'first_name' => 'Account',
            'last_name' => 'Name',
        ]);
        $this->addProfile($dean, 'Jeri', 'Mei');

        $this->assertSame('Jeri Mei', $this->dropdownDeans()->firstWhere('id', $dean->id)->name);
    }

    public function test_other_schools_and_variant_roles_are_excluded(): void
    {
        $ours = $this->makeDean();

        $otherSchool = School::factory()->create();
        User::factory()->create(['school_id' => $otherSchool->id, 'role' => 'dean']);
        $this->makeDean(['role' => 'college_dean']);

        $this->assertSame([$ours->id], $this->dropdownDeans()->pluck('id')->all());
    }

    public function test_assigned_profile_less_dean_is_not_listed_as_unassigned(): void
    {
        $dean = $this->makeDean([
            'first_name' => 'Ramon',
            'last_name' => 'Villar',
        ]);
        College::create([
            'school_id' => $this->school->id,
            'code' => 'BSN',
            'name' => 'College of Nursing',
            'is_active' => true,
            'dean_id' => $dean->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.assignments.indexColleges'));

        $response->assertOk();

        $college = collect($response->viewData('colleges'))->firstWhere('code', 'BSN');
        $this->assertSame('Ramon Villar', $college->dean_name);
    }
}
