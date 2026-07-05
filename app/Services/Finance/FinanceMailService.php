<?php

namespace App\Services\Finance;

use App\Mail\InvoiceIssuedMail;
use App\Mail\StatementIssuedMail;
use App\Models\FinanceSetting;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\StudentEnrollment;
use App\Models\Student;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Emails invoice / SOA PDFs to the student and their guardians.
 *
 * Sending is opt-in per school (finance_settings.auto_send_invoices) and
 * idempotent: an invoice is only ever emailed once (invoices.emailed_at).
 * Every SOA_EVERY_N_INVOICES-th emailed invoice for an enrollment also
 * generates and emails the student's up-to-date Statement of Account.
 *
 * Mail goes out through the finance SMTP account (finance_settings.smtp_*)
 * when configured, otherwise through the application's default mailer
 * (the school-wide system SMTP applied at boot).
 */
class FinanceMailService
{
    /** Every Nth emailed invoice per enrollment also sends the SOA. */
    public const SOA_EVERY_N_INVOICES = 3;

    public function __construct(private readonly StatementService $statements)
    {
    }

    /** Email every due, not-yet-sent invoice for a school. Returns sent count. */
    public function sendDueInvoicesForSchool(int $schoolId): int
    {
        $setting = FinanceSetting::forSchool($schoolId);
        if (! $setting->auto_send_invoices) {
            return 0;
        }

        $due = Invoice::query()
            ->where('school_id', $schoolId)
            ->whereNull('emailed_at')
            ->whereNotNull('billing_date')
            ->whereDate('billing_date', '<=', now()->toDateString())
            ->where('balance', '>', 0.005)
            ->orderBy('billing_date')->orderBy('id')
            ->get();

        $sent = 0;
        foreach ($due as $invoice) {
            if ($this->sendInvoice($invoice, $setting)) {
                $sent++;
            }
        }

        return $sent;
    }

