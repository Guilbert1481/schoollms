<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deadline extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'school_id',
        'title',
        'content',
        'type',
        'due_date',
        'requires_submission',
        'allow_late_submission',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'requires_submission' => 'boolean',
        'allow_late_submission' => 'boolean',
        'active' => 'boolean',
    ];

    public function assignments()
    {
        return $this->hasMany(DeadlineAssignment::class);
    }

    public function userCompletions()
    {
        return $this->hasMany(\App\Models\DeadlineUserCompletion::class);
    }

    public function deadline()
    {
        return $this->belongsTo(\App\Models\Deadline::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}