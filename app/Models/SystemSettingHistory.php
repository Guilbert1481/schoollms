<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSettingHistory extends Model
{
    protected $table = 'system_settings_history';

    protected $fillable = [
        'school_id',
        'updated_by',
        'old_values',
        'new_values',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}