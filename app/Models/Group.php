<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = [
        'school_id',
        'name',
        'type',
        'description'
    ];

    public function members()
    {
        return $this->hasMany(GroupMember::class);
    }
}