<?php

namespace App\Http\Controllers\Training\ProgramHead;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class TrainorController extends Controller
{
    public function index(Request $request)
    {
        $trainors = User::query()
            ->where('school_id', $request->user()->school_id)
            ->where('role', 'trainor')
            ->orderBy('name')
            ->paginate(15);

        return view('training.program_head.trainors.index', [
            'trainors' => $trainors,
        ]);
    }
}
