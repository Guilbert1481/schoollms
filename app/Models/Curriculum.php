<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToSchool;

class Curriculum extends Model
{
    // Add this line to fix the "Table not found" error
    protected $table = 'curriculums'; 


    use BelongsToSchool;
    
    protected $fillable = [
        'school_id',
        'program_id',
        'name',
        'version',
        'terms_per_year',
        'has_summer_term',
        'effective_from',
        'effective_to',
        'is_active',
        'description',
    ];

    protected $casts = [
        'terms_per_year'   => 'integer',
        'has_summer_term'  => 'boolean',
        'is_active'        => 'boolean',
        'effective_from'   => 'date',
        'effective_to'     => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'curriculum_subjects')
            ->withPivot([
                'year_level',
                'semester',
                'is_core',
                'is_elective',
                'units'
            ])
            ->withTimestamps();
    }

    public function setting()
    {
        return $this->hasOne(CurriculumSetting::class);
    }

    
}
