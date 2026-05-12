<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountAccess extends Model
{
    protected $table = 'account_access';

    protected $fillable = [
        'user_id',
        'role_id',
        'person_id',
        'office_id',
        'role_snapshot',
        'start_date',
        'end_date',
        'assigned_by',
        'remarks',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'person_id');
    }
}
