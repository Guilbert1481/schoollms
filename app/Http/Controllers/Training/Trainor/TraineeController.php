<?php

namespace App\Http\Controllers\Training\Trainor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TraineeController extends Controller
{
    public function index(Request $request)
    {
        return view('training.trainor.trainees', [
            'trainees' => [],
        ]);
    }

    public function show(Request $request, int $trainee)
    {
        return view('training.trainor.trainee_show', [
            'traineeId' => $trainee,
        ]);
    }
}
