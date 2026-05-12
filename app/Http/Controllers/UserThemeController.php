<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserThemeController extends Controller
{
    public function edit()
    {
        return view('user.theme');
    }

    public function update(Request $request)
{
    $themeColors = config('theme.colors');
    $user = auth()->user();

    // THE FIX: Convert "Soft Gray" to "soft-gray" to match your config keys
    $sidebarKey = \Illuminate\Support\Str::slug($request->sidebar_color);
    $headerKey = \Illuminate\Support\Str::slug($request->header_color);
    $kpiKey = \Illuminate\Support\Str::slug($request->kpi_accent_color);

    // 1. SAVE SIDEBAR HEX
    // Now it finds $themeColors['soft-gray']['hex'] correctly!
    $user->sidebar_color = $themeColors[$sidebarKey]['hex'] ?? '#1e293b';

    // 2. SAVE FULL IDENTITY
    $user->dashboard_identity = [
        'sidebar' => [
            'mode'  => $request->sidebar_mode,
            'style' => $request->sidebar_style,
            'color' => $sidebarKey, // Save the cleaned key
        ],
        'header' => [
            'mode'  => $request->header_mode,
            'style' => $request->header_style,
            'color' => $headerKey,
        ],
        'kpi' => [
            'card_style'      => $request->kpi_card_style,
            'border_style'    => $request->kpi_border_style,
            'background_tint' => $request->kpi_background_tint,
            'accent_color'    => $kpiKey,
        ],
    ];

    $user->save();

    // 3. CLEAR CACHE IMMEDIATELY
    // This ensures your new laptop doesn't show the old cached sidebar
    \Illuminate\Support\Facades\Artisan::call('view:clear');

    return back()->with('success', 'Theme updated successfully.');
}
}