<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Show the Superadmin Dashboard Overview
     */
    public function index()
    {
        return view('superadmin.dashboard');
    }

    /**
     * Show System Health and Infrastructure Status
     */
    public function status()
    {
        // Gather system metrics
        $status = [
            'server_load'    => function_exists('sys_getloadavg') ? sys_getloadavg()[0] : 'N/A',
            'disk_space'     => round(disk_free_space("/") / 1024 / 1024 / 1024, 2), // GB
            'database'       => DB::connection()->getPdo() ? 'Connected' : 'Error', //
            'active_schools' => School::where('is_active', true)->count(), //
            'total_users'    => User::count(), //
            'cache_driver'   => config('cache.default'),
        ];

        return view('superadmin.status', compact('status'));
    }

    /**
     * Global Maintenance Toggle
     */
    public function toggleMaintenance(Request $request)
    {
        // Logic to enable/disable system-wide access
        // This usually interacts with an 'options' or 'settings' table
        return back()->with('success', 'Maintenance mode updated.');
    }
}