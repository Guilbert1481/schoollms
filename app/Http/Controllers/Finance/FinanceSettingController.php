<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceSettingController extends Controller
{
    /** Finance Preferences — tabbed page (Preferences | Info). */
    public function preferences()
    {
        $schoolId = (int) auth()->user()->school_id;
        $setting  = FinanceSetting::forSchool($schoolId);

        return view('finance.settings.preferences', [
            'setting'     => $setting,
            'frequencies' => FinanceSetting::FREQUENCIES,
            'schoolName'  => DB::table('schools')->where('id', $schoolId)->value('school_name') ?: '—',
        ]);
    }

    public function updatePreferences(Request $request)
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

        return back()->with('success', 'Finance preferences saved.');
    }

    /*
    |--------------------------------------------------------------------------
    | Settings sub-pages (scaffolded — to be fleshed out per requirements)
    |--------------------------------------------------------------------------
    */

    public function transactionTypes()
    {
        return view('finance.settings.placeholder', [
            'title'       => 'Transaction Types',
            'icon'        => 'tags',
            'description' => 'The transaction types posted to the student ledger.',
            'items'       => ['Charge', 'Payment', 'Discount', 'Adjustment', 'Refund'],
        ]);
    }

    public function paymentMethods()
    {
        return view('finance.settings.placeholder', [
            'title'       => 'Payment Methods',
            'icon'        => 'credit-card',
            'description' => 'Accepted methods when recording a student payment.',
            'items'       => ['Cash', 'Bank Transfer', 'GCash', 'Card', 'Cheque'],
        ]);
    }

    public function penaltyRules()
    {
        return view('finance.settings.placeholder', [
            'title'       => 'Penalty Rules',
            'icon'        => 'alert-triangle',
            'description' => 'Late-payment penalties and surcharge rules applied to overdue balances.',
            'items'       => [],
        ]);
    }

    public function receiptNumbering()
    {
        return view('finance.settings.placeholder', [
            'title'       => 'OR & Receipt Numbering',
            'icon'        => 'hash',
            'description' => 'Official Receipt and acknowledgement-receipt numbering format and series.',
            'items'       => [],
        ]);
    }

    public function invoiceNumbering()
    {
        return view('finance.settings.placeholder', [
            'title'       => 'Invoice Numbering',
            'icon'        => 'receipt-text',
            'description' => 'Invoice number format, prefix, and running series.',
            'items'       => [],
        ]);
    }

    public function soaTemplate()
    {
        return view('finance.settings.placeholder', [
            'title'       => 'SOA Template',
            'icon'        => 'file-text',
            'description' => 'Statement of Account layout, header/footer, and content options.',
            'items'       => [],
        ]);
    }
}
