<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupMember extends Model
{
    protected $fillable = [
        'group_id',
        'profile_id'
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }
}