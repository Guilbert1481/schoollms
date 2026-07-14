<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Pricing;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SuperSchoolRegistrationController extends Controller
{
    /**
     * Display a listing of all registered schools and freelancers.
     */
    public function index()
    {
        $schools = School::with('modules')
            ->withCount('modules')
            ->orderByDesc('id')
            ->get();

        // decorate rows for the shared table component (plain-text labels)
        $schools->each(function ($s) {
            $s->plan_name = $s->plan_name ?: 'Basic';
            $s->status_label = $s->is_active ? 'Active' : 'Inactive';
            $s->type_label = ucfirst($s->type);
        });

        // schoolId => [enabled role keys], consumed by the Edit modal checklist.
        $enabledRolesBySchool = $schools->mapWithKeys(
            fn ($s) => [$s->id => $s->enabledRoleKeys()],
        );

        $config = config('tables.superadmin_schools');
        $columns = $config['columns'] ?? [];
        $labels = $config['labels'] ?? [];
        $formColumns = $config['form'] ?? [];
        $fieldTypes = $config['types'] ?? [];

        return view('superadmin.schools.index', [
            'schools' => $schools,
            'data' => $schools,
            'columns' => $columns,
            'labels' => $labels,
            'formColumns' => $formColumns,
            'fieldTypes' => $fieldTypes,
            'roleCatalog' => SchoolRole::groupedCatalog(),
            'roleDefaults' => SchoolRole::defaultKeys(),
            'enabledRolesBySchool' => $enabledRolesBySchool,
        ]);
    }

    /**
     * The create form lives inside a modal on the index page now.
     */
    public function create()
    {
        return redirect()->route('superadmin.schools.index');
    }

    /**
     * Handle manual registration from the Superadmin dashboard.
     * Automatically assigns the 'basic' plan and 1-month trial.
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'school_name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'domain' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:100|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/|unique:schools,slug',
            'country' => 'nullable|string|max:100',
            'type' => 'required|in:school,freelance',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'roles' => 'nullable|array',
            'roles.*' => ['string', Rule::in(SchoolRole::catalogKeys())],
        ], [
            'slug.regex' => 'The slug may only contain lowercase letters, numbers, and dashes (e.g. "memory-ridge").',
            'slug.unique' => 'That slug is already taken by another school.',
            'roles.*.in' => 'One of the selected roles is not a recognised role.',
        ]);

        try {
            DB::beginTransaction();

            $slug = ! empty($data['slug'])
                ? $data['slug']
                : Str::slug($data['school_name']).'-'.rand(1000, 9999);

            $school = School::create([
                'school_name' => $data['school_name'],
                'code' => $data['code'] ?? null,
                'domain' => $data['domain'] ?? null,
                'slug' => $slug,
                'type' => $data['type'],
                'country' => $data['country'] ?? null,
                'is_active' => true,
                'plan_name' => 'basic',
                'plan_expires_at' => now()->addMonth(),
            ]);

            User::create([
                'school_id' => $school->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'admin',
            ]);

            DB::table('school_profiles')->insert([
                'school_id' => $school->id,
                'school_name' => $data['school_name'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->initializeModules($school);

            // Persist the subscribed roles. Always include 'admin' — the primary
            // admin account above is created with that role.
            $roleKeys = array_values(array_unique(array_merge(['admin'], $data['roles'] ?? [])));
            $this->syncRoleSubscriptions($school, $roleKeys);

            DB::commit();

            return redirect()->route('superadmin.schools.index')
                ->with('success', 'Institution added successfully with a 1-month trial.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->withErrors(['error' => 'Failed to add institution: '.$e->getMessage()]);
        }
    }

    /**
     * Initialize default modules based on institution type.
     */
    protected function initializeModules($school)
    {
        $defaultModules = ($school->type === 'freelance')
            ? ['academics', 'attendance', 'finance', 'billing']
            : ['admissions', 'registrar', 'hr', 'accounting', 'library', 'transport'];

        $moduleIds = \App\Models\Module::whereIn('name', $defaultModules)->pluck('id', 'name');

        foreach ($defaultModules as $moduleName) {
            if (isset($moduleIds[$moduleName])) {
                DB::table('school_modules')->updateOrInsert(
                    [
                        'school_id' => $school->id,
                        'module_id' => $moduleIds[$moduleName],
                    ],
                    [
                        'is_enabled' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }

    /**
     * Set a school's subscribed roles from the Add/Edit Partner checklist.
     *
     * Mirrors ModuleController::update — disable everything, then (re)enable the
     * selected keys. Subscription rows are kept (not deleted) so history/badges
     * survive a later change. For each enabled role we also ensure a matching
     * `roles` row exists, so the school admin's User Management dropdown and
     * Roles tab have something to show for a brand-new school.
     *
     * @param  list<string>  $keys
     */
    protected function syncRoleSubscriptions(School $school, array $keys): void
    {
        $valid = array_values(array_intersect($keys, SchoolRole::catalogKeys()));

        SchoolRole::where('school_id', $school->id)->update(['is_enabled' => false]);

        foreach ($valid as $key) {
            SchoolRole::updateOrCreate(
                ['school_id' => $school->id, 'role_key' => $key],
                ['is_enabled' => true],
            );

            // Materialise the assignable role row (badge/head metadata) if the
            // school does not have one yet. Never overwrite an existing row.
            Role::firstOrCreate(
                ['school_id' => $school->id, 'name' => $key],
                ['is_head_role' => 0],
            );
        }
    }

    /**
     * Toggle the active status of a school for manual deactivation.
     */
    public function toggleStatus(School $school)
    {
        $school->is_active = ! $school->is_active;
        $school->save();

        $status = $school->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Institution #{$school->id} has been {$status}.");
    }

    /**
     * Show the edit form for a specific school.
     */
    public function edit(School $school)
    {
        // MERGED: Only one edit function is allowed.
        // Fetch all available plans from the database for the dropdown
        $plans = Pricing::all();

        return view('superadmin.schools.edit', compact('school', 'plans'));
    }

    /**
     * Update school details.
     */
    public function update(Request $request, School $school)
    {
        $data = $request->validate([
            'school_name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'domain' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:100|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/|unique:schools,slug,'.$school->id,
            'country' => 'nullable|string|max:100',
            'type' => 'required|in:school,freelance',
            'plan_name' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
            'roles' => 'nullable|array',
            'roles.*' => ['string', Rule::in(SchoolRole::catalogKeys())],
        ], [
            'slug.regex' => 'The slug may only contain lowercase letters, numbers, and dashes.',
            'slug.unique' => 'That slug is already taken by another school.',
            'roles.*.in' => 'One of the selected roles is not a recognised role.',
        ]);

        // Role keys are not a schools column — pull them out before mass-assign.
        $roleKeys = array_values(array_unique(array_merge(['admin'], $data['roles'] ?? [])));
        unset($data['roles']);

        $data['is_active'] = $request->boolean('is_active');

        $school->update($data);

        $this->syncRoleSubscriptions($school, $roleKeys);

        // keep school_profiles.school_name in sync
        DB::table('school_profiles')
            ->where('school_id', $school->id)
            ->update([
                'school_name' => $data['school_name'],
                'updated_at' => now(),
            ]);

        return redirect()->route('superadmin.schools.index')
            ->with('success', 'Institution updated successfully.');
    }

    /**
     * Display details for a specific school.
     */
    public function show($id)
    {
        $school = School::with(['users' => function ($q) {
            $q->where('role', 'admin');
        }])->findOrFail($id);

        $profile = \App\Models\SchoolProfile::firstOrCreate(
            ['school_id' => $school->id],
            ['school_name' => $school->school_name]
        );

        $logoUrl = null;
        if ($profile && $profile->school_logo) {
            $path = $profile->school_logo;
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:') || str_starts_with($path, '/')) {
                $logoUrl = $path;
            } else {
                $logoUrl = \Illuminate\Support\Facades\Storage::url($path);
            }
        }

        return view('superadmin.schools.show', compact('school', 'profile', 'logoUrl'));
    }

    public function destroy($id)
    {
        $school = School::findOrFail($id);

        // This will also remove the links in school_modules table
        // if your database is set up with 'onDelete cascade'
        $school->delete();

        return redirect()->route('superadmin.schools.index')
            ->with('success', 'Institution removed successfully.');
    }
}
