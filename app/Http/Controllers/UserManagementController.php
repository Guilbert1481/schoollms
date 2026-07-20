<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolRole;
use App\Models\Trainee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    /**
     * account_access is staff-only (teachers, executives, other school
     * workers). These roles must never receive a row — they have no
     * profiles record, so person_id (NOT NULL) has no source anyway.
     */
    private const NON_STAFF_ROLES = ['student', 'parent', 'alumni'];

    /**
     * Store a new role (for modal role creation)
     */
    public function storeRole(Request $request)
    {
        // Support both the legacy single-role payload (role_name, is_head_role)
        // and the new multi-role payload (roles[][role_name], roles[][is_head_role]).
        $payload = $request->input('roles');

        if (! is_array($payload) || empty($payload)) {
            $payload = [[
                'role_name' => $request->input('role_name'),
                'is_head_role' => $request->input('is_head_role'),
                'badge_color' => $request->input('badge_color'),
                'badge_text_color' => $request->input('badge_text_color'),
            ]];
        }

        $request->merge(['roles' => $payload]);

        $request->validate([
            'roles' => 'required|array|min:1',
            'roles.*.role_name' => 'required|string|max:255|distinct',
            'roles.*.is_head_role' => 'required|in:0,1',
            'roles.*.badge_color' => 'nullable|string|max:80',
            'roles.*.badge_text_color' => 'nullable|string|max:80',
        ]);

        $schoolId = auth()->user()->school_id;
        $enabledKeys = $this->enabledRoleKeys();
        $created = 0;

        foreach ($payload as $row) {
            $name = trim((string) $row['role_name']);
            if ($name === '') {
                continue;
            }

            $normalized = $this->normalizeRoleName($name);

            // Roles are governed by the school's subscription (set by the
            // platform admin). Reject anything outside it — the Add Role button
            // is already hidden in the UI; this backstops a crafted POST.
            if (! in_array($normalized, $enabledKeys, true)) {
                return back()->withErrors([
                    'roles' => 'Roles are managed by the platform administrator and cannot be created here.',
                ]);
            }

            $role = \App\Models\Role::query()->firstOrNew([
                'school_id' => $schoolId,
                'name' => $normalized,
            ]);

            if (! $role->exists) {
                $role->is_head_role = (int) $row['is_head_role'];
                $role->badge_color = $row['badge_color'] ?? 'bg-gray-100';
                $role->badge_text_color = $row['badge_text_color'] ?? 'text-gray-700';
                $role->save();
                $created++;
            }
        }

        return redirect()->back()->with('success', $created === 1
            ? 'Role created successfully.'
            : "{$created} role(s) created successfully.");
    }

    public function index()
    {
        $schoolId = auth()->user()->school_id;

        // Only the roles this school is subscribed to (superadmin-controlled)
        // are exposed on the Roles tab and in the create/edit-user dropdown.
        $enabledKeys = $this->enabledRoleKeys();

        $users = DB::table('users as u')
            ->leftJoin('account_access as aa', function ($join) {
                $join->on('aa.user_id', '=', 'u.id')
                    ->where('aa.is_active', 1);
            })
            ->leftJoin('roles as r', 'r.id', '=', 'aa.role_id')
            ->where('u.school_id', $schoolId)
            ->select(
                'u.*',
                'r.name as role_name',
                'r.badge_color',
                'r.badge_text_color'
            )
            ->get();

        $roles = DB::table('roles')
            ->where('school_id', $schoolId)
            ->whereNotIn('name', ['system_owner'])
            ->whereIn('name', $enabledKeys)
            ->orderBy('name')
            ->get();

        $roleRows = $roles->map(function ($role) use ($users) {
            $role->display_name = Str::headline((string) $role->name);
            $role->type_label = ! empty($role->is_head_role)
                ? '<span class="inline-flex rounded-full bg-indigo-100 px-2 py-0.5 text-[11px] font-bold text-indigo-700">Head</span>'
                : '<span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600">Regular</span>';
            $role->users_count = $users->where('role_name', $role->name)->count();
            $role->badge_preview = '<span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold '
                .e($role->badge_color ?: 'bg-gray-100').' '
                .e($role->badge_text_color ?: 'text-gray-700').'">'
                .e(Str::headline((string) $role->name)).'</span>';

            return $role;
        });

        $roleColumns = [
            ['key' => 'display_name', 'label' => 'Role', 'width' => '220px'],
            ['key' => 'name', 'label' => 'System Name', 'width' => '180px'],
            ['key' => 'type_label', 'label' => 'Type', 'width' => '110px', 'raw' => true],
            ['key' => 'users_count', 'label' => 'Users', 'width' => '90px'],
            ['key' => 'badge_preview', 'label' => 'Badge', 'width' => '180px', 'raw' => true],
        ];

        $roleActions = [
            [
                'type' => 'modal',
                'label' => 'Edit',
                'modal' => 'roleEditModal',
                'handler' => 'openRoleEditModal({id})',
                'class' => 'bg-indigo-600 text-white hover:bg-indigo-700',
            ],
            [
                'type' => 'delete',
                'label' => 'Delete',
                'class' => 'bg-rose-600 text-white hover:bg-rose-700',
                'confirm' => 'Are you sure you want to delete this role? This action cannot be undone.',
            ],
        ];

        $roleEditRecords = $roleRows->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'is_head_role' => (bool) ($role->is_head_role ?? false),
                'badge_color' => $role->badge_color ?: 'bg-gray-100',
                'badge_text_color' => $role->badge_text_color ?: 'text-gray-700',
            ];
        })->values();

        return view('admin.settings.users.index', compact(
            'users',
            'roles',
            'roleRows',
            'roleColumns',
            'roleActions',
            'roleEditRecords',
        ));
    }

    public function updateRole(Request $request, $roleId)
    {
        // Look the role up scoped to the current school instead of relying on
        // implicit route-model binding, so a stale page / already-deleted role
        // gives a friendly message instead of a raw 404.
        $role = Role::query()
            ->where('id', $roleId)
            ->where('school_id', auth()->user()->school_id)
            ->first();

        if (! $role) {
            return redirect()
                ->route('settings.users.index')
                ->with('success', 'That role no longer exists — it may have already been removed.');
        }

        $data = $request->validate([
            'role_name' => 'required|string|max:255',
            'is_head_role' => 'required|in:0,1',
            'badge_color' => 'nullable|string|max:80',
            'badge_text_color' => 'nullable|string|max:80',
        ]);

        $name = $this->normalizeRoleName($data['role_name']);
        $exists = Role::query()
            ->where('school_id', $role->school_id)
            ->where('name', $name)
            ->where('id', '!=', $role->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['role_name' => 'A role with this name already exists.']);
        }

        $oldName = $role->name;
        $role->fill([
            'name' => $name,
            'is_head_role' => (int) $data['is_head_role'],
            'badge_color' => $data['badge_color'] ?? null,
            'badge_text_color' => $data['badge_text_color'] ?? null,
        ])->save();

        if ($oldName !== $name) {
            DB::table('users')
                ->where('school_id', $role->school_id)
                ->where('role', $oldName)
                ->update(['role' => $name, 'updated_at' => now()]);

            DB::table('account_access')
                ->where('role_id', $role->id)
                ->update([
                    'role_snapshot' => Str::headline($name),
                    'updated_at' => now(),
                ]);
        }

        return back()->with('success', 'Role updated successfully.');
    }

    public function destroyRole($roleId)
    {
        // Scoped lookup (not implicit binding) so deleting an already-removed
        // role from a stale page redirects with a message instead of a 404.
        $role = Role::query()
            ->where('id', $roleId)
            ->where('school_id', auth()->user()->school_id)
            ->first();

        if (! $role) {
            return redirect()
                ->route('settings.users.index')
                ->with('success', 'That role no longer exists — it may have already been removed.');
        }

        $assignedUsers = DB::table('account_access')
            ->where('role_id', $role->id)
            ->where('is_active', 1)
            ->count();

        if ($assignedUsers > 0) {
            return redirect()
                ->route('settings.users.index')
                ->withErrors(['role' => 'This role is assigned to active users and cannot be deleted.']);
        }

        $role->delete();

        return redirect()
            ->route('settings.users.index')
            ->with('success', 'Role deleted successfully.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => ['required', 'string', Rule::in($this->enabledRoleKeys())],
        ], [
            'role.in' => 'That role is not enabled for your school.',
        ]);

        DB::beginTransaction();

        try {

            $schoolId = auth()->user()->school_id;

            // 1. Create User
            $user = User::create([
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'role' => $request->role,
                'school_id' => $schoolId,
            ]);

            // 2. Create Profile
            $profileId = DB::table('profiles')->insertGetId([
                'user_id' => $user->id,
                'school_id' => $schoolId,
                'profile_type' => 'employee',
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name,
                'last_name' => $request->last_name,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // ✅ 3. CREATE TRAINEE (NEW - SAFE ADD)
            if (strtolower($request->role) === 'trainee') {
                Trainee::create([
                    'profile_id' => $profileId,
                    'status' => 'active',
                ]);
            }

            // 4. Get or create Role
            $role = DB::table('roles')
                ->where('school_id', $schoolId)
                ->where('name', $request->role)
                ->first();

            if (! $role) {
                $roleId = DB::table('roles')->insertGetId([
                    'school_id' => $schoolId,
                    'name' => $request->role,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $roleId = $role->id;
            }

            // 5. Create Account Access (staff only — never students/parents/alumni)
            if (! in_array(strtolower($request->role), self::NON_STAFF_ROLES, true)) {
                DB::table('account_access')->insert([
                    'user_id' => $user->id,
                    'role_id' => $roleId,
                    'office_id' => null,
                    'person_id' => $profileId,
                    'role_snapshot' => ucfirst($request->role),
                    'start_date' => now(),
                    'assigned_by' => auth()->id(),
                    'remarks' => 'Initial account holder',
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            return back()->with('success', 'User created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            dd('Database Error: '.$e->getMessage());
        }
    }

    public function show(User $user)
    {
        return view('admin.settings.users.show', compact('user'));
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $schoolId = auth()->user()->school_id;

        $roles = Role::where('school_id', $schoolId)
            ->whereNotIn('name', ['system_owner'])
            ->whereIn('name', $this->enabledRoleKeys())
            ->get();

        return view('admin.settings.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'role' => ['required', 'string', Rule::in($this->enabledRoleKeys())],
            'phone' => 'nullable|string|max:20',
            'birthday' => 'nullable|date',
            'password' => 'nullable|min:6',
        ], [
            'role.in' => 'That role is not enabled for your school.',
        ]);

        if ($request->filled('password')) {
            $validated['password'] = bcrypt($request->password);
        } else {
            unset($validated['password']);
        }

        DB::beginTransaction();
        try {
            $user->update($validated);

            $schoolId = $user->school_id ?? auth()->user()->school_id;

            // Keep profiles row in sync (names).
            DB::table('profiles')
                ->where('user_id', $user->id)
                ->update([
                    'first_name' => $validated['first_name'],
                    'middle_name' => $validated['middle_name'] ?? null,
                    'last_name' => $validated['last_name'],
                    'updated_at' => now(),
                ]);

            if (in_array(strtolower($validated['role']), self::NON_STAFF_ROLES, true)) {
                // Non-staff role: retire any stray staff access instead of
                // syncing one (e.g. a teacher account converted to student).
                // strtolower() mirrors store() — role keys can arrive capitalized
                // ('Student'), and NON_STAFF_ROLES is lowercase, so a case-sensitive
                // compare would misroute a student into the staff-insert branch below
                // and crash on account_access.person_id (NOT NULL).
                DB::table('account_access')
                    ->where('user_id', $user->id)
                    ->where('is_active', 1)
                    ->update([
                        'is_active' => 0,
                        'end_date' => now(),
                        'updated_at' => now(),
                    ]);
            } else {
                // Resolve (or create) the role row for this school.
                $role = DB::table('roles')
                    ->where('school_id', $schoolId)
                    ->where('name', $validated['role'])
                    ->first();

                $roleId = $role->id ?? DB::table('roles')->insertGetId([
                    'school_id' => $schoolId,
                    'name' => $validated['role'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Upsert account_access so the User Management list shows the role.
                $access = DB::table('account_access')
                    ->where('user_id', $user->id)
                    ->where('is_active', 1)
                    ->first();

                if ($access) {
                    DB::table('account_access')
                        ->where('id', $access->id)
                        ->update([
                            'role_id' => $roleId,
                            'role_snapshot' => ucfirst(str_replace('_', ' ', $validated['role'])),
                            'updated_at' => now(),
                        ]);
                } else {
                    // Staff role with no active access row yet — create one, but only
                    // when a profiles record exists to source person_id (NOT NULL). A
                    // missing profile means the account was never fully provisioned;
                    // skip rather than crash on the constraint.
                    $profileId = DB::table('profiles')->where('user_id', $user->id)->value('id');

                    if ($profileId !== null) {
                        DB::table('account_access')->insert([
                            'user_id' => $user->id,
                            'role_id' => $roleId,
                            'office_id' => null,
                            'person_id' => $profileId,
                            'role_snapshot' => ucfirst(str_replace('_', ' ', $validated['role'])),
                            'start_date' => now(),
                            'assigned_by' => auth()->id(),
                            'remarks' => 'Set via User Management edit',
                            'is_active' => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('settings.users.index')
            ->with('success', 'User updated successfully!');
    }

    public function delete(User $user)
    {
        DB::beginTransaction();

        try {

            DB::table('account_access')
                ->where('user_id', $user->id)
                ->delete();

            DB::table('profiles')
                ->where('user_id', $user->id)
                ->delete();

            $user->delete();

            DB::commit();

            return back()->with('success', 'User deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', $e->getMessage());
        }
    }

    public function resetPassword(User $user)
    {
        $temporaryPassword = 'Temp1234';

        $user->password = bcrypt($temporaryPassword);
        $user->save();

        return back()->with('success', 'Temporary password: '.$temporaryPassword);
    }

    protected function normalizeRoleName(string $name): string
    {
        return str_replace('-', '_', Str::slug($name, '_'));
    }

    /**
     * The role keys the current user's school is subscribed to. Legacy schools
     * with no subscription rows fall back to the full catalog so nothing breaks.
     *
     * @return list<string>
     */
    protected function enabledRoleKeys(): array
    {
        $schoolId = auth()->user()?->school_id;

        $school = $schoolId ? School::find($schoolId) : null;

        return $school ? $school->enabledRoleKeys() : SchoolRole::catalogKeys();
    }

    protected function authorizeRoleSchool(Role $role): void
    {
        abort_unless((int) $role->school_id === (int) auth()->user()->school_id, 403);
    }
}
