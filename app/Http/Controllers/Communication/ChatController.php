<?php

namespace App\Http\Controllers\Communication;

use App\Http\Controllers\Controller;
use App\Models\ChatThread;
use App\Models\ChatMessage;
use App\Models\User;
use App\Notifications\FlaggedChatNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\Reusable\AssignableService;

class ChatController extends Controller
{
    public function index()
    {
        $threads = auth()->user()
            ->chatThreads()
            ->with('participants')
            ->latest()
            ->get();

        $unreadChats = 0;

        foreach ($threads as $thread) {
            $participant = $thread->participants
                ->firstWhere('id', auth()->id());

            $lastRead = $participant?->pivot?->last_read_at;

            $unreadChats += $thread->messages()
                ->when($lastRead, function ($query) use ($lastRead) {
                    $query->where('created_at', '>', $lastRead);
                })
                ->where('user_id', '!=', auth()->id())
                ->count();
        }

        return view('communication.chat.index', compact('threads', 'unreadChats'));
    }



    public function show(ChatThread $thread)
    {
        $this->authorizeThread($thread);

        $messages = $thread->messages()
            ->orderBy('created_at')
            ->get();

        // Mark related notifications as read
        auth()->user()
            ->unreadNotifications
            ->where('data.chat_thread_id', $thread->id)
            ->markAsRead();

        // Update pivot last_read_at
        $thread->participants()
            ->updateExistingPivot(auth()->id(), [
                'last_read_at' => now()
            ]);

        return view('communication.chat.show', compact('thread', 'messages'));
    }




    public function storeMessage(Request $request, ChatThread $thread)
    {
        $this->authorizeThread($thread);

        $request->validate([
            'message' => 'required|string'
        ]);

        $messageText = $request->message;

        // 🔎 Tiered Flag Detection
        $levels = config('chat.levels');

        $isFlagged = false;
        $flagLevel = null;

        $messageLower = strtolower($messageText);

        foreach ($levels as $level => $words) {
            foreach ($words as $word) {
                if (str_contains($messageLower, strtolower($word))) {
                    $isFlagged = true;
                    $flagLevel = $level;
                    break 2; // stop immediately
                }
            }
        }

        // 💬 Save Message
        $message = ChatMessage::create([
            'chat_thread_id' => $thread->id,
            'user_id'        => auth()->id(),
            'message'        => $messageText,
            'is_flagged'     => $isFlagged,
            'flag_level'     => $flagLevel,
        ]);

        // 🚨 Notify Based on Severity Level
        if ($isFlagged && $flagLevel) {

            $rolesToNotify = config("chat.notify_roles.$flagLevel");

            $recipients = User::whereIn('role', $rolesToNotify)->get();

            foreach ($recipients as $user) {
                $user->notify(new FlaggedChatNotification($message));
            }
        }

        return back();
    }

    public function create(AssignableService $assignableService)
    {
        $schoolId = auth()->user()->school_id;

        $groups = $assignableService->getGroups($schoolId)->toArray();
		
		$users = User::where('id', '!=', auth()->id())->get();

        return view('communication.chat.create', compact('groups','users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'nullable|string|max:255',
            'assignments' => 'required|array|min:1',
        ]);

        // Extract only user assignments
        $participants = collect($request->assignments)
            ->filter(fn($item) => str_starts_with($item, 'user:'))
            ->map(fn($item) => (int) str_replace('user:', '', $item))
            ->values()
            ->all();

        if (count($participants) === 0) {
            return back()->withErrors(['assignments' => 'Please select at least one user.']);
        }

        $thread = ChatThread::create([
            'title'      => $request->title,
            'type'       => count($participants) > 1 ? 'group' : 'private',
            'school_id'  => Auth::user()->school_id,
            'created_by' => Auth::id(),
            'status'     => 'active',
        ]);

        $thread->participants()->attach($participants);
        $thread->participants()->attach(Auth::id());

        return redirect()->route('communication.chat.show', $thread);
    }





    protected function authorizeThread(ChatThread $thread)
    {
        $user = Auth::user();

        if (in_array($thread->type, ['private', 'group'])) {
            abort_unless(
                $thread->participants->contains('id', $user->id),
                403
            );
        }

        if ($thread->type === 'department') {
            abort_unless(
                $user->department_id === $thread->department_id,
                403
            );
        }

        if ($thread->type === 'class') {
            abort_unless(
                $user->classes->contains($thread->class_id),
                403
            );
        }
    }

    public function deleteMessage(ChatMessage $message)
    {
        abort_unless($message->user_id === auth()->id(), 403);

        $message->update([
            'deleted_by_user' => true
        ]);

        $message->delete(); // soft delete

        return back();
    }

    public function requestDeletion(ChatThread $thread)
    {
        abort_unless($thread->created_by === auth()->id(), 403);

        $thread->update([
            'status' => 'pending_deletion'
        ]);

        return back();
    }

    
}
