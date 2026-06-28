<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceSetting extends Model
{
    public const FREQUENCIES = [
        'monthly'   => 'Monthly',
        'quarterly' => 'Quarterly',
        'per_term'  => 'Per Term / Semester',
        'annual'    => 'Annually',
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
    ];

    protected $casts = [
        'soa_generation_day'      => 'integer',
        'auto_generate_soa'       => 'boolean',
        'auto_invoice_on_billing' => 'boolean',
        'invoice_due_days'        => 'integer',
        'last_soa_run_at'         => 'datetime',
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
                'soa_frequency'           => 'per_term',
                'soa_generation_day'      => 1,
                'auto_generate_soa'       => true,
                'auto_invoice_on_billing' => true,
                'invoice_due_days'        => 7,
                'currency'                => 'PHP',
            ]
        );
    }

    public function frequencyLabel(): string
    {
        return self::FREQUENCIES[$this->soa_frequency] ?? ucfirst((string) $this->soa_frequency);
    }
}
