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
            // School
            'entity_name' => 'required|string|max:255',
            'code'        => 'required|string|max:10|unique:schools,code',
            'type'        => 'required|in:school,freelancer',

            // User
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

            // Create School WITH CODE
            $school = School::create([
                'school_name'     => $validated['entity_name'],
                'slug'            => $slug,
                'code'            => strtoupper($validated['code']),
                'type'            => $dbType,
                'is_active'       => 1,
                'plan_name'       => 'basic',
                'plan_expires_at' => now()->addMonth(),
            ]);

            // Create Admin User with full name fields
            $user = User::create([
                'school_id'   => $school->id,
                'first_name'  => $validated['first_name'],
                'middle_name' => $validated['middle_name'],
                'last_name'   => $validated['last_name'],
                'email'       => $validated['email'],
                'password'    => Hash::make($validated['password']),
                'role'        => 'admin',
            ]);

            DB::commit();

            Auth::login($user);

            return redirect()->route('dashboard')
                ->with('success', 'Welcome! Your 1-month free trial has started.');

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}