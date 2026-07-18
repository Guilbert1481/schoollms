<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\Concerns\ResolvesSettingsBand;
use App\Models\GradingSetting;
use App\Services\Grading\GradingSettingsService;
use App\Support\AcademicBand;
use App\Support\EducationLevels;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Per-level grading configuration, shared by the Principal (basic ed) and the
 * Dean (higher ed). The band is fixed by the route and each route is role-gated,
 * so neither role can configure the other's levels. Thin: all work lives in
 * GradingSettingsService.
 */
class GradingSettingsController extends Controller
{
    use ResolvesSettingsBand;

    public function index(Request $request, GradingSettingsService $service)
    {
        $band = $this->band($request);
        $levels = $service->levelsWithSettings($this->schoolId(), $band);

        // Basic ed only: fold the (long) list of grade levels behind the shared
        // education-level tabs — offered stage groups (Preschool / Elementary /
        // …). Higher ed keeps the flat list, so its tab collection stays empty.
        $stageGroups = collect();
        $activeLevelId = 0;
        $showAll = true;

        if ($band === AcademicBand::BASIC) {
            $stageGroups = EducationLevels::basicStageGroups();

            $requested = $request->query('level');
            if ($requested !== null && $requested !== 'all'
                && $stageGroups->contains(fn ($g) => (int) $g->id === (int) $requested)) {
                $activeLevelId = (int) $requested;
                $showAll = false;

                $bucket = EducationLevels::bucketBasicLevels($levels->pluck('level'));
                $levels = $levels
                    ->filter(fn ($row) => ($bucket[$row['level']->id] ?? null) === $activeLevelId)
                    ->values();
            }
        }

        return view('settings.grading', [
            'band' => $band,
            'levels' => $levels,
            'stageGroups' => $stageGroups,
            'activeLevelId' => $activeLevelId,
            'showAll' => $showAll,
            'indexRoute' => $this->indexRoute($band),
            'updateRoute' => $this->updateRoute($band),
            'scales' => GradingSetting::SCALES,
        ]);
    }

    public function update(Request $request, GradingSettingsService $service)
    {
        $band = $this->band($request);

        $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.scale_type' => ['required', Rule::in(GradingSetting::SCALES)],
            'settings.*.passing_mark' => ['required', 'numeric', 'min:0', 'max:100'],
            'settings.*.attendance_weight' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'settings.*.components' => ['nullable', 'array'],
            'settings.*.components.*.name' => ['nullable', 'string', 'max:100'],
            'settings.*.components.*.weight' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $count = $service->save($this->schoolId(), $band, $request->input('settings', []));

        return back()->with('success', "Grading settings saved for {$count} level(s).");
    }

    private function indexRoute(string $band): string
    {
        return $band === 'basic'
            ? 'principal.settings.grading'
            : 'dean.settings.grading';
    }

    private function updateRoute(string $band): string
    {
        return $band === 'basic'
            ? 'principal.settings.grading.update'
            : 'dean.settings.grading.update';
    }
}
