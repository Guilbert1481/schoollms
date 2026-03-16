<?php

namespace App\Http\Controllers\Communication;

use App\Http\Controllers\Controller;
use App\Models\Deadline;
use App\Models\DeadlineAssignment;
use App\Models\DeadlineUserCompletion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\Reusable\AssignableService;
use Carbon\Carbon;

class DeadlineController extends Controller
{
    /* =====================================================
     | INDEX
     |=====================================================*/
    public function index()
    {
        $user = auth()->user();

        $deadlines = Deadline::where('school_id', $user->school_id)
            ->with('creator')
            ->where(function ($query) use ($user) {

                // Deadlines assigned to this user
                $query->whereHas('userCompletions', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })

                // OR deadlines created by this user
                ->orWhere('created_by', $user->id);
            })
            ->withCount([
                // Total assigned users
                'userCompletions as total_users',

                // Completed users (submitted or late)
                'userCompletions as completed_users' => function ($query) {
                    $query->whereIn('status', ['submitted', 'late']);
                }
            ])
            ->latest()
            ->paginate(10);

        return view('communication.deadlines.index', compact('deadlines'));
    }

    /* =====================================================
     | CREATE
     |=====================================================*/
    public function create(AssignableService $assignableService)
    {
        $schoolId = auth()->user()->school_id;

        $groups = $assignableService->getGroups($schoolId)->toArray();

        $users = User::where('school_id', $schoolId)
        ->get()
        ->map(function ($user) {
            return [
                'id' => $user->id,
                // This calls your getFullNameAttribute()
                'name' => $user->full_name 
            ];
        });

        return view('communication.deadlines.create', compact('groups','users'));
    }






    

    /* =====================================================
     | STORE
     |=====================================================*/
    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'content'     => 'nullable|string',
            'due_date'    => 'required|date',
            'assignments' => 'required|array',
        ]);

        $user     = auth()->user();
        $schoolId = $user->school_id;

        DB::beginTransaction();

        try {

            // 1️⃣ Create the deadline
            $deadline = Deadline::create([
                'title'               => $request->title,
                'content'             => $request->content,
                'due_date'            => Carbon::parse($request->due_date),
                'created_by'          => $user->id,
                'school_id'           => $schoolId,
                'requires_submission' => false,
            ]);

            // 2️⃣ Assign recipients (duplicate-safe)
            foreach ($request->assignments as $assignment) {

                [$type, $id] = explode(':', $assignment);

                DeadlineAssignment::firstOrCreate(
                    [
                        'deadline_id'     => $deadline->id,
                        'assignable_type' => $type,
                        'assignable_id'   => $id,
                    ],
                    [
                        'school_id'  => $schoolId,
                        'assigned_by'=> $user->id,
                    ]
                );
            }

            DB::commit();

            // 3️⃣ Redirect (PREVENTS duplicate on refresh)
            return redirect()
                ->route('communication.deadlines.index')
                ->with('success', 'Deadline created successfully.');

        } catch (\Exception $e) {

            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Something went wrong. Please try again.');
        }
    }








    /* =====================================================
     | SHOW
     |=====================================================*/
    public function show(Deadline $deadline)
    {
        $this->authorizeSchool($deadline);

        return view('communication.deadlines.show', compact('deadline'));
    }

    /* =====================================================
     | EDIT
     |=====================================================*/
    public function edit(Deadline $deadline, AssignableService $assignableService)
    {
        $this->authorizeSchool($deadline);

        $schoolId = auth()->user()->school_id;
        $groups = $assignableService->getGroups($schoolId)->toArray();
        $users = User::where('school_id', $schoolId)
        ->get()
        ->map(function ($user) {
            return [
                'id' => $user->id,
                // This calls your getFullNameAttribute()
                'name' => $user->full_name 
            ];
        });

        return view('communication.deadlines.edit', compact('deadline', 'groups','users'));
    }

    /* =====================================================
     | UPDATE
     |=====================================================*/
    public function update(Request $request, Deadline $deadline)
    {
        $this->authorizeSchool($deadline);

        $request->validate([
            'title'    => 'required|string|max:255',
            'content'  => 'required|string',
            'due_date' => 'required|date'
        ]);

        $deadline->update($request->only('title', 'content', 'due_date'));

        return redirect()
            ->route('communication.deadlines.index')
            ->with('success', 'Deadline updated.');
    }

    /* =====================================================
     | DESTROY
     |=====================================================*/
    public function destroy(Deadline $deadline)
    {
        $this->authorizeSchool($deadline);

        $deadline->delete();

        return redirect()
            ->route('communication.deadlines.index')
            ->with('success', 'Deadline deleted.');
    }

    /* =====================================================
     | MARK COMPLETE
     |=====================================================*/
    public function markComplete(Deadline $deadline)
    {
        $this->authorizeSchool($deadline);

        $user = auth()->user();

        DeadlineUserCompletion::where('deadline_id', $deadline->id)
        ->where('user_id', $user->id)
        ->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        return redirect()->back()
            ->with('success', 'Deadline marked as complete.');
    }

    /* =====================================================
     | EXPAND ASSIGNMENTS TO USERS
     |=====================================================*/
    private function expandToUsers($type, $id, $schoolId)
    {
        return match ($type) {

            'user' => User::where('id', $id)
                ->where('school_id', $schoolId)
                ->get(),

            'department' => User::where('department_id', $id)
                ->where('school_id', $schoolId)
                ->get(),

            'class' => User::where('class_id', $id)
                ->where('school_id', $schoolId)
                ->get(),

            'faculty' => User::where('faculty_id', $id)
                ->where('school_id', $schoolId)
                ->get(),

            default => collect(),
        };
    }

    /* =====================================================
     | SCHOOL AUTHORIZATION HELPER
     |=====================================================*/
    private function authorizeSchool(Deadline $deadline)
    {
        if ($deadline->school_id !== auth()->user()->school_id) {
            abort(403);
        }
    }

    /* =====================================================
     | COMPLIANCE DATA (AJAX)
     |=====================================================*/
    public function complianceData(Deadline $deadline)
    {
        $this->authorizeSchool($deadline);

        $pendingUsers = DeadlineUserCompletion::with('user')
            ->where('deadline_id', $deadline->id)
            ->where('status', '!=', 'completed')
            ->get();

        return response()->json([
            'success' => true,
            'users'   => $pendingUsers->map(fn($item) => [
                'name'         => $item->user->name,
                'status'       => $item->status,
                'completed_at' => $item->completed_at,
            ])
        ]);
    }

    /* =====================================================
     | MANAGE
     |=====================================================*/
    public function manage(Deadline $deadline)
    {
        $this->authorizeSchool($deadline);

        $currentUser = auth()->user();

        if ($deadline->created_by !== $currentUser->id && !$currentUser->is_admin) {
            abort(403);
        }

        $completionRecords = DeadlineUserCompletion::with('user')
        ->where('deadline_id', $deadline->id)
        ->paginate(10);

        return view('communication.deadlines.manage', compact('deadline', 'completionRecords'));
    }
}