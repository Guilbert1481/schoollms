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
    return User::where('school_id', $schoolId)

        ->when($search, function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        })

        ->orderBy('last_name')
        ->limit(10)
        ->get()
        ->map(function ($user) {

            $middle = $user->middle_name
                ? ' ' . strtoupper(substr($user->middle_name, 0, 1)) . '.'
                : '';

            return [
                'id'   => $user->id,
                'name' => trim(
                    $user->first_name . $middle . ' ' . $user->last_name
                ),
            ];
        });
}
}