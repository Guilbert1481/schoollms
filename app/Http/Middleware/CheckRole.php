<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role)
    {
        // 1. Check if the user is even logged in
        if (!$request->user()) {
            return redirect()->route('login');
        }

        // 2. Compare the user's role to the required role
        // Note: This assumes you have a 'role' column in your 'users' table
        if ($request->user()->role !== $role) {
            abort(403, 'You do not have the correct permissions to access this page.');
        }

        // 3. If everything is correct, let the request continue
        return $next($request);
    }
}
