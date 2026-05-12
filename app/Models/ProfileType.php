<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileType extends Model
{
    protected $fillable = [
        'code',
        'name'
    ];

    public function profiles()
    {
        return $this->hasMany(Profile::class);
    }
}