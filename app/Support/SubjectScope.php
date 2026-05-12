<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SubjectScope
{
    /**
     * Resolve the subject scope ('academic' or 'training') for the given user.
     */
    public static function forUser(?User $user = null): string
    {
        $user = $user ?: Auth::user();

        if (!$user) {
            return 'academic';
        }

        return in_array($user->role, ['trainor', 'training_program_head'], true)
            ? 'training'
            : 'academic';
    }

    /**
     * Apply both school + scope filters to a Subject query for the given user.
     * Academic users also see legacy rows where scope IS NULL.
     */
    public static function applyTo(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?: Auth::user();

        if ($user && $user->school_id) {
            $query->where('school_id', $user->school_id);
        }

        $scope = self::forUser($user);

        return $query->where(function ($q) use ($scope) {
            if ($scope === 'academic') {
                $q->where('scope', 'academic')->orWhereNull('scope');
            } else {
                $q->where('scope', $scope);
            }
        });
    }
}
