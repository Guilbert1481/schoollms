<?php

namespace App\Http\Controllers\Training\Trainor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function index(Request $request)
    {
        return view('training.trainor.materials', [
            'materials' => [],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'file'  => 'required|file|max:20480',
        ]);

        return back()->with('success', 'Material uploaded.');
    }

    public function destroy(Request $request, int $material)
    {
        return back()->with('success', 'Material deleted.');
    }
}
