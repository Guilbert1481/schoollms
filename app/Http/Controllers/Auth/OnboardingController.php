<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * --- ONBOARDING CONTROLLER ---
 * PURPOSE: This controller acts as a "Guard" and "Wizard" for new users.
 * * ROLE IN ARCHITECTURE: 
 * In a multi-tenant system, every piece of data (grades, students, etc.) MUST 
 * be attached to a school_id. If a user (like a Freelancer) registers without 
 * a school, they are "homeless." This controller creates their "home" (The School Record) 
 * so the rest of the LMS features can function.
 */
class OnboardingController extends Controller
{
    /**
     * Display the Setup Wizard.
     * Logic: If the user already has a school_id, they don't need to be here.
     * We redirect them to their dashboard to prevent them from creating multiple schools.
     */
    public function showWizard()
    {
        if (auth()->user()->school_id) {
            return redirect()->route('admin.dashboard');
        }
        return view('auth.onboarding-wizard');
    }

    /**
     * Initialize the Multi-Tenant Environment.
     * Logic: 
     * 1. Validates the Professional/School name.
     * 2. Uses a DB Transaction to ensure both the School and User update succeed or fail together.
     * 3. Creates the 'Box' (School) and links the User as the 'Admin' of that box.
     */
    public function setupEntity(Request $request)
    {
        $request->validate([
            'entity_name' => 'required|string|min:3|max:255',
        ]);

        return DB::transaction(function () use ($request) {
            $user = auth()->user();

            // STEP 1: Create the School/Professional Entity
            // This is the 'Tenant' record that isolates this user's data from others.
            $school = School::create([
                'name' => $request->entity_name,
                'slug' => Str::slug($request->entity_name) . '-' . rand(1000, 9999),
                'is_active' => true,
            ]);

            // STEP 2: Associate the User with the New School
            // We update the user's school_id so that all future database queries 
            // know exactly which 'box' this user belongs to.
            $user->update([
                'school_id' => $school->id,
                'role' => 'admin' // Granting them ownership rights over this school
            ]);

            return redirect()->route('admin.dashboard')
                             ->with('success', 'Welcome! Your digital campus is now active.');
        });
    }
}