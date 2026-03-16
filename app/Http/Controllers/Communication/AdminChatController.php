<?php

namespace App\Http\Controllers\Communication;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Chat;
use Illuminate\Support\Facades\Auth;

class AdminChatController extends Controller
{
    /**
     * Display all chat messages for admin review.
     */
    public function index()
    {
        $chats = Chat::with(['student', 'approvedBy'])
            ->latest()
            ->paginate(20);

        return view('admin.communication.chat.index', compact('chats'));
    }

    /**
     * Show a specific chat thread.
     */
    public function show(Chat $chat)
    {
        $this->authorize('view', $chat);

        return view('admin.communication.chat.show', compact('chat'));
    }

    /**
     * Approve a pending chat message.
     */
    public function approve(Chat $chat)
    {
        $this->authorize('approve', $chat);

        if ($chat->status !== 'pending') {
            return back()->with('error', 'Only pending messages can be approved.');
        }

        $chat->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Message approved successfully.');
    }

    /**
     * Escalate chat to guidance (future ready).
     */
    public function escalate(Chat $chat)
    {
        $this->authorize('escalate', $chat);

        $chat->update([
            'status' => 'escalated',
        ]);

        return back()->with('success', 'Message escalated to guidance.');
    }

    /**
     * Close a chat conversation.
     */
    public function close(Chat $chat)
    {
        $this->authorize('close', $chat);

        $chat->update([
            'status' => 'closed',
        ]);

        return back()->with('success', 'Chat closed successfully.');
    }
}
