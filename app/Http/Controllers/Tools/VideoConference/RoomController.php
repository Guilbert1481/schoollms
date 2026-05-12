<?php

namespace App\Http\Controllers\Tools\VideoConference;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\User;
use App\Models\VideoConferencePermission;
use App\Models\VideoConferenceRoom;
use App\Models\VideoConferenceSession;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        VideoConferenceSession::syncExpiredForSchool($user->school_id);

        $rooms = VideoConferenceRoom::query()
            ->with(['creator', 'owner', 'group', 'permission.teacher', 'permission.student', 'activeSession', 'latestSession'])
            ->where('school_id', $user->school_id)
            ->latest()
            ->paginate(12);

        return view('tools.video-conference.room-list', [
            'rooms' => $rooms,
            'canManageAllRooms' => $this->canManageAllRooms($user),
        ]);
    }

    public function create()
    {
        $user = auth()->user();

        return view('tools.video-conference.room-create', [
            'groups' => $this->availableGroups($user),
            'studentPermissions' => $this->availableStudentPermissions($user),
            'isStudent' => $user->isStudent(),
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'context_name' => ['nullable', 'string', 'max:255'],
            'group_id' => ['nullable', 'integer', Rule::exists('groups', 'id')],
            'starts_at' => ['nullable', 'date'],
            'permission_id' => ['nullable', 'integer', Rule::exists('video_conference_permissions', 'id')],
        ]);

        $roomData = [
            'school_id' => $user->school_id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'starts_at' => $validated['starts_at'] ?? null,
            'created_by' => $user->id,
            'owner_user_id' => $user->id,
            'status' => 'scheduled',
            'auto_end_minutes' => 180,
        ];

        if ($user->isStudent()) {
            $permission = $this->resolveStudentPermission($user, $validated['permission_id'] ?? null);

            $roomData['permission_id'] = $permission->id;
            $roomData['group_id'] = $permission->group_id;
            $roomData['context_name'] = $permission->context_name;
        } else {
            $roomData['group_id'] = $this->validatedGroupIdForSchool($user->school_id, $validated['group_id'] ?? null);
            $roomData['context_name'] = $validated['context_name'] ?? null;
        }

        if (!$roomData['group_id'] && blank($roomData['context_name'])) {
            return back()
                ->withInput()
                ->withErrors(['context_name' => 'Context name is required when no group is selected.']);
        }

        $room = VideoConferenceRoom::create($roomData);

        return redirect()
            ->route('tools.video-conference.rooms.join', $room)
            ->with('success', 'Video conference room created.');
    }

    public function edit(VideoConferenceRoom $room)
    {
        $this->ensureSameSchoolRoom($room);
        $this->ensureRoomEditor($room);

        return view('tools.video-conference.room-edit', [
            'room' => $room->load(['group', 'permission']),
            'groups' => $this->availableGroups(auth()->user()),
        ]);
    }

    public function update(Request $request, VideoConferenceRoom $room)
    {
        $this->ensureSameSchoolRoom($room);
        $this->ensureRoomEditor($room);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'context_name' => ['nullable', 'string', 'max:255'],
            'group_id' => ['nullable', 'integer', Rule::exists('groups', 'id')],
            'starts_at' => ['nullable', 'date'],
        ]);

        $room->fill([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'starts_at' => $validated['starts_at'] ?? null,
        ]);

        if (!auth()->user()->isStudent()) {
            $room->group_id = $this->validatedGroupIdForSchool(auth()->user()->school_id, $validated['group_id'] ?? null);
            $room->context_name = $validated['context_name'] ?? null;
        }

        if (!$room->group_id && blank($room->context_name)) {
            return back()
                ->withInput()
                ->withErrors(['context_name' => 'Context name is required when no group is selected.']);
        }

        $room->save();

        return redirect()
            ->route('tools.video-conference.rooms.index')
            ->with('success', 'Room updated.');
    }

    public function join(VideoConferenceRoom $room)
    {
        $this->ensureSameSchoolRoom($room);
        VideoConferenceSession::syncExpiredForSchool(auth()->user()->school_id);

        return view('tools.video-conference.room-join', [
            'room' => $room->load(['creator', 'owner', 'group', 'permission.teacher', 'permission.student', 'activeSession', 'latestSession']),
        ]);
    }

    private function availableGroups(User $user)
    {
        return Group::query()
            ->where('school_id', $user->school_id)
            ->orderBy('name')
            ->get();
    }

    private function availableStudentPermissions(User $user)
    {
        if (!$user->isStudent()) {
            return collect();
        }

        return VideoConferencePermission::query()
            ->with(['teacher', 'group'])
            ->where('school_id', $user->school_id)
            ->where('student_id', $user->id)
            ->where('is_active', true)
            ->orderBy('context_name')
            ->get();
    }

    private function resolveStudentPermission(User $user, ?int $permissionId): VideoConferencePermission
    {
        if (!$permissionId) {
            abort(422, 'A teacher-approved permission is required before a student can create a room.');
        }

        return VideoConferencePermission::query()
            ->where('school_id', $user->school_id)
            ->where('student_id', $user->id)
            ->where('is_active', true)
            ->whereKey($permissionId)
            ->firstOrFail();
    }

    private function validatedGroupIdForSchool(int $schoolId, ?int $groupId): ?int
    {
        if (!$groupId) {
            return null;
        }

        return Group::query()
            ->where('school_id', $schoolId)
            ->whereKey($groupId)
            ->value('id');
    }

    private function ensureSameSchoolRoom(VideoConferenceRoom $room): void
    {
        abort_unless($room->school_id === auth()->user()->school_id, 404);
    }

    private function ensureRoomEditor(VideoConferenceRoom $room): void
    {
        $user = auth()->user();

        abort_unless(
            $this->canManageAllRooms($user) || $room->owner_user_id === $user->id,
            403
        );
    }

    private function canManageAllRooms(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher() || $user->isSuperadmin();
    }
}
