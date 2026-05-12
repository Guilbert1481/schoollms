<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class College extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'school_id',
        'description',
        'is_active',
        'dean_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * A College has many Programs
     */
    public function programs()
    {
        return $this->hasMany(Program::class);
    }

    public function dean()
    {
        return $this->belongsTo(User::class, 'dean_id');
    }
}