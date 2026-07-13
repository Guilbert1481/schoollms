<?php

if (!function_exists('fileUrl')) {
    function fileUrl($path)
    {
        if (!$path) return null;
        return asset('storage/' . $path);
    }
}

if (!function_exists('current_school')) {
    /**
     * The school resolved from the request Host header for this request,
     * or null on the platform/central host. Set by ResolveSchoolFromHost.
     */
    function current_school(): ?\App\Models\School
    {
        return app(\App\Support\Tenancy\CurrentSchool::class)->get();
    }
}