<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class FinanceSetting extends Model
{
    use Auditable; // admin-action log (Phase 4)

    /** Secrets stay out of the audit log — only the fact of a change shows. */
    protected array $auditExclude = ['smtp_password', 'imap_password'];

    public const FREQUENCIES = [
        'monthly' => 'Monthly',
        'quarterly' => 'Quarterly',
        'per_term' => 'Per Term / Semester',
        'annual' => 'Annually',
        'on_demand' => 'On Demand (manual only)',
    ];

    protected $fillable = [
        'school_id',
        'soa_frequency',
        'soa_generation_day',
        'auto_generate_soa',
        'auto_invoice_on_billing',
        'invoice_due_days',
        'currency',
        'soa_footer_note',
        'last_soa_run_at',
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',
        'mail_from_address',
        'mail_from_name',
        'imap_host',
        'imap_port',
        'imap_username',
        'imap_password',
        'imap_encryption',
        'auto_send_invoices',
    ];

    protected $casts = [
        'soa_generation_day' => 'integer',
        'auto_generate_soa' => 'boolean',
        'auto_invoice_on_billing' => 'boolean',
        'invoice_due_days' => 'integer',
        'last_soa_run_at' => 'datetime',
        'smtp_port' => 'integer',
        'imap_port' => 'integer',
        'smtp_password' => 'encrypted',
        'imap_password' => 'encrypted',
        'auto_send_invoices' => 'boolean',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the finance settings row for a school, creating it with sensible
     * defaults the first time it is requested.
     */
    public static function forSchool(int $schoolId): self
    {
        return static::firstOrCreate(
            ['school_id' => $schoolId],
            [
                'soa_frequency' => 'per_term',
                'soa_generation_day' => 1,
                'auto_generate_soa' => true,
                'auto_invoice_on_billing' => true,
                'invoice_due_days' => 7,
                'currency' => 'PHP',
            ]
        );
    }

    public function frequencyLabel(): string
    {
        return self::FREQUENCIES[$this->soa_frequency] ?? ucfirst((string) $this->soa_frequency);
    }

    /** Finance SMTP is fully configured (auto-send can use its own account). */
    public function hasFinanceSmtp(): bool
    {
        return filled($this->smtp_host) && filled($this->smtp_username);
    }
}
