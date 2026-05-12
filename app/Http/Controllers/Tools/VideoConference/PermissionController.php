<?php

namespace App\Http\Controllers\Tools\VideoConference;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\User;
use App\Models\VideoConferencePermission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PermissionController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $permissions = VideoConferencePermission::query()
            ->with(['teacher', 'student', 'group', 'grantedBy'])
            ->where('school_id', $user->school_id)
            ->when(
                $user->isTeacher(),
                fn ($query) => $query->where('teacher_id', $user->id)
            )
            ->when(
                $user->isStudent(),
                fn ($query) => $query->where('student_id', $user->id)
            )
            ->latest()
            ->get();

        return view('tools.video-conference.permissions', [
            'permissions' => $permissions,
            'students' => $this->students($user),
            'teachers' => $this->teachers($user),
            'groups' => $this->groups($user),
            'canManagePermissions' => $this->canManagePermissions($user),
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        abort_unless($this->canManagePermissions($user), 403);

        $validated = $request->validate([
            'student_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'teacher_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'group_id' => ['nullable', 'integer', Rule::exists('groups', 'id')],
            'context_name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $teacherId = $user->isTeacher() ? $user->id : ($validated['teacher_id'] ?? null);
        abort_unless($teacherId, 422, 'A teacher must be selected.');

        $student = User::query()
            ->where('school_id', $user->school_id)
            ->where('role', 'student')
            ->whereKey($validated['student_id'])
            ->firstOrFail();

        $teacher = User::query()
            ->where('school_id', $user->school_id)
            ->where('role', 'teacher')
            ->whereKey($teacherId)
            ->firstOrFail();

        $groupId = $validated['group_id'] ?? null;
        if ($groupId) {
            Group::query()
                ->where('school_id', $user->school_id)
                ->whereKey($groupId)
                ->firstOrFail();
        }

        VideoConferencePermission::create([
            'school_id' => $user->school_id,
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'group_id' => $groupId,
            'context_name' => $validated['context_name'],
            'notes' => $validated['notes'] ?? null,
            'allow_repeated_room_creation' => true,
            'is_active' => true,
            'granted_by_user_id' => $user->id,
        ]);

        return redirect()
            ->route('tools.video-conference.permissions.index')
            ->with('success', 'Student room creation permission granted.');
    }

    public function toggle(VideoConferencePermission $permission)
    {
        $user = auth()->user();
        abort_unless($this->canManagePermissions($user), 403);
        abort_unless($permission->school_id === $user->school_id, 404);

        if ($user->isTeacher() && $permission->teacher_id !== $user->id) {
            abort(403);
        }

        $permission->is_active = !$permission->is_active;
        $permission->save();

        return redirect()
            ->route('tools.video-conference.permissions.index')
            ->with('success', $permission->is_active ? 'Permission activated.' : 'Permission revoked.');
    }

    private function canManagePermissions(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher() || $user->isSuperadmin();
    }

    private function students(User $user)
    {
        if (!$this->canManagePermissions($user)) {
            return collect();
        }

        return User::query()
            ->where('school_id', $user->school_id)
            ->where('role', 'student')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    private function teachers(User $user)
    {
        if (!$user->isAdmin() && !$user->isSuperadmin()) {
            return collect([$user]);
        }

        return User::query()
            ->where('school_id', $user->school_id)
            ->where('role', 'teacher')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    private function groups(User $user)
    {
        if (!$this->canManagePermissions($user)) {
            return collect();
        }

        return Group::query()
            ->where('school_id', $user->school_id)
            ->orderBy('name')
            ->get();
    }
}
