<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ModuleService
{
    /**
     * Check if a specific module is enabled for the logged-in user's institution.
     */
    public static function isEnabled($moduleName)
    {
        $user = Auth::user();
        
        // If not logged in or no school assigned, hide everything
        if (!$user || !$user->school_id) {
            return false;
        }

        // Check the school_modules table for this specific school
        return DB::table('school_modules')
            ->where('school_id', $user->school_id)
            ->where('module_name', $moduleName)
            ->where('is_enabled', true)
            ->exists();
    }


    public static function isEnabledForSchool($schoolId, $moduleName)
    {
        return DB::table('school_modules')
            ->where('school_id', $schoolId)
            ->where('module_name', $moduleName)
            ->where('is_enabled', true)
            ->exists();
    }
}