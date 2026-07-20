<?php

namespace App\Support;

use App\Models\User;

/**
 * Virtual (derived) security nudges for the notification bell. Currently a
 * single row that prompts any signed-in user who has NOT enabled two-factor
 * authentication to turn it on. It appears automatically while
 * `google2fa_secret` is empty and disappears the moment the user enrols — no
 * DB row is stored, so it can never go stale.
 *
 * Mirrors the BillingNotifications / EnrollmentNotifications pattern consumed
 * by resources/views/layouts/header.blade.php.
 */
class SecurityNotifications
{
    /**
     * @return array<int,array<string,mixed>>
     */
    public static function forUser(?User $user): array
    {
        if (! $user || $user->twoFactorEnabled()) {
            return [];
        }

        $mandatory = $user->twoFactorMandatory();

        return [[
            'id' => 'enable-2fa',
            'title' => 'Protect your account with two-factor authentication',
            'message' => $mandatory
                ? 'Two-factor authentication is required for your role. Set it up now in Profile Settings → Security.'
                : 'Add a second layer of security. Enable Google Authenticator in Profile Settings → Security.',
            'type' => 'security_2fa',
            'reference_id' => 0,
            'term_id' => null,
            'read' => false,
            'urgent' => $mandatory,
        ]];
    }
}
