<?php

namespace App\Http\Controllers\School\Settings\MasterData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FacilitiesController extends Controller
{
    public function index()
    {
        return view('school.settings.master-data.facilities');
    }

    public function store(Request $request)
    {
        return back()->with('success', 'Facilities store placeholder is ready for implementation.');
    }

    public function update(Request $request)
    {
        return back()->with('success', 'Facilities update placeholder is ready for implementation.');
    }

    public function delete($id)
    {
        return back()->with('success', 'Facilities delete placeholder is ready for implementation.');
    }
}
