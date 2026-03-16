<?php

namespace App\Services;

class DashboardIdentityService
{
    public function forUser($user): array
    {
        $default = config('dashboard.identity');

        // School override
        $schoolIdentity = $user->school->dashboard_identity ?? [];

        if (is_string($schoolIdentity)) {
            $schoolIdentity = json_decode($schoolIdentity, true) ?? [];
        }

        // User override
        $userIdentity = $user->dashboard_identity ?? [];

        if (is_string($userIdentity)) {
            $userIdentity = json_decode($userIdentity, true) ?? [];
        }

        return array_replace_recursive(
            $default,
            $schoolIdentity,
            $userIdentity
        );
    }
}
