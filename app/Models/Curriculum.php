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
        'effective_from',
        'effective_to',
        'is_active',
        'description',
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
