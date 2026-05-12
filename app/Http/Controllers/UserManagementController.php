<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Profile;
use App\Models\Role;
use App\Models\Trainee;

class UserManagementController extends Controller
{
    /**
     * Store a new role (for modal role creation)
     */
    public function storeRole(Request $request)
    {
        $request->validate([
            'role_name' => 'required|string|max:255|unique:roles,name',
            'is_head_role' => 'required|in:0,1',
        ]);

        $schoolId = auth()->user()->school_id;
        $role = new \App\Models\Role();
        $role->school_id = $schoolId;
        $role->name = $request->role_name;
        $role->is_head_role = $request->is_head_role ? 1 : 0;
        $role->save();

        return redirect()->back()->with('success', 'Role created successfully.');
    }
    public function index()
    {
        $schoolId = auth()->user()->school_id;

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
            ->orderBy('name')
            ->get();

        return view('admin.settings.users.index', compact('users', 'roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name'  => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name'   => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|min:6',
            'role'        => 'required|string',
        ]);

        DB::beginTransaction();

        try {

            $schoolId = auth()->user()->school_id;

            // 1. Create User
            $user = User::create([
                'first_name' => $request->first_name,
                'middle_name'=> $request->middle_name,
                'last_name'  => $request->last_name,
                'email'      => $request->email,
                'password'   => bcrypt($request->password),
                'role'       => $request->role,
                'school_id'  => $schoolId,
            ]);

            // 2. Create Profile
            $profileId = DB::table('profiles')->insertGetId([
                'user_id'      => $user->id,
                'school_id'    => $schoolId,
                'profile_type' => 'employee',
                'first_name'   => $request->first_name,
                'middle_name'  => $request->middle_name,
                'last_name'    => $request->last_name,
                'status'       => 'active',
                'created_at'   => now(),
                'updated_at'   => now()
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

            if (!$role) {
                $roleId = DB::table('roles')->insertGetId([
                    'school_id' => $schoolId,
                    'name' => $request->role,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } else {
                $roleId = $role->id;
            }

            // 5. Create Account Access
            DB::table('account_access')->insert([
                'user_id'       => $user->id,
                'role_id'       => $roleId,
                'office_id'     => null,
                'person_id'     => $profileId,
                'role_snapshot' => ucfirst($request->role),
                'start_date'    => now(),
                'assigned_by'   => auth()->id(),
                'remarks'       => 'Initial account holder',
                'is_active'     => 1,
                'created_at'    => now(),
                'updated_at'    => now()
            ]);

            DB::commit();

            return back()->with('success', 'User created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            dd("Database Error: " . $e->getMessage());
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
            ->get();

        return view('admin.settings.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|string',
            'phone' => 'nullable|string|max:20',
            'birthday' => 'nullable|date',
            'password' => 'nullable|min:6',
        ]);

        if ($request->filled('password')) {
            $validated['password'] = bcrypt($request->password);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

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

        return back()->with('success', 'Temporary password: ' . $temporaryPassword);
    }
}