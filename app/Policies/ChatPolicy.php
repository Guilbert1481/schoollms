<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Chat;

class ChatPolicy
{
    /**
     * Admin can view any chat.
     */
    public function view(User $user, Chat $chat)
    {
        return $user->role === 'admin';
    }

    /**
     * Admin can approve only pending chats.
     */
    public function approve(User $user, Chat $chat)
    {
        return $user->role === 'admin' && $chat->status === 'pending';
    }

    /**
     * Admin can escalate pending or approved chats.
     */
    public function escalate(User $user, Chat $chat)
    {
        return $user->role === 'admin' 
            && in_array($chat->status, ['pending', 'approved']);
    }

    /**
     * Admin can close approved or escalated chats.
     */
    public function close(User $user, Chat $chat)
    {
        return $user->role === 'admin' 
            && in_array($chat->status, ['approved', 'escalated']);
    }
}
