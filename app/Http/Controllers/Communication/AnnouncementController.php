<?php

namespace App\Http\Controllers\Communication;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AnnouncementAssignment;
use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\User; 
use App\Models\Office;
use App\Models\Program;
use App\Models\Group;

use App\Services\Reusable\AssignableService;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::with('creator')->latest()->paginate(10);

        foreach ($announcements as $announcement) {
            $announcement->sent_count = $announcement->getSentCount();
            $announcement->ack_count = $announcement->acknowledgements()->count();
            $announcement->status = $announcement->getStatus();
        }

        $offices = Office::all();
        $programs = Program::all();
        $groups = Group::all();

        return view('communication.announcements.index', compact(
            'announcements','offices','programs','groups'
        ));
    }

      public function create(AssignableService $assignableService)
        {
            $schoolId = auth()->user()->school_id;

            $groups = $assignableService->getGroups($schoolId)->toArray();

            $offices = Office::where('school_id', $schoolId)
                ->get()
                ->map(function ($office) {
                    return [
                        'id' => $office->id,
                        'name' => $office->office_name
                    ];
                });

            $programs = Program::where('school_id', $schoolId)
                ->get()
                ->map(function ($program) {
                    return [
                        'id' => $program->id,
                        'name' => $program->program_name
                    ];
                });

            return view('communication.announcements.create', compact(
                'groups',
                'offices',
                'programs'
            ));
        }


public function store(Request $request)
{
    $priorityLevel = $request->priority_level ?? 'normal';
    $isSuper = $priorityLevel === 'super';
    $minutes = max(1, min(1440, (int) ($request->super_priority_minutes ?? 60)));

    $announcement = Announcement::create([
        'title' => $request->title,
        'content' => $request->content,
        'announcement_type' => $request->announcement_type,
        // Super priority publishes immediately; regular uses form values.
        'published_at' => $isSuper ? now() : $request->published_at,
        'expires_at'   => $isSuper ? now()->addMinutes($minutes) : $request->expires_at,
        'priority_level' => $priorityLevel,
        // Flashing red banner window for super priority (until acknowledged or this expires).
        'super_priority_expires_at' => $isSuper ? now()->addMinutes($minutes) : null,
        'created_by' => auth()->id(),
        'school_id' => auth()->user()->school_id,
    ]);

    // Assign Offices
    if ($request->offices) {
        foreach ($request->offices as $officeId) {
            AnnouncementAssignment::create([
                'announcement_id' => $announcement->id,
                'assignable_type' => 'office',
                'assignable_id' => $officeId,
                'school_id' => auth()->user()->school_id,
            ]);
        }
    }

    // Assign Programs
    if ($request->programs) {
        foreach ($request->programs as $programId) {
            AnnouncementAssignment::create([
                'announcement_id' => $announcement->id,
                'assignable_type' => 'program',
                'assignable_id' => $programId,
                'school_id' => auth()->user()->school_id,
            ]);
        }
    }

    // Assign Groups
    if ($request->groups) {
        foreach ($request->groups as $groupId) {
            AnnouncementAssignment::create([
                'announcement_id' => $announcement->id,
                'assignable_type' => 'group',
                'assignable_id' => $groupId,
                'school_id' => auth()->user()->school_id,
            ]);
        }
    }

    return redirect()->route('communication.announcements.index')
        ->with('success', 'Announcement created successfully.');
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
            'super_priority_minutes' => 'nullable|integer|min:1|max:1440',
        ]);

        $priorityLevel = $validated['priority_level'] ?? 'normal';
        $isSuper = $priorityLevel === 'super';
        $minutes = max(1, min(1440, (int) ($validated['super_priority_minutes'] ?? 60)));

        // When toggled ON (or duration changed), reset the window; otherwise keep the existing one.
        $wasSuper = $announcement->priority_level === 'super' && $announcement->super_priority_expires_at;
        $superExpiresAt = $isSuper
            ? ($wasSuper && !$request->filled('super_priority_minutes')
                ? $announcement->super_priority_expires_at
                : now()->addMinutes($minutes))
            : null;

        $announcement->update([
            'title'          => $validated['title'],
            'content'        => $validated['content'],
            'expires_at'     => $isSuper ? $superExpiresAt : ($validated['expires_at'] ?? null),
            'priority_level' => $priorityLevel,
            'super_priority_expires_at' => $superExpiresAt,
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