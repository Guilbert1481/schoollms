<?php

namespace App\Http\Controllers\Tools\Games;

use App\Http\Controllers\Controller;
use App\Models\Games\GameSession;
use App\Services\Games\GameSessionException;
use App\Services\Games\GameSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Live multiplayer game sessions (Tug-of-War). Thin: validates + delegates to
 * {@see GameSessionService}. Tenant isolation is by construction — GameSession
 * is school-scoped (BelongsToSchool), so route-model binding 404s any session
 * outside the caller's school. Host-only actions add an explicit owner check.
 */
class GameSessionController extends Controller
{
    public function __construct(private GameSessionService $sessions) {}

    /** Config-screen roster: classmates per class (year/subject/section). */
    public function classmates(Request $request): JsonResponse
    {
        return response()->json($this->sessions->roster($request->user()));
    }

    /** Host opens a lobby from the config screen. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mode' => ['required', 'in:solo,opponent,team'],
            'question_type' => ['required', 'in:mcq,identification'],
            'difficulty' => ['required', 'in:average,advanced,mixed'],
            'question_count' => ['nullable', 'integer', 'min:3', 'max:30'],
            'round_seconds' => ['nullable', 'integer', 'min:5', 'max:120'],
            'academic_level_id' => ['nullable', 'integer'],
            'subject_id' => ['nullable', 'integer'],
            'topic_id' => ['nullable', 'integer'],
            'lesson_id' => ['nullable', 'integer'],
            'competency_id' => ['nullable', 'integer'],
            'team_a_label' => ['nullable', 'string', 'max:40'],
            'team_b_label' => ['nullable', 'string', 'max:40'],
            'players' => ['nullable', 'array', 'max:100'],
            'players.*.student_id' => ['required_with:players', 'integer'],
            'players.*.team' => ['required_with:players', 'in:A,B'],
        ]);

        $session = $this->sessions->create($request->user(), $data);

        return response()->json($this->sessions->state($session), 201);
    }

    /** A player opens the lobby by code. */
    public function join(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:12']]);

        try {
            $player = $this->sessions->join($data['code'], $request->user());
            $session = GameSession::findOrFail($player->game_session_id);
        } catch (GameSessionException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($this->sessions->state($session));
    }

    /** Poll the live snapshot (resync / no-websocket fallback). */
    public function show(Request $request, GameSession $session): JsonResponse
    {
        $this->assertParticipant($request, $session);

        return response()->json($this->sessions->state($session));
    }

    /** Host draws questions and starts the match. */
    public function start(Request $request, GameSession $session): JsonResponse
    {
        $this->assertHost($request, $session);

        try {
            $started = $this->sessions->start($session);
        } catch (GameSessionException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($this->sessions->state($started));
    }

    /** A buzz — server grades it. */
    public function answer(Request $request, GameSession $session): JsonResponse
    {
        $this->assertParticipant($request, $session);

        $data = $request->validate([
            'player_id' => ['nullable', 'integer'],
            'team' => ['nullable', 'in:A,B'],
            'choice_index' => ['nullable', 'integer', 'min:0', 'max:11'],
            'answer_text' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $result = $this->sessions->answer($session, $data);
        } catch (GameSessionException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($result);
    }

    /** Host skips / times out the current question. */
    public function advance(Request $request, GameSession $session): JsonResponse
    {
        $this->assertHost($request, $session);

        try {
            $advanced = $this->sessions->advance($session);
        } catch (GameSessionException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($this->sessions->state($advanced));
    }

    /** Host ends the match. */
    public function end(Request $request, GameSession $session): JsonResponse
    {
        $this->assertHost($request, $session);

        return response()->json($this->sessions->state($this->sessions->end($session)));
    }

    private function assertHost(Request $request, GameSession $session): void
    {
        abort_unless((int) $session->host_id === (int) $request->user()->id, 403);
    }

    private function assertParticipant(Request $request, GameSession $session): void
    {
        $userId = (int) $request->user()->id;
        $isParticipant = (int) $session->host_id === $userId
            || $session->players()->where('user_id', $userId)->exists();

        abort_unless($isParticipant, 403);
    }
}
