<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SchoolModule;
use App\Models\Module;

class DashboardController extends Controller
{
    /**
     * Display the Admin Dashboard.
     * This now loads modules enabled for the logged-in school.
     */
    public function index()
    {
        $schoolId = auth()->user()->school_id;

        // Get enabled module IDs for this school
        $enabledModuleIds = SchoolModule::where('school_id', $schoolId)
            ->where('is_enabled', true)
            ->pluck('module_id');

        // Get module names from the modules table
        $moduleNames = Module::whereIn('id', $enabledModuleIds)
            ->pluck('name')
            ->toArray();

        return view('admin.dashboard', compact('moduleNames'));
    }
}
