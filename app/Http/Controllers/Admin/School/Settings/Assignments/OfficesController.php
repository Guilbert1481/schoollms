<?php

namespace App\Http\Controllers\Admin\School\Settings\Assignments;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Office;
use App\Models\OfficeType;

class OfficesController extends Controller
{
    public function indexOffices()
    {
        $schoolId = auth()->user()->school_id;

        $officeHeads = DB::table('office_heads')
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->select('id', 'name', 'position')
            ->get();

        $officeTypes = OfficeType::orderBy('name')->get(['id', 'name', 'code']);

        $offices = DB::table('offices as o')
            ->leftJoin('office_heads as oh', 'oh.id', '=', 'o.office_head_id')
            ->leftJoin('office_types as ot', 'ot.id', '=', 'o.office_type_id')
            ->where('o.school_id', $schoolId)
            ->select(
                'o.id',
                'o.code',
                'o.name',
                'o.office_type_id',
                'o.office_head_id',
                DB::raw("COALESCE(ot.name,'-') as office_type_name"),
                DB::raw("COALESCE(oh.name,'Unassigned') as office_head_name")
            )
            ->orderBy('o.name')
            ->get();

        return view('admin.assignments.index', [
            'tab'         => 'offices',
            'officeHeads' => $officeHeads,
            'officeTypes' => $officeTypes,
            'offices'     => $offices,
        ]);
    }

    public function createOffice(Request $request)
    {
        $request->validate([
            'code'           => 'nullable|string|max:50',
            'name'           => 'required|string|max:255',
            'office_type_id' => 'nullable|exists:office_types,id',
        ]);

        Office::create([
            'school_id'      => auth()->user()->school_id,
            'code'           => $request->code,
            'name'           => $request->name,
            'office_type_id' => $request->office_type_id ?: null,
        ]);

        return back()->with('success', 'Office created successfully.');
    }

    public function updateOfficeInfo(Request $request, $id)
    {
        $request->validate([
            'code'           => 'nullable|string|max:50',
            'name'           => 'required|string|max:255',
            'office_type_id' => 'nullable|exists:office_types,id',
        ]);

        $office = Office::where('school_id', auth()->user()->school_id)
            ->findOrFail($id);

        $office->update([
            'code'           => $request->code,
            'name'           => $request->name,
            'office_type_id' => $request->office_type_id ?: null,
        ]);

        return back()->with('success', 'Office updated successfully.');
    }

    public function destroyOffice($id)
    {
        $office = Office::where('school_id', auth()->user()->school_id)
            ->findOrFail($id);

        $office->delete();

        return back()->with('success', 'Office deleted successfully.');
    }

    public function storeOffices(Request $request)
    {
        $office = Office::where('school_id', auth()->user()->school_id)
            ->findOrFail($request->office_id);

        $office->update(['office_head_id' => $request->office_head_id ?: null]);

        return back()->with('success', 'Office Head assigned successfully.');
    }

    public function updateOffices(Request $request, $id)
    {
        $office = Office::where('school_id', auth()->user()->school_id)
            ->findOrFail($id);

        $office->update(['office_head_id' => $request->office_head_id ?: null]);

        return back()->with('success', 'Office Head updated successfully.');
    }
}
