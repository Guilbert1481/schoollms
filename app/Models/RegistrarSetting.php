<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrarSetting extends Model
{
    protected $fillable = [
        'school_id',
        'parent_portal_auto_provision',
    ];

    protected $casts = [
        'parent_portal_auto_provision' => 'boolean',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the registrar settings row for a school, creating it with defaults
     * the first time it is requested (same pattern as FinanceSetting).
     */
    public static function forSchool(int $schoolId): self
    {
        return static::firstOrCreate(
            ['school_id' => $schoolId],
            ['parent_portal_auto_provision' => true]
        );
    }
}
