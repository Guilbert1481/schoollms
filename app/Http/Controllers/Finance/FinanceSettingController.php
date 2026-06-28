<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceSetting;
use Illuminate\Http\Request;

class FinanceSettingController extends Controller
{
    public function edit()
    {
        $schoolId = (int) auth()->user()->school_id;
        $setting  = FinanceSetting::forSchool($schoolId);

        return view('finance.settings.edit', [
            'setting'     => $setting,
            'frequencies' => FinanceSetting::FREQUENCIES,
        ]);
    }

    public function update(Request $request)
    {
        $schoolId = (int) auth()->user()->school_id;
        $setting  = FinanceSetting::forSchool($schoolId);

        $data = $request->validate([
            'soa_frequency'           => ['required', 'in:'.implode(',', array_keys(FinanceSetting::FREQUENCIES))],
            'soa_generation_day'      => ['required', 'integer', 'min:1', 'max:28'],
            'auto_generate_soa'       => ['nullable', 'boolean'],
            'auto_invoice_on_billing' => ['nullable', 'boolean'],
            'invoice_due_days'        => ['required', 'integer', 'min:0', 'max:365'],
            'currency'                => ['required', 'string', 'max:10'],
            'soa_footer_note'         => ['nullable', 'string', 'max:2000'],
        ]);

        $data['auto_generate_soa']       = (bool) ($data['auto_generate_soa'] ?? false);
        $data['auto_invoice_on_billing'] = (bool) ($data['auto_invoice_on_billing'] ?? false);

        $setting->update($data);

        return back()->with('success', 'Finance settings saved.');
    }
}
