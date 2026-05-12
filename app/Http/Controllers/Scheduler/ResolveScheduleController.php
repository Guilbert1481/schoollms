<?php

namespace App\Http\Controllers\Scheduler;

use App\Http\Controllers\Controller;
use App\Services\Scheduler\ScheduleAutoFixService;
use Illuminate\Http\Request;

class ResolveScheduleController extends Controller
{
    public function __invoke(Request $request, ScheduleAutoFixService $fixer)
    {
        $schedule = [
            'sessions'  => $request->input('sessions',  []),
            'conflicts' => $request->input('conflicts', []),
        ];
        $payload  = $request->input('payload', []);
        $maxPasses = (int) $request->input('max_passes', 3);

        $fixed = $fixer->fix($schedule, $payload, $maxPasses);

        return response()->json([
            'ok'       => true,
            'sessions' => $fixed['sessions']  ?? [],
            'conflicts'=> $fixed['conflicts'] ?? [],
            'score'    => $fixed['score']     ?? null,
            'pass'     => $fixed['pass']      ?? null,
        ]);
    }
}
