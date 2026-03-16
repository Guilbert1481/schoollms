<?php

namespace App\Http\Controllers\Superadmin;

use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuperAdminController extends Controller
{
    public function index()
    {
        // Fetch all registered schools/partners
        $schools = School::with('users')->get();
        return view('superadmin.schools.index', compact('schools'));
    }

    public function toggleModule(Request $request, School $school)
    {
        // Toggle the 'is_enabled' status for a specific school and module
        DB::table('school_modules')
            ->where('school_id', $school->id)
            ->where('module_name', $request->module_name)
            ->update(['is_enabled' => $request->status]);

        return response()->json(['success' => true]);
    }
}