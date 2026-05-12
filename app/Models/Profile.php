<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'middle_name',
        'last_name',
        'gender',
        'birthday',
        'contact_number',
        'address',
        'city',
        'province',
        'country',
        'guardian_name',
        'guardian_contact',
        'nationality',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function trainee()
    {
        return $this->hasOne(\App\Models\Trainee::class);
    }

public function student()
{
    return $this->hasOne(Student::class, 'user_id', 'user_id');
}

public function profileType()
{
    return $this->belongsTo(ProfileType::class);
}


public function groups()
{
    return $this->belongsToMany(
        Group::class,
        'group_members',
        'profile_id',
        'group_id'
    );
}

public function staff()
    {
        return $this->hasOne(Staff::class);

    }



}
