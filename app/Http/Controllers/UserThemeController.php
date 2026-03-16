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
        $themeColors = \Config::get('theme.colors');
        $allowedColors = implode(',', array_keys($themeColors));
        $accentColors = implode(',', [
            'slate','blue','emerald','purple','rose','amber','cyan','indigo','teal','fuchsia'
        ]);

        $request->validate([
            'sidebar_mode'   => 'required|in:dark,light',
            'sidebar_style'  => 'required|in:solid,gradient',
            'sidebar_color'  => 'required|in:' . $allowedColors,

            'header_mode'    => 'required|in:dark,light',
            'header_style'   => 'required|in:solid,gradient',
            'header_color'   => 'required|in:' . $allowedColors,

            'kpi_card_style'      => 'required|in:soft,glass,flat',
            'kpi_border_style'    => 'required|in:subtle,bold,none',
            'kpi_background_tint' => 'required|in:neutral,brand,dark',
            'kpi_accent_color'    => 'required|in:' . $accentColors,
        ]);

        $user = auth()->user();
        $identity = $user->dashboard_identity ?? [];

        // SAVE SIDEBAR
        $identity['sidebar'] = [
            'mode'  => $request->sidebar_mode,
            'style' => $request->sidebar_style,
            'color' => $request->sidebar_color,
        ];

        // SAVE HEADER
        $identity['header'] = [
            'mode'  => $request->header_mode,
            'style' => $request->header_style,
            'color' => $request->header_color,
        ];

        // SAVE KPI
        $identity['kpi'] = [
            'card_style'      => $request->kpi_card_style,
            'border_style'    => $request->kpi_border_style,
            'background_tint' => $request->kpi_background_tint,
            'accent_color'    => $request->kpi_accent_color,
        ];

        $user->dashboard_identity = $identity;
        $user->save();

        return back()->with('success', 'Theme updated successfully.');
    }
}