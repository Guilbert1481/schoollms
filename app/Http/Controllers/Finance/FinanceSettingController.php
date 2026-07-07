<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class FinanceSettingController extends Controller
{
    /**
     * The former standalone settings sub-pages, now tabs inside Finance
     * Preferences. Key = tab slug (also the ?tab= deep link).
     */
    public const PREFERENCE_SECTIONS = [
        'payment-methods' => [
            'label'       => 'Payment Methods',
            'icon'        => 'credit-card',
            'description' => 'Accepted methods when recording a student payment.',
            'items'       => ['Cash', 'Bank Transfer', 'GCash', 'Card', 'Cheque'],
        ],
        'penalty-rules' => [
            'label'       => 'Penalty Rules',
            'icon'        => 'alert-triangle',
            'description' => 'Late-payment penalties and surcharge rules applied to overdue balances.',
            'items'       => [],
        ],
        'receipt-numbering' => [
            'label'       => 'OR & Receipt Numbering',
            'icon'        => 'hash',
            'description' => 'Official Receipt and acknowledgement-receipt numbering format and series.',
            'items'       => [],
        ],
        'invoice-numbering' => [
            'label'       => 'Invoice Numbering',
            'icon'        => 'receipt-text',
            'description' => 'Invoice number format, prefix, and running series.',
            'items'       => [],
        ],
        'soa-template' => [
            'label'       => 'SOA Template',
            'icon'        => 'file-text',
            'description' => 'Statement of Account layout, header/footer, and content options.',
            'items'       => [],
        ],
    ];

    /** Finance Preferences — tabbed page (Preferences | section tabs | Info). */
    public function preferences(Request $request)
    {
        $schoolId = (int) auth()->user()->school_id;
        $setting  = FinanceSetting::forSchool($schoolId);

        $tab = (string) $request->query('tab', 'preferences');
        if (! in_array($tab, array_merge(['preferences', 'info'], array_keys(self::PREFERENCE_SECTIONS)), true)) {
            $tab = 'preferences';
        }

        return view('finance.settings.preferences', [
            'setting'     => $setting,
            'frequencies' => FinanceSetting::FREQUENCIES,
            'schoolName'  => DB::table('schools')->where('id', $schoolId)->value('school_name') ?: '—',
            'sections'    => self::PREFERENCE_SECTIONS,
            'activeTab'   => $tab,
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

    /** Email — finance SMTP/IMAP configuration + invoice/SOA auto-send switch. */
    public function email()
    {
        $schoolId = (int) auth()->user()->school_id;
        $setting  = FinanceSetting::forSchool($schoolId);

        return view('finance.settings.email', [
            'setting'    => $setting,
            'schoolName' => DB::table('schools')->where('id', $schoolId)->value('school_name') ?: '—',
        ]);
    }

    public function updateEmail(Request $request)
    {
        $schoolId = (int) auth()->user()->school_id;
        $setting  = FinanceSetting::forSchool($schoolId);

        $data = $request->validate([
            'smtp_host'          => ['nullable', 'string', 'max:255'],
            'smtp_port'          => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_username'      => ['nullable', 'string', 'max:255'],
            'smtp_password'      => ['nullable', 'string', 'max:500'],
            'smtp_encryption'    => ['nullable', 'in:tls,ssl'],
            'mail_from_address'  => ['nullable', 'email', 'max:255'],
            'mail_from_name'     => ['nullable', 'string', 'max:255'],
            'imap_host'          => ['nullable', 'string', 'max:255'],
            'imap_port'          => ['nullable', 'integer', 'min:1', 'max:65535'],
            'imap_username'      => ['nullable', 'string', 'max:255'],
            'imap_password'      => ['nullable', 'string', 'max:500'],
            'imap_encryption'    => ['nullable', 'in:tls,ssl'],
            'auto_send_invoices' => ['nullable', 'boolean'],
        ]);

        // Blank password fields mean "keep the stored one" — never wipe on save.
        foreach (['smtp_password', 'imap_password'] as $secret) {
            if (($data[$secret] ?? '') === '' || $data[$secret] === null) {
                unset($data[$secret]);
            }
        }

        $data['auto_send_invoices'] = (bool) ($data['auto_send_invoices'] ?? false);

        $setting->update($data);

        return back()->with('success', 'Finance email settings saved.');
    }

    /**
     * Send a test email through the saved finance SMTP account to the
     * signed-in finance manager. Uses the stored settings — save first.
     */
    public function testSmtp()
    {
        $schoolId = (int) auth()->user()->school_id;
        $setting  = FinanceSetting::forSchool($schoolId);

        if (! $setting->hasFinanceSmtp()) {
            return back()->with('error', 'Save the SMTP host and username first, then test.');
        }

        $schoolName = DB::table('schools')->where('id', $schoolId)->value('school_name') ?: 'your school';
        $to         = (string) auth()->user()->email;

        $mailer = 'finance_smtp_test_'.$schoolId;
        config(["mail.mailers.{$mailer}" => [
            'transport'  => 'smtp',
            'host'       => $setting->smtp_host,
            'port'       => (int) ($setting->smtp_port ?: 587),
            'username'   => $setting->smtp_username,
            'password'   => $setting->smtp_password,
            'encryption' => $setting->smtp_encryption ?: null,
            'timeout'    => 15,
        ]]);
        Mail::purge($mailer);

        try {
            Mail::mailer($mailer)->raw(
                'This is a test email from the '.$schoolName.' finance email settings. '
                .'If you are reading this, the SMTP configuration works.',
                function ($message) use ($to, $setting, $schoolName) {
                    $message->to($to)->subject('Finance email test — SMTP is working');
                    if (filled($setting->mail_from_address)) {
                        $message->from($setting->mail_from_address, $setting->mail_from_name ?: $schoolName.' — Finance Office');
                    }
                }
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'SMTP test failed: '.Str::limit($e->getMessage(), 300));
        }

        return back()->with('success', 'Test email sent to '.$to.' — check that inbox.');
    }

    /**
     * Verify the saved IMAP host, port and credentials with a real
     * connect + LOGIN (no mailbox is read).
     */
    public function testImap()
    {
        $schoolId = (int) auth()->user()->school_id;
        $setting  = FinanceSetting::forSchool($schoolId);

        if (! filled($setting->imap_host) || ! filled($setting->imap_username) || ! filled($setting->imap_password)) {
            return back()->with('error', 'Save the IMAP host, username and password first, then test.');
        }

        $host   = (string) $setting->imap_host;
        $port   = (int) ($setting->imap_port ?: 993);
        $remote = ($setting->imap_encryption ? 'ssl://' : 'tcp://').$host.':'.$port;

        try {
            $fp = @stream_socket_client($remote, $errno, $errstr, 10);
            if (! $fp) {
                throw new \RuntimeException($errstr ?: 'Could not connect ('.$errno.').');
            }
            stream_set_timeout($fp, 10);

            $greeting = (string) fgets($fp);
            if (! str_contains($greeting, '* OK')) {
                fclose($fp);
                throw new \RuntimeException('Unexpected server greeting: '.trim($greeting));
            }

            $quote = fn (string $v): string => '"'.addcslashes($v, "\\\"").'"';
            fwrite($fp, 'A1 LOGIN '.$quote((string) $setting->imap_username).' '.$quote((string) $setting->imap_password)."\r\n");

            $result = '';
            while (($line = fgets($fp)) !== false) {
                if (str_starts_with($line, 'A1 ')) {
                    $result = trim($line);
                    break;
                }
            }
            fwrite($fp, "A2 LOGOUT\r\n");
            fclose($fp);

            if (! str_starts_with($result, 'A1 OK')) {
                throw new \RuntimeException($result !== '' ? $result : 'The server closed the connection during login.');
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'IMAP test failed: '.Str::limit($e->getMessage(), 300));
        }

        return back()->with('success', 'IMAP connection and login OK ('.$host.':'.$port.').');
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
}
