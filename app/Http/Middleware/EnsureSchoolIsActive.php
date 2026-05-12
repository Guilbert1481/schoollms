<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EnsureSchoolIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user && $user->role !== 'superadmin' && $user->school && !$user->school->is_active) {
            $slug = $user->school->slug;

            Log::info('EnsureSchoolIsActive: kicking out user', [
                'user_id'   => $user->id,
                'school_id' => $user->school->id,
                'slug'      => $slug,
                'ajax'      => $request->ajax() || $request->wantsJson(),
            ]);

            Auth::guard('web')->logout();

            $message = 'Your institution has been deactivated by the platform administrator. You have been automatically signed out.';

            // Persist via cookie so AJAX polls + any stray /login hits can recover the context.
            $cookies = [
                cookie('school_inactive_notice', $message, 10, null, null, false, false),
                cookie('school_inactive_slug', $slug ?: '', 10, null, null, false, false),
            ];

            // For AJAX requests, return a JSON redirect hint so JS can refresh.
            if ($request->ajax() || $request->wantsJson()) {
                $target = $slug
                    ? route('website.home', ['schoolSlug' => $slug])
                    : route('login');

                $response = response()->json([
                    'redirect' => $target,
                    'message'  => $message,
                ], 419);

                foreach ($cookies as $c) $response->withCookie($c);
                return $response;
            }

            $redirect = $slug
                ? redirect()->route('website.home', ['schoolSlug' => $slug])
                    ->with('school_inactive_notice', $message)
                    ->with('warning', $message)
                : redirect()->route('login')->withErrors(['email' => $message]);

            foreach ($cookies as $c) $redirect->withCookie($c);
            return $redirect;
        }

        return $next($request);
    }
}
