<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Modality extends Model
{
    protected $fillable = ['name','code'];

    public function schools()
    {
        return $this->belongsToMany(School::class, 'school_modalities');
    }
}