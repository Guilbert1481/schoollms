<?php

namespace App\Http\Controllers\Communication;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AnnouncementAssignment;
use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\User; 
use App\Services\Reusable\AssignableService;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::published()
            ->notExpired()
            ->latest()
            ->paginate(10);

        return view('communication.announcements.index', compact('announcements'));
    }

      public function create(AssignableService $assignableService)
    {
        $schoolId = auth()->user()->school_id;

        $groups = $assignableService->getGroups($schoolId)->toArray();

        $users = User::where('school_id', $schoolId)
        ->get()
        ->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->full_name // Calls your getFullNameAttribute()
            ];
        });

        return view('communication.announcements.create', compact('groups','users'));
    }

    public function store(Request $request)
{
    $validated = $request->validate([
        'title'          => 'required|string|max:255',
        'content'        => 'required|string',
        'assignments'    => 'required|array',
        'assignments.*'  => 'string',
        'expires_at'     => 'nullable|date|after:now',
        'priority_level' => 'nullable|string',
    ]);

    $user = auth()->user();
    
    // Create the announcement
    $announcement = new \App\Models\Announcement();
    $announcement->title = $validated['title'];
    $announcement->content = $validated['content'];
    $announcement->created_by = $user->id;
    $announcement->school_id = $user->school_id;
    $announcement->published_at = now();
    
    // Determine priority
    $isSuper = $request->priority_level === 'super';
    $announcement->priority_level = $isSuper ? 'super' : 'normal';

    if ($isSuper) {
        // 🔴 SUPER: Force the 1-hour rule
        $announcement->super_priority_expires_at = now()->addHour();
        $announcement->expires_at = now()->addHour();
    } else {
        // 🟢 NORMAL: Use the standard expiration from form (won't affect red banner)
        $announcement->super_priority_expires_at = null;
        $announcement->expires_at = $validated['expires_at'] ?? null;
    }

    $announcement->save();

    // Handle Assignments (Pivot Table)
    foreach ($validated['assignments'] as $assignment) {
        if (!str_contains($assignment, ':')) continue;
        [$type, $id] = explode(':', $assignment);

        \App\Models\AnnouncementAssignment::create([
            'announcement_id' => $announcement->id,
            'assignable_type' => $type,
            'assignable_id'   => $id,
            'school_id'       => $user->school_id,
        ]);
    }

    return redirect()->route('communication.announcements.index')
        ->with('success', 'Announcement published successfully.');
}

    public function show(Announcement $announcement)
    {
        return view('communication.announcements.show', compact('announcement'));
    }

    public function edit(Announcement $announcement, AssignableService $assignableService)
    {
        $this->authorizeSchool($deadline);

        $schoolId = auth()->user()->school_id;

        $groups = $assignableService->getGroups($schoolId);

        $users = User::where('school_id', $schoolId)
        ->get()
        ->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->full_name // Calls your getFullNameAttribute()
            ];
        });

        return view('communication.announcements.edit', compact('announcement', 'groups','users'));
    }

    public function update(Request $request, Announcement $announcement)
    {
        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'content'        => 'required|string',
            'expires_at'     => 'nullable|date',
            'priority_level' => 'nullable|string',
        ]);

        $announcement->update([
            'title'          => $validated['title'],
            'content'        => $validated['content'],
            'expires_at'     => $validated['expires_at'] ?? null,
            'priority_level' => $validated['priority_level'] ?? 'normal',
        ]);

        return redirect()
            ->route('communication.announcements.index')
            ->with('success', 'Announcement updated successfully.');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return redirect()
            ->route('communication.announcements.index')
            ->with('success', 'Announcement deleted.');
    }
}