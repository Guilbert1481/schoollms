<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToSchool
{
    protected static function bootBelongsToSchool(): void
    {
        static::addGlobalScope('school', function (Builder $query) {

            // Only apply scope if user is authenticated
            if (!Auth::check()) {
                return;
            }


            $user = Auth::user();

            // Superadmin sees everything across all schools.
            // NOTE: must use isSuperadmin() (canonical `role === 'superadmin'`).
            // The old `$user->role('super_admin')` actually invoked the role()
            // *relationship* and returned a truthy BelongsTo object, so this
            // bypass fired for EVERY user — silently disabling the scope.
            if ($user->isSuperadmin()) {
                return;
            }

            // Apply school filter with table prefix to avoid ambiguity errors
            if ($user->school_id) {
                $tableName = $query->getModel()->getTable();
                $query->where($tableName . '.school_id', $user->school_id);
            }
        });

        static::creating(function ($model) {

            if (!Auth::check()) {
                return;
            }

            $user = Auth::user();

            // Auto-assign the user's school_id to the new record
            if (! $user->isSuperadmin() && !$model->school_id) {
                $model->school_id = $user->school_id;
            }
        });
    }
}