<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = [
        'name',
        'category',
    ];

    /**
     * Relationship: A module can belong to many schools
     * through the school_modules table.
     */
    public function schoolModules()
    {
        return $this->hasMany(\App\Models\SchoolModule::class);
    }
}
