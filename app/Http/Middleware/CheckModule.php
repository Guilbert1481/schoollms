<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * --- CHECK MODULE MIDDLEWARE ---
 * PURPOSE: Validates if the current school has a specific feature enabled.
 * LOGIC: It looks at the school_modules table for the current user's school_id.
 * If the module is missing or 'is_enabled' is false, it blocks access.
 */
class CheckModule
{
    public function handle(Request $request, Closure $next, $moduleName)
    {
        $user = $request->user();

        // 1. If no school is assigned, we can't check modules
        if (!$user || !$user->school_id) {
            abort(403, 'Unauthorized: No school context found.');
        }

        // 2. Check the database for this specific module
        $isEnabled = DB::table('school_modules')
            ->where('school_id', $user->school_id)
            ->where('module_name', $moduleName)
            ->where('is_enabled', true)
            ->exists();

        if (!$isEnabled) {
            // If it's a freelancer or school without the module, send them back
            return redirect()->route('admin.dashboard')
                ->with('error', "The " . ucfirst($moduleName) . " module is not enabled for your account.");
        }

        return $next($request);
    }
}