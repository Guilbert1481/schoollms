<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Module;
use App\Models\School;
use App\Models\SchoolModule;

class ModuleController extends Controller
{
    public function index($schoolId)
    {
        $school = School::findOrFail($schoolId);

        // Load all modules
        $modules = Module::all();

        // Group modules by category
        $categories = $modules->groupBy('category');

        // Load enabled modules for this school
        $enabled = SchoolModule::where('school_id', $schoolId)
                    ->where('is_enabled', true)
                    ->pluck('module_id')
                    ->toArray();

        return view('superadmin.schools.modules.index', compact(
            'school',
            'modules',
            'enabled',
            'categories'
        ));
    }


    public function update(Request $request, $schoolId)
    {
        $selected = $request->input('modules', []);

        // Disable all modules for this school
        SchoolModule::where('school_id', $schoolId)->update(['is_enabled' => false]);

        // Enable selected modules
        foreach ($selected as $moduleId) {
            SchoolModule::updateOrCreate(
                ['school_id' => $schoolId, 'module_id' => $moduleId],
                ['is_enabled' => true]
            );
        }

        return back()->with('status', 'Modules updated successfully.');
    }
}
