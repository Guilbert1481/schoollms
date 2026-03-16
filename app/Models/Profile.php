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
}
