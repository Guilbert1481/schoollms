<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders a route inside the standalone Scanner app shell instead of the full
 * portal chrome. Views opt in with `@extends($layout ?? 'layouts.app')`, so the
 * same OMR scan screen serves both the portal and the installed Scanner PWA
 * without being duplicated.
 */
class ScannerShell
{
    public function handle(Request $request, Closure $next): Response
    {
        View::share('layout', 'layouts.scanner');

        return $next($request);
    }
}
