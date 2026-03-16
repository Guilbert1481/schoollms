<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Only check if a user is logged in and belongs to a school
        // We skip this for Superadmins
        if ($user && $user->school && $user->role !== 'superadmin') {
            
            if ($user->school->isPlanExpired()) {
                // Redirect to a pricing or 'trial expired' page
                // Ensure you have a route named 'subscription.expired'
                return redirect()->route('subscription.expired')
                    ->with('error', 'Your trial period has ended. Please upgrade to continue.');
            }
        }

        return $next($request);
    }
}