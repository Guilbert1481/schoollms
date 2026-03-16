<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = [
        'school_id',
        'name',
        'head_user_id'
    ];

    // Department belongs to a school
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    // Department head
    public function head()
    {
        return $this->belongsTo(User::class, 'head_user_id');
    }

    // Users under this department
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
