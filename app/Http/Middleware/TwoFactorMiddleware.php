<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // 1. Only proceed if a user is logged in
        if ($user) {

            // 2. Role-Based Access Denied (Optional safety layer)
            // If they are hitting a superadmin route but aren't a superadmin, abort.
            if ($request->is('superadmin/*') && $user->role !== 'superadmin') {
                abort(403, 'Access Denied: Insufficient Clearance.');
            }
            
            // 3. Check if the user has a 2FA secret set in the database
            // 4. And check if they have NOT yet verified for this session
            if ($user->google2fa_secret && !session('2fa_verified')) {
                
                // 5. Prevent redirect loops: Allow access to verification, 
                // recovery, and logout routes.
                if (!$request->is('2fa/*') && !$request->routeIs('logout')) {
                    return redirect()->route('2fa.verify');
                }
            }
        }

        return $next($request);
    }
}