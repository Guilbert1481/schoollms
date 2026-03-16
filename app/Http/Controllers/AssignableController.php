<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\Reusable\AssignableService;

class AssignableController extends Controller
{
    public function users(Request $request, AssignableService $assignableService)
    {
        $schoolId = auth()->user()->school_id;

        try {
            return response()->json(
                $assignableService->getUsers(
                    $schoolId,
                    $request->search
                )
            );
        } catch (\Exception $e) {

            Log::error($e->getMessage());

            return response()->json([
                'error'   => 'Internal server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}