<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SchoolController extends Controller
{
    /**
     * Display a listing of all schools with pagination.
     */
    public function index()
    {
        $schools = School::withCount('users')
            ->withCount([
                'modules as active_modules_count' => function ($q) {
                    $q->where('is_enabled', true);
                }
            ])
            ->latest()
            ->paginate(10);

        return view('superadmin.schools.index', compact('schools'));
    }


    /**
     * Show the form for creating a new school.
     */
    public function create()
    {
        return view('superadmin.schools.create');
    }

    /**
     * Store a newly created school in the database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:schools,slug|max:255',
        ]);

        // Auto-generate slug if not provided or ensure it's URL friendly
        $validated['slug'] = Str::slug($validated['slug']);

        School::create($validated);

        return redirect()->route('superadmin.schools.index')
            ->with('success', 'New school partner registered successfully.');
    }

    /**
     * Show the form for editing the school details.
     */
    public function edit(School $school)
    {
        return view('superadmin.schools.edit', compact('school'));
    }

    /**
     * Update the specified school in the database.
     */
    public function update(Request $request, School $school)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:schools,slug,' . $school->id,
        ]);

        $school->update($validated);

        return redirect()->route('superadmin.schools.index')
            ->with('success', 'School details updated.');
    }

    /**
     * Toggle the active status of a school.
     */
    public function toggleStatus(School $school)
    {
        $school->update([
            'is_active' => !$school->is_active
        ]);

        return back()->with('success', 'School status updated successfully.');
    }

    /**
     * Remove the school from the system.
     */
    public function destroy(School $school)
    {
        $school->delete();
        return back()->with('success', 'School partner removed.');
    }
}