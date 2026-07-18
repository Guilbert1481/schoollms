<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class EnrollmentSetting extends Model
{
    use Auditable; // admin-action log (Phase 4)

    protected $fillable = [
        'name',
        'enrollment_type_id',
        'academic_year_id',
        'term_id',
        'title',
        'description',
        'price',
        'currency',
        'cover_image',
        'instructor_title',
        'instructor_name',
        'course_details',
        'is_open',
        'start_date',
        'end_date',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $dates = [
        'start_date',
        'end_date',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class, 'term_id');
    }

    public function enrollmentType()
    {
        return $this->belongsTo(EnrollmentType::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getStatusAttribute()
    {
        $today = now()->startOfDay();
        $start = \Carbon\Carbon::parse($this->start_date)->startOfDay();
        $end = \Carbon\Carbon::parse($this->end_date)->endOfDay();

        if ($today->lt($start)) {
            return 'Upcoming';
        }

        if ($today->gte($start) && $today->lte($end)) {
            return 'Active';
        }

        return 'Closed';
    }
}
