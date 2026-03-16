<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * This fixes the MassAssignmentException error.
     */
    protected $fillable = [
        'content',
        'author',
        'theme',
        'display_duration',
        'is_active',
        'activated_at',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'activated_at' => 'datetime',
        'is_active' => 'boolean',
    ];
}