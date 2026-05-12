<?php

namespace App\Http\Controllers\Tools\VideoConference;

use App\Http\Controllers\Controller;
use App\Models\VideoConferenceRoom;
use App\Models\VideoConferenceSession;

class MeetingRoomController extends Controller
{
    public function show(VideoConferenceRoom $room)
    {
        abort_unless($room->school_id === auth()->user()->school_id, 404);

        VideoConferenceSession::syncExpiredForSchool(auth()->user()->school_id);

        return view('tools.video-conference.meeting-room', [
            'room' => $room->load(['creator', 'owner', 'group', 'activeSession.host', 'latestSession']),
        ]);
    }
}
