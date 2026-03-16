<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\BelongsToSchool;


class Announcement extends Model
{
    use HasFactory, SoftDeletes, BelongsToSchool;

    protected $fillable = [
        'title',
        'content',
        'created_by',
        'school_id',
        'priority_level',
        'published_at',
        'expires_at',
        'super_priority_expires_at',
    ];



    protected $casts = [
        'published_at' => 'datetime',
        'expires_at'   => 'datetime',
        'super_priority_expires_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Who created the announcement
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }


    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    // Only published announcements
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
                     ->where('published_at', '<=', now());
    }

    // Not expired
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>=', now());
        });
    }

    public function acknowledgements()
    {
        return $this->hasMany(AnnouncementAcknowledgement::class);
    }

    public function scopeActiveSuperPriority($query)
    {
        return $query->where('priority_level', 'super')
                    ->where('super_priority_expires_at', '>', now());
    }

    public function assignments()
    {
        return $this->hasMany(AnnouncementAssignment::class);
    }



}
