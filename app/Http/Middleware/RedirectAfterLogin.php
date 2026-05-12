<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RedirectAfterLogin
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && $request->is('/')) {

            $user = auth()->user();

            if ($user->role === 'superadmin') {
                return redirect()->route('superadmin.dashboard');
            }

            if ($user->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }

            if (in_array($user->role, ['admission', 'admission_manager'], true)) {
                return redirect()->route('staff.admissions.dashboard');
            }

            if ($user->role === 'guidance_counselor') {
                return redirect()->route('guidance_counselor.dashboard');
            }
        }

        return $next($request);
    }
}
