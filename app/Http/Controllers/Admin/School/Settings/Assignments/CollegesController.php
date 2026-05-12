<?php

namespace App\Http\Controllers\Admin\School\Settings\Assignments;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\College;

class CollegesController extends Controller
{
    public function indexColleges()
    {
        $schoolId = auth()->user()->school_id;

        $deans = DB::table('users')
            ->join('profiles', 'profiles.user_id', '=', 'users.id')
            ->where('users.school_id', $schoolId)
            ->where('users.role', 'dean')
            ->select(
                'users.id',
                DB::raw("CONCAT(profiles.first_name,' ',profiles.last_name) as name")
            )
            ->orderBy('profiles.last_name')
            ->get();

        $colleges = DB::table('colleges as c')
            ->leftJoin('users as u', 'u.id', '=', 'c.dean_id')
            ->leftJoin('profiles as p', 'p.user_id', '=', 'u.id')
            ->where('c.school_id', $schoolId)
            ->select(
                'c.id',
                'c.code',
                'c.name',
                'c.description',
                'c.dean_id',
                DB::raw("COALESCE(CONCAT(p.first_name,' ',p.last_name),'Unassigned') as dean_name")
            )
            ->orderBy('c.name')
            ->get();

        $tableConfig = config('tables.tables.colleges');

        return view('admin.assignments.index', [
            'tab'          => 'colleges',
            'deans'        => $deans,
            'colleges'     => $colleges,
            'columns'      => $tableConfig['columns'] ?? [],
            'labels'       => $tableConfig['labels'] ?? [],
            'formColumns'  => $tableConfig['form'] ?? [],
            'programs'     => [],
            'offices'      => [],
            'programHeads' => [],
            'officeHeads'  => [],
        ]);
    }

    // Create a new college
    public function createCollege(Request $request)
    {
        $request->validate([
            'code'        => 'required|string|max:50',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        College::create([
            'school_id'   => auth()->user()->school_id,
            'code'        => $request->code,
            'name'        => $request->name,
            'description' => $request->description,
            'is_active'   => true,
        ]);

        return back()->with('success', 'College created successfully.');
    }

    // Update college info (code/name/description)
    public function updateCollegeInfo(Request $request, $id)
    {
        $request->validate([
            'code'        => 'required|string|max:50',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $college = College::where('school_id', auth()->user()->school_id)
            ->findOrFail($id);

        $college->update($request->only('code', 'name', 'description'));

        return back()->with('success', 'College updated successfully.');
    }

    // Delete a college
    public function destroyCollege($id)
    {
        $college = College::where('school_id', auth()->user()->school_id)
            ->findOrFail($id);

        $college->delete();

        return back()->with('success', 'College deleted successfully.');
    }

    // Assign dean to college
    public function storeColleges(Request $request)
    {
        $college = College::where('school_id', auth()->user()->school_id)
            ->findOrFail($request->college_id);

        $college->update(['dean_id' => $request->dean_id ?: null]);

        return back()->with('success', 'Dean assigned successfully.');
    }

    public function updateColleges(Request $request, $id)
    {
        $college = College::where('school_id', auth()->user()->school_id)
            ->findOrFail($id);

        $college->update(['dean_id' => $request->dean_id ?: null]);

        return back()->with('success', 'Dean updated successfully.');
    }
}