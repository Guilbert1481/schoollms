<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\Concerns\ResolvesSettingsBand;
use App\Models\AttendanceSetting;
use App\Services\Attendance\AttendanceSettingsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Per-level attendance configuration, shared by the Principal (basic ed) and
 * the Dean (higher ed). The "band" is fixed by the route's default, not by user
 * input, and each route is role-gated — so neither role can configure the
 * other's levels. Thin: all work lives in AttendanceSettingsService.
 */
class AttendanceSettingsController extends Controller
{
    use ResolvesSettingsBand;

    public function index(Request $request, AttendanceSettingsService $service)
    {
        $band = $this->band($request);

        return view('settings.attendance', [
            'band' => $band,
            'levels' => $service->levelsWithSettings($this->schoolId(), $band),
            'updateRoute' => $this->updateRoute($band),
        ]);
    }

    public function update(Request $request, AttendanceSettingsService $service)
    {
        $band = $this->band($request);

        $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.capture_mode' => ['required', Rule::in([AttendanceSetting::CAPTURE_DAILY, AttendanceSetting::CAPTURE_SESSION])],
            'settings.*.late_after' => ['nullable', 'date_format:H:i'],
            'settings.*.grace_minutes' => ['nullable', 'integer', 'min:0', 'max:240'],
            'settings.*.expected_days_per_period' => ['nullable', 'integer', 'min:1', 'max:250'],
            'settings.*.qr_rotation_seconds' => ['nullable', 'integer', 'min:3', 'max:120'],
        ]);

        $count = $service->save($this->schoolId(), $band, $request->input('settings', []));

        return back()->with('success', "Attendance settings saved for {$count} level(s).");
    }

    /* ------------------------------------------------------------------ */

    private function updateRoute(string $band): string
    {
        return $band === 'basic'
            ? 'principal.settings.attendance.update'
            : 'dean.settings.attendance.update';
    }
}
