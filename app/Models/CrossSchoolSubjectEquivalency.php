<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Maps a subject offered by a host school to its equivalent at the
 * cross-enrollee's home school. Used when validating that a cross-enrollee
 * can take a host-school subject in lieu of a home-school subject.
 */
class CrossSchoolSubjectEquivalency extends Model
{
    use HasFactory;

    protected $table = 'cross_school_subject_equivalencies';

    protected $fillable = [
        'home_school_id',
        'host_school_id',
        'home_subject_id',
        'host_subject_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function homeSchool()
    {
        return $this->belongsTo(School::class, 'home_school_id');
    }

    public function hostSchool()
    {
        return $this->belongsTo(School::class, 'host_school_id');
    }

    public function homeSubject()
    {
        return $this->belongsTo(Subject::class, 'home_subject_id');
    }

    public function hostSubject()
    {
        return $this->belongsTo(Subject::class, 'host_subject_id');
    }
}
