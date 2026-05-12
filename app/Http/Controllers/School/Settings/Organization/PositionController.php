<?php

namespace App\Http\Controllers\School\Settings\Organization;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Position;

class PositionController extends Controller
{
    public function indexPositions()
    {
        $data = Position::orderBy('name')->get();

        $columns = [
            ['key' => 'name', 'label' => 'Position Name'],
            ['key' => 'description', 'label' => 'Description'],
        ];

        return view(
            'school.settings.master-data.organization.positions',
            compact('data','columns')
        );
    }

    public function store(Request $request)
    {
        Position::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->back()->with('success', 'Position created successfully.');
    }

    public function storePositions(Request $request)
    {
        return $this->store($request);
    }

    public function update(Request $request, $id)
    {
        $position = Position::findOrFail($id);

        $position->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->back()->with('success', 'Position updated successfully.');
    }

    public function updatePositions(Request $request, $id)
    {
        return $this->update($request, $id);
    }

    public function destroy($id)
    {
        Position::destroy($id);
        return redirect()->back()->with('success', 'Position deleted successfully.');
    }

    public function destroyPositions($id)
    {
        return $this->destroy($id);
    }
}
