<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * --- ENSURE USER HAS SCHOOL MIDDLEWARE ---
 * PURPOSE: Prevents "homeless" users from accessing the LMS.
 * LOGIC: If a user is logged in but their school_id is NULL, they 
 * are forced to the onboarding wizard to create their professional space.
 */
class EnsureUserHasSchool
{
    public function handle(Request $request, Closure $next)
    {
        // 1. Check if user is logged in
        if (Auth::check()) {
            $user = Auth::user();

            // 2. If they don't have a school and aren't already on the setup page
            if (!$user->school_id && !$request->is('onboarding*')) {
                return redirect()->route('onboarding.wizard');
            }
        }

        return $next($request);
    }
}