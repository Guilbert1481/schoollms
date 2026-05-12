<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class SchoolRegistrationController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register-school');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'entity_name' => 'required|string|max:255',
            'code'        => 'required|string|max:10|unique:schools,code',
            'type'        => 'required|in:school,freelancer',

            'first_name'  => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name'   => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|min:8|confirmed',
        ]);

        DB::beginTransaction();

        try {

            // Normalize type
            $dbType = $validated['type'] === 'freelancer' ? 'freelance' : 'school';

            // Generate unique slug
            $baseSlug = Str::slug($validated['entity_name']);
            $slug = $baseSlug;
            $counter = 1;

            while (School::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter++;
            }

            // Create School
            $school = School::create([
                'school_name'     => $validated['entity_name'],
                'slug'            => $slug,
                'code'            => strtoupper($validated['code']),
                'type'            => $dbType,
                'is_active'       => 1,
                'plan_name'       => 'basic',
                'plan_expires_at' => now()->addMonth(),
            ]);

            // Create Admin User
            $user = User::create([
                'school_id'   => $school->id,
                'first_name'  => $validated['first_name'],
                'middle_name' => $validated['middle_name'],
                'last_name'   => $validated['last_name'],
                'email'       => $validated['email'],
                'password'    => Hash::make($validated['password']),
                'role'        => 'admin',
            ]);

            // Create Profile
            $profileId = DB::table('profiles')->insertGetId([
                'user_id'      => $user->id,
                'school_id'    => $school->id,
                'profile_type' => 'employee',
                'first_name'   => $validated['first_name'],
                'middle_name'  => $validated['middle_name'],
                'last_name'    => $validated['last_name'],
                'status'       => 'active',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            // Get Admin Role ID
            $adminRole = DB::table('roles')
                ->where('school_id', $school->id)
                ->where('name', 'admin')
                ->first();

            // If no admin role yet, create one
            if (!$adminRole) {
                $adminRoleId = DB::table('roles')->insertGetId([
                    'school_id'  => $school->id,
                    'name'       => 'admin',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $adminRoleId = $adminRole->id;
            }

            // Create Account Access
            DB::table('account_access')->insert([
                'user_id'     => $user->id,
                'role_id'     => $adminRoleId,
                'person_id'   => $profileId,
                'start_date'  => now(),
                'assigned_by' => $user->id,
                'remarks'     => 'School Owner Admin Access',
                'is_active'   => 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            DB::commit();

            Auth::login($user);

            // Set Active Session
            session([
                'active_profile_id' => $profileId,
                'active_role_id'    => $adminRoleId
            ]);

            return redirect()->route('dashboard')
                ->with('success', 'Welcome! Your 1-month free trial has started.');

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}