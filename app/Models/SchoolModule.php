<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolModule extends Model
{
    protected $table = 'school_modules';

    protected $fillable = [
        'school_id',
        'module_id',
        'is_enabled',
    ];

    /**
     * Relationship: This entry belongs to a module.
     */
    public function module()
    {
        return $this->belongsTo(\App\Models\Module::class);
    }

    /**
     * Relationship: This entry belongs to a school.
     */
    public function school()
    {
        return $this->belongsTo(\App\Models\School::class);
    }
}
