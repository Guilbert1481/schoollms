<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;

/**
 * Platform-wide (cross-school) configuration edited by the superadmin.
 * Currently: the online-checkout system fee.
 */
class ConfigurationController extends Controller
{
    public function index()
    {
        return view('superadmin.settings', [
            'systemFee'        => PlatformSetting::systemFee(),
            'defaultSystemFee' => PlatformSetting::DEFAULT_SYSTEM_FEE,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'system_fee' => ['required', 'numeric', 'min:0', 'max:100000'],
        ]);

        PlatformSetting::set('system_fee', round((float) $data['system_fee'], 2));

        return redirect()
            ->route('superadmin.settings.index')
            ->with('success', 'Platform settings saved.');
    }

    /** Placeholder — wired route, not yet implemented. */
    public function toggleMaintenance(Request $request)
    {
        return back()->with('info', 'Maintenance mode is not configured yet.');
    }

    /** Placeholder — wired route, not yet implemented. */
    public function backups()
    {
        return back()->with('info', 'Backups are not configured yet.');
    }
}
