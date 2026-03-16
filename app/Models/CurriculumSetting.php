<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToSchool;

class CurriculumSetting extends Model
{

        use BelongsToSchool;
        
    protected $fillable = [
        'curriculum_id',
        'enrollment_mode',
        'enforce_prerequisites',
        'allow_core_override',
        'allow_cross_year',
        'max_units',
        'min_units',
        'auto_assign_core',
        'strict_year_level',
    ];

    public function curriculum()
    {
        return $this->belongsTo(Curriculum::class);
    }
}

