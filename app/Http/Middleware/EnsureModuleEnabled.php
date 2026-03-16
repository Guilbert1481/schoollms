<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, $next, $module)
    {
        if (!\App\Services\ModuleService::isEnabled($module)) {
            // If the module is off, we act like the page doesn't exist
            abort(404, "This feature is currently disabled by your administrator.");
        }

        return $next($request);
    }
}
