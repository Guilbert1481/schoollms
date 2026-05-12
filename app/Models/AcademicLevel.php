<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'sequence_order',
        'type'
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships (Optional Future Expansion)
    |--------------------------------------------------------------------------
    */

    public function curriculums()
    {
        return $this->hasMany(Curriculum::class);
    }
}
