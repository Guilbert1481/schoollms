<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramType extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Each Program Type belongs to a School (Multi-tenant)
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    // If you will connect this to programs table later
    public function programs()
    {
        return $this->hasMany(Program::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes (Multi-Tenant Safety)
    |--------------------------------------------------------------------------
    */

    // Scope to current school
    public function scopeForSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    // Only active types
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
