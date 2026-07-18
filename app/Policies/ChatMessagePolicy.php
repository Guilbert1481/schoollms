<?php

namespace App\Policies;

use App\Models\ChatMessage;
use App\Models\User;

/**
 * Ownership rules for individual chat messages (Roadmap A1). Reading a
 * message (or its attachment) requires access to the parent thread; deleting
 * one is reserved to its author.
 */
class ChatMessagePolicy
{
    public function view(User $user, ChatMessage $message): bool
    {
        $thread = $message->thread;

        return $thread !== null && $user->can('view', $thread);
    }

    public function delete(User $user, ChatMessage $message): bool
    {
        return (int) $message->user_id === (int) $user->id;
    }
}
