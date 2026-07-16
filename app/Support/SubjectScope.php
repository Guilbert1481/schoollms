<?php

namespace App\Support;

use App\Models\TeacherProfile;
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

        if (! $user) {
            return 'academic';
        }

        return in_array($user->role, ['trainor', 'training_program_head'], true)
            ? 'training'
            : 'academic';
    }

    /**
     * Apply both school + scope filters to a Subject query for the given user.
     * Academic users also see legacy rows where scope IS NULL.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
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

    /**
     * Restrict a Subject query to the subjects assigned to the given teacher
     * (the teacher_subjects pivot, keyed by their teacher_profiles.id).
     *
     * Only the 'teacher' role is restricted — managers (program_head, dean,
     * admin, …) keep the full catalogue. A teacher with no profile / no
     * assignments matches nothing. Does NOT apply the school/scope filter;
     * compose with applyTo() (see forTeacher) or add it separately.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function restrictToTeacher(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?: Auth::user();

        if (! $user || $user->role !== 'teacher') {
            return $query;
        }

        $profileId = TeacherProfile::where('user_id', $user->id)->value('id');

        // No profile => no assignments => sees nothing.
        if (! $profileId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('subjects.id', function ($sub) use ($profileId) {
            $sub->from('teacher_subjects')
                ->select('subject_id')
                ->where('teacher_id', $profileId);
        });
    }

    /**
     * Full teacher-facing subject scope: school + academic/training scope
     * (applyTo) PLUS the teacher's own assigned subjects (restrictToTeacher).
     * Non-teacher roles fall through to the plain applyTo catalogue.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function forTeacher(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?: Auth::user();

        return self::restrictToTeacher(self::applyTo($query, $user), $user);
    }
}
