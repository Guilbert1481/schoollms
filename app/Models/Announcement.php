<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\BelongsToSchool;
use App\Models\AnnouncementAssignment;
use App\Models\Profile;
use App\Models\GroupMember;


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


    

public function getSentCount()
{
    $assignments = AnnouncementAssignment::where('announcement_id', $this->id)->get();

    $profileIds = collect();

    foreach ($assignments as $assignment) {

        // OFFICE → profiles.office_id
                if ($assignment->assignable_type == 'office') {

            $userIds = DB::table('account_access')
                ->where('office_id', $assignment->assignable_id)
                ->whereDate('start_date', '<=', now())
                ->where(function ($q) {
                    $q->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', now());
                })
                ->pluck('user_id');

            $profiles = Profile::whereIn('user_id', $userIds)
                ->pluck('id');

            $profileIds = $profileIds->merge($profiles);
        }

        // PROGRAM → enrollments.student_id → profiles.user_id
        if ($assignment->assignable_type == 'program') {
            $studentIds = DB::table('enrollments')
                ->where('program_id', $assignment->assignable_id)
                ->pluck('student_id');

            $profiles = Profile::whereIn('user_id', $studentIds)
                ->pluck('id');

            $profileIds = $profileIds->merge($profiles);
        }

        // GROUP
        if ($assignment->assignable_type == 'group') {
            $profiles = DB::table('group_members')
                ->where('group_id', $assignment->assignable_id)
                ->pluck('profile_id');

            $profileIds = $profileIds->merge($profiles);
        }
    }

    return $profileIds->unique()->count();
}

public function getStatus()
{
    if (!$this->published_at) return 'Draft';

    if ($this->expires_at && now()->gt($this->expires_at)) {
        return 'Expired';
    }

    if (now()->lt($this->published_at)) {
        return 'Scheduled';
    }

    return 'Active';
}



}
