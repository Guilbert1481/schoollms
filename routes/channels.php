<?php

use App\Models\Games\GameSession;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Presence channel for a live Tug-of-War match. Authorization is the
 * tenant-isolation gate: GameSession::find is school-scoped (BelongsToSchool),
 * so a session from another school resolves to null and access is denied. Only
 * the host or a joined player of the session may subscribe.
 */
Broadcast::channel('game.{sessionId}', function ($user, $sessionId) {
    $session = GameSession::find($sessionId);
    if (! $session) {
        return false;
    }

    $isMember = (int) $session->host_id === (int) $user->id
        || $session->players()->where('user_id', $user->id)->exists();

    if (! $isMember) {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'Player',
    ];
});
