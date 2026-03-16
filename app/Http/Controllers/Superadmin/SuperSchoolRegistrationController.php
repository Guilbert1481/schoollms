<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Pricing;

class SuperSchoolRegistrationController extends Controller
{
    /**
     * Display a listing of all registered schools and freelancers.
     */
   public function index()
{
    $schools = School::with('modules')
        ->withCount('modules')
        ->get();

    return view('superadmin.schools.index', compact('schools'));
}

    /**
     * Show the form for the Superadmin to manually add a school.
     */
    public function create()
    {
        return view('superadmin.schools.create');
    }

    /**
     * Handle manual registration from the Superadmin dashboard.
     * Automatically assigns the 'basic' plan and 1-month trial.
     */
    public function register(Request $request)
    {
        $request->validate([
            'school_name' => 'required|string|max:255',
            'type'        => 'required|in:school,freelance',
            'admin_name'  => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|min:8|confirmed',
        ]);

        try {
            DB::beginTransaction();

            // Create the Entity with subscription defaults
            $school = School::create([
                'name'            => $request->school_name,
                'slug'            => Str::slug($request->school_name) . '-' . rand(1000, 9999),
                'type'            => $request->type, 
                'is_active'       => true,
                'plan_name'       => 'basic', 
                'plan_expires_at' => now()->addMonth(), // Default 1-month trial
            ]);

            // Create the Primary Admin account
            User::create([
                'school_id' => $school->id,
                'name'      => $request->admin_name,
                'email'     => $request->email,
                'password'  => Hash::make($request->password),
                'role'      => 'admin', 
            ]);

            $this->initializeModules($school);

            DB::commit();

            return redirect()->route('superadmin.schools.index')
                ->with('success', 'Institution added successfully with a 1-month trial.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to add institution: ' . $e->getMessage()]);
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
                        'module_id' => $moduleIds[$moduleName]
                    ],
                    [
                        'is_enabled' => true,
                        'updated_at' => now(),
                        'created_at' => now()
                    ]
                );
            }
        }
    }

    /**
     * Toggle the active status of a school for manual deactivation.
     */
    public function toggleStatus(School $school)
    {
        $school->is_active = !$school->is_active;
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
        // Updated validation to include the plan change
        $request->validate([
            'name' => 'required|string|max:255',
            'pricing_id' => 'nullable|exists:pricings,id',
            'contact_person' => 'nullable|string|max:255',
        ]);

        // If a new plan was selected from the dropdown
        if ($request->has('pricing_id')) {
            $plan = Pricing::find($request->pricing_id);
            // Update the school's plan name based on the pricing table
            $school->plan_name = $plan->plan_name;
            // You might also want to link the ID if you have a pricing_id column on schools
            // $school->pricing_id = $plan->id; 
        }

        $school->update($request->only(['name', 'contact_person']));

        return redirect()->route('superadmin.schools.show', $school->id)
                         ->with('success', 'School updated successfully!');
    }

    /**
     * Display details for a specific school.
     */
    public function show($id)
    {
        $school = School::with(['users' => function($q) {
            $q->where('role', 'admin');
        }])->findOrFail($id);

        return view('superadmin.schools.show', compact('school'));
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