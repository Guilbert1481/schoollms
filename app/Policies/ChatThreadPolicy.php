<?php

namespace App\Policies;

use App\Models\ChatThread;
use App\Models\User;

/**
 * Membership rules for chat threads (Roadmap A1). Formalizes the
 * ChatController::authorizeThread chokepoint: private/group threads are
 * visible to participants only; department and class threads to their
 * department/class members.
 */
class ChatThreadPolicy
{
    public function view(User $user, ChatThread $thread): bool
    {
        if (in_array($thread->type, ['private', 'group'], true)) {
            return $thread->participants->contains('id', $user->id);
        }

        if ($thread->type === 'department') {
            return $user->department_id === $thread->department_id;
        }

        if ($thread->type === 'class') {
            return $user->classes->contains($thread->class_id);
        }

        return false;
    }

    /** Delete the whole thread. Private: either participant. Group: creator only. */
    public function delete(User $user, ChatThread $thread): bool
    {
        if (! $this->view($user, $thread)) {
            return false;
        }

        return $thread->type !== 'group' || (int) $thread->created_by === (int) $user->id;
    }

    /** Group-management actions reserved to the thread creator. */
    public function manage(User $user, ChatThread $thread): bool
    {
        return (int) $thread->created_by === (int) $user->id;
    }
}