    /** Email the due, not-yet-sent invoices of one enrollment (used right after submission). */
    public function sendDueInvoicesForEnrollment(StudentEnrollment $enrollment): int
    {
        $setting = FinanceSetting::forSchool((int) $enrollment->school_id);
        if (! $setting->auto_send_invoices) {
            return 0;
        }

        $due = Invoice::query()
            ->where('school_id', (int) $enrollment->school_id)
            ->where('student_enrollment_id', $enrollment->id)
            ->whereNull('emailed_at')
            ->whereNotNull('billing_date')
            ->whereDate('billing_date', '<=', now()->toDateString())
            ->where('balance', '>', 0.005)
            ->orderBy('billing_date')->orderBy('id')
            ->get();

        $sent = 0;
        foreach ($due as $invoice) {
            if ($this->sendInvoice($invoice, $setting)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Render + email one invoice PDF to the student and guardians, stamp
     * emailed_at, and follow with the SOA when this was a 3rd/6th/… send.
     */
    public function sendInvoice(Invoice $invoice, FinanceSetting $setting): bool
    {
        $recipients = $this->recipientsForUser((int) $invoice->student_id, (int) $invoice->school_id);
        if (empty($recipients)) {
            Log::info('Invoice auto-send skipped — no valid recipient emails', ['invoice_id' => $invoice->id]);

            return false;
        }

        $invoice->loadMissing(['items', 'student', 'school', 'enrollment.term', 'enrollment.program']);
        $pdf = Pdf::loadView('finance.pdf.invoice', [
            'invoice' => $invoice,
            'school'  => $invoice->getRelation('school'),
        ])->setPaper('a4')->output();

        $mailer = $this->mailerFor($setting);

        $delivered = 0;
        foreach ($recipients as $email => $name) {
            try {
                Mail::mailer($mailer)->to($email)->send(new InvoiceIssuedMail($invoice, $pdf, $name, $setting));
                $delivered++;
            } catch (\Throwable $e) {
                Log::warning('Invoice mail recipient failed', [
                    'invoice_id' => $invoice->id,
                    'email'      => $email,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        if ($delivered === 0) {
            return false;
        }

        $invoice->forceFill(['emailed_at' => now()])->save();
        $this->maybeSendStatement($invoice, $setting, $mailer, $recipients);

        return true;
    }

    /** Every SOA_EVERY_N_INVOICES-th emailed invoice per enrollment also sends the SOA. */
    protected function maybeSendStatement(Invoice $invoice, FinanceSetting $setting, string $mailer, array $recipients): void
    {
        if (! $invoice->student_enrollment_id) {
            return;
        }

        $sentCount = Invoice::query()
            ->where('school_id', (int) $invoice->school_id)
            ->where('student_enrollment_id', $invoice->student_enrollment_id)
            ->whereNotNull('emailed_at')
            ->count();

        if ($sentCount === 0 || $sentCount % self::SOA_EVERY_N_INVOICES !== 0) {
            return;
        }

        try {
            $first = LedgerEntry::query()
                ->where('school_id', (int) $invoice->school_id)
                ->where('student_id', (int) $invoice->student_id)
                ->min('entry_date');
            $start = $first ? Carbon::parse($first) : now()->startOfMonth();

            $soa = $this->statements->generate(
                (int) $invoice->school_id,
                (int) $invoice->student_id,
                $start,
                now(),
                [
                    'student_enrollment_id' => $invoice->student_enrollment_id,
                    'academic_year_id'      => $invoice->academic_year_id,
                    'term_id'               => $invoice->term_id,
                    'frequency'             => 'on_demand',
                    'period_label'          => 'As of '.now()->format('M d, Y'),
                    'due_days'              => (int) $setting->invoice_due_days,
                    'force'                 => true,
                ]
            );
            if (! $soa) {
                return;
            }

            $soa->loadMissing(['student', 'school']);
            $pdf = Pdf::loadView('finance.pdf.statement_of_account', [
                'statement' => $soa,
                'school'    => $soa->getRelation('school'),
            ])->setPaper('a4')->output();

            $delivered = 0;
            foreach ($recipients as $email => $name) {
                try {
                    Mail::mailer($mailer)->to($email)->send(new StatementIssuedMail($soa, $pdf, $name, $setting));
                    $delivered++;
                } catch (\Throwable $e) {
                    Log::warning('SOA mail recipient failed', [
                        'soa_id' => $soa->id,
                        'email'  => $email,
                        'error'  => $e->getMessage(),
                    ]);
                }
            }

            if ($delivered > 0) {
                $soa->forceFill(['emailed_at' => now()])->save();
            }
        } catch (\Throwable $e) {
            // SOA delivery must never block the invoice send that triggered it.
            Log::warning('SOA auto-send failed', [
                'invoice_id' => $invoice->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    /** [email => display name] — the student's account email + guardian emails. */
    public function recipientsForUser(int $userId, int $schoolId): array
    {
        $recipients = [];

        $user = User::find($userId);
        if ($user && filter_var($user->email ?? null, FILTER_VALIDATE_EMAIL)) {
            $name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
            $recipients[$user->email] = $name !== '' ? $name : 'Student';
        }

        $student = Student::query()
            ->where('user_id', $userId)
            ->where('school_id', $schoolId)
            ->first();

        if ($student) {
            $guardians = $student->guardians()->whereIn('type', ['parent', 'guardian'])->get();
            $emergency = $student->guardians()->where('is_emergency_contact', true)->first();
            if ($emergency) {
                $guardians->push($emergency);
            }

            foreach ($guardians as $g) {
                $email = $g->email ?? null;
                if (filter_var($email, FILTER_VALIDATE_EMAIL) && ! isset($recipients[$email])) {
                    $name = trim(($g->first_name ?? '').' '.($g->last_name ?? ''));
                    $recipients[$email] = $name !== '' ? $name : 'Parent / Guardian';
                }
            }
        }

        return $recipients;
    }

    /**
     * Resolve the mailer to send through: a per-school finance SMTP mailer
     * (configured at runtime) when set, otherwise the application default.
     */
    protected function mailerFor(FinanceSetting $setting): string
    {
        if (! $setting->hasFinanceSmtp()) {
            return (string) config('mail.default', 'smtp');
        }

        $name = 'finance_smtp_'.$setting->school_id;
        config(["mail.mailers.{$name}" => [
            'transport'  => 'smtp',
            'host'       => $setting->smtp_host,
            'port'       => (int) ($setting->smtp_port ?: 587),
            'username'   => $setting->smtp_username,
            'password'   => $setting->smtp_password,
            'encryption' => $setting->smtp_encryption ?: null,
            'timeout'    => null,
        ]]);
        // Settings may change within one process (queue/scheduler) — drop any
        // cached transport so the fresh config is used.
        Mail::purge($name);

        return $name;
    }
}
