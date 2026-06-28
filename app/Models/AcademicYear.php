<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AcademicYear extends Model
{
    use HasFactory;

    protected $table = 'academic_years';

    protected $fillable = [
        'school_id',
        'education_level',
        'name',
        'start_date',
        'end_date',
        'is_active',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'is_active'  => 'boolean',
    ];

    protected $appends = [
        'computed_status',
        'can_activate',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function getComputedStatusAttribute()
{
    $today = now()->startOfDay();
    $start = \Carbon\Carbon::parse($this->start_date)->startOfDay();
    $end   = \Carbon\Carbon::parse($this->end_date)->endOfDay();

    if ($today->lt($start)) {
        return 'upcoming';
    }

    if ($today->gte($start) && $today->lte($end)) {
        return 'active';
    }

    return 'closed';
}

    public function getCanActivateAttribute(): bool
    {
        if ($this->end_date < now()) {
            return false;
        }

        $otherActive = self::query()
            ->where('school_id', $this->school_id)
            ->where('id', '!=', $this->id)
            ->where('is_active', true)
            ->exists();

        return ! $otherActive;
    }
}