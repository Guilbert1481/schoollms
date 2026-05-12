<?php

namespace App\Http\Controllers\Tools\VideoConference;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VideoConferenceRoom;
use App\Models\VideoConferenceSession;

class SessionController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        VideoConferenceSession::syncExpiredForSchool($user->school_id);

        $sessions = VideoConferenceSession::query()
            ->with(['room', 'host', 'starter', 'endedBy', 'reopenedFrom'])
            ->where('school_id', $user->school_id)
            ->latest('started_at')
            ->paginate(15);

        return view('tools.video-conference.sessions', [
            'sessions' => $sessions,
        ]);
    }

    public function start(VideoConferenceRoom $room)
    {
        $this->ensureRoomAvailable($room);

        VideoConferenceSession::syncExpiredForSchool(auth()->user()->school_id);

        if ($room->activeSession()->exists()) {
            return redirect()
                ->route('tools.video-conference.meeting-room.show', $room)
                ->with('error', 'This room already has a live session.');
        }

        $lastEndedSession = $room->sessions()
            ->where('status', 'ended')
            ->latest('started_at')
            ->first();

        $session = VideoConferenceSession::create([
            'school_id' => $room->school_id,
            'room_id' => $room->id,
            'started_by' => auth()->id(),
            'host_user_id' => auth()->id(),
            'reopened_from_session_id' => $lastEndedSession?->id,
            'status' => 'live',
            'started_at' => now(),
            'auto_end_at' => now()->addMinutes($room->auto_end_minutes ?: 180),
        ]);

        $room->status = 'live';
        $room->ended_at = null;
        $room->save();

        return redirect()
            ->route('tools.video-conference.meeting-room.show', $room)
            ->with('success', 'Meeting session started.');
    }

    public function end(VideoConferenceSession $session)
    {
        $user = auth()->user();
        abort_unless($session->school_id === $user->school_id, 404);
        abort_unless($this->canEndSession($user, $session), 403);

        if ($session->status !== 'live') {
            return back()->with('error', 'This session is already ended.');
        }

        $session->status = 'ended';
        $session->ended_at = now();
        $session->ended_by = $user->id;
        $session->ended_reason = 'manual';
        $session->save();

        if ($session->room) {
            $session->room->status = 'ended';
            $session->room->ended_at = $session->ended_at;
            $session->room->save();
        }

        return redirect()
            ->route('tools.video-conference.sessions.index')
            ->with('success', 'Session ended.');
    }

    public function reopen(VideoConferenceRoom $room)
    {
        $this->ensureRoomAvailable($room);

        return $this->start($room);
    }

    private function ensureRoomAvailable(VideoConferenceRoom $room): void
    {
        abort_unless($room->school_id === auth()->user()->school_id, 404);
    }

    private function canEndSession(User $user, VideoConferenceSession $session): bool
    {
        return $user->id === $session->host_user_id
            || $user->isAdmin()
            || $user->isTeacher()
            || $user->isSuperadmin();
    }
}
