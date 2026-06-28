<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Term extends Model
{
    use HasFactory;

    protected $table = 'terms';

    protected $fillable = [
        'school_id',
        'education_level',
        'academic_year',
        'enrollment_type',
        'academic_year_id',
        'term',
        'title',
        'name',
        'start_date',
        'end_date',
        'status',
        'is_current',
        'is_active',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
    ];

    protected $appends = ['can_activate', 'computed_status'];

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

        $otherActiveAcademic = self::query()
            ->where('academic_year_id', $this->academic_year_id)
            ->where('school_id', $this->school_id)
            ->where('id', '!=', $this->id)
            ->whereIn('term', ['first','second','third','fourth','summer'])
            ->where('status', 'active')
            ->exists();

        return ! $otherActiveAcademic;
    }

    public function isAcademicTerm(): bool
    {
        return in_array(strtolower($this->term), [
            'first', 'second', 'third', 'fourth', 'summer',
        ]);
    }

    public function isSpecialTerm(): bool
    {
        return ! $this->isAcademicTerm();
    }

    public function subjectOfferings()
    {
        return $this->hasMany(SubjectOffering::class);
    }
}