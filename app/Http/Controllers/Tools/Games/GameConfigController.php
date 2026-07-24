<?php

namespace App\Http\Controllers\Tools\Games;

use App\Http\Controllers\Controller;
use App\Services\Games\GameSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Feeds the shared pre-game configuration component
 * (<x-gamified-configuration>): which difficulty tiers have stock for the
 * current scope, and the hosting teacher's sections. Thin — delegates to
 * {@see GameSessionService::configOptions}; school scoping lives there.
 */
class GameConfigController extends Controller
{
    public function __construct(private GameSessionService $sessions) {}

    public function options(Request $request): JsonResponse
    {
        return response()->json(
            $this->sessions->configOptions($request->user(), $request->query())
        );
    }
}
