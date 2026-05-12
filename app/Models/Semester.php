<?php
// app/Models/Semester.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Semester extends Model
{
    use HasFactory;

    protected $table = 'semesters';

    protected $fillable = [
        'school_id',
        'academic_year',
        'academic_year_id',
        'term',
        'name',
        'start_date',
        'end_date',
        'is_current',
        'is_active',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
        'is_current'  => 'boolean',
        'is_active'   => 'boolean',
    ];

    // FIXED: combine both attributes here
    protected $appends = ['can_activate', 'status'];

    public function getCanActivateAttribute(): bool
    {
        $today = now()->toDateString();

        // Cannot activate expired term
        if ($this->end_date->format('Y-m-d') < $today) {
            return false;
        }

        // Cannot activate if already active/current
        if ($this->is_active || $this->is_current) {
            return false;
        }

        return true;
    }

    public function getStatusAttribute(): string
    {
        $today = now()->toDateString();
        $end   = $this->end_date->format('Y-m-d');

        // Expired
        if ($end < $today) {
            return 'expired';
        }

        // Active (current OR active)
        if ($this->is_active || $this->is_current) {
            return 'active';
        }

        // Everything else
        return 'inactive';
    }

    public function isAcademicTerm(): bool
    {
        return in_array($this->term, [
            'first',
            'second',
            'third',
            'fourth',
            'summer',
        ]);
    }

    public function isSpecialTerm(): bool
    {
        return ! $this->isAcademicTerm();
    }

}
