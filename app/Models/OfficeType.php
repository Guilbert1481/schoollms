<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficeType extends Model
{
    protected $fillable = [
        'name',
        'description'
    ];

    public function offices()
    {
        return $this->hasMany(Office::class);
    }
}