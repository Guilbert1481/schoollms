<?php

namespace App\Services\Reusable;

use App\Models\User;
use App\Models\Department;
use App\Models\Program;

class AssignableService
{
    /**
     * Get all assignable groups for a specific school.
     *
     * @param int $schoolId
     * @return \Illuminate\Support\Collection
     */
    public function getGroups(int $schoolId)
    {
        return collect()

            ->merge(
                Department::where('school_id', $schoolId)
                    ->get()
                    ->map(fn($d) => [
                        'id'   => $d->id,
                        'name' => $d->name,
                        'type' => 'department'
                    ])
            )

            ->merge(
                Program::where('school_id', $schoolId)
                    ->get()
                    ->map(fn($p) => [
                        'id'   => $p->id,
                        'name' => $p->name,
                        'type' => 'program'
                    ])
            )

            /*add here other group you want to add to the dropdown*/

            ->values(); // reset collection keys
    }

    /**
     * Get limited users for dropdown (searchable).
     *
     * @param int $schoolId
     * @param string|null $search
     * @return \Illuminate\Support\Collection
     */
    public function getUsers(int $schoolId, ?string $search = null)
{
    $search = trim((string) $search);

    $query = User::where('users.school_id', $schoolId)
        ->with('profile')
        ->when($search !== '', function ($q) use ($search) {
            $q->where(function ($inner) use ($search) {
                $inner->whereHas('profile', function ($p) use ($search) {
                    $p->where('first_name', 'like', "%{$search}%")
                      ->orWhere('middle_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%");
                })
                ->orWhere('users.email', 'like', "%{$search}%");
            });
        })
        ->leftJoin('profiles', 'profiles.user_id', '=', 'users.id')
        ->orderByRaw('COALESCE(profiles.last_name, users.email) ASC')
        ->select('users.*')
        ->limit(50)
        ->get();

    return $query->map(function ($user) {
        $profile = $user->profile;

        if ($profile) {
            $middle = $profile->middle_name
                ? ' ' . strtoupper(substr($profile->middle_name, 0, 1)) . '.'
                : '';
            $full = trim(($profile->first_name ?? '') . $middle . ' ' . ($profile->last_name ?? ''));
        } else {
            $full = '';
        }

        if ($full === '') {
            $full = $user->email ?: ('User #' . $user->id);
        }

        return [
            'id'   => $user->id,
            'name' => $full,
        ];
    })->values();
}
}