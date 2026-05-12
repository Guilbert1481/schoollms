<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_name',
        'domain',
        'code',
        'slug',
        'requires_admission_test',
        'primary_color',
        'type',
        'country',
        'is_active',
        'plan_name',
        'pricing_id',
        'plan_expires_at',
        'contact_person',   
        'mobile_number',    
        'email',            
        'address',          
    ];

    /**
     * Merge all your casts into this single array
     */
    protected $casts = [
        'plan_expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function isPlanExpired(): bool
    {
        if (!$this->plan_expires_at) {
            return false;
        }

        return Carbon::now()->gt($this->plan_expires_at);
    }


    /**
 * Calculate remaining trial days.
 */
    public function getTrialDaysLeftAttribute(): int
    {
        if (!$this->plan_expires_at) {
            return 0;
        }
        
        $days = now()->diffInDays($this->plan_expires_at, false);
        
        return $days > 0 ? (int)$days : 0;
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * The modules that belong to the school (Many-to-Many).
     */
    public function modules()
    {
        return $this->belongsToMany(Module::class, 'school_modules')
                    ->withPivot('expires_at', 'is_enabled')
                    ->withTimestamps()
                    ->distinct(); // <--- This forces the modal to show each module only once
    }

    public function colleges()
    {
        return $this->hasMany(College::class);
    }

    public function modalities()
    {
        return $this->belongsToMany(Modality::class, 'school_modalities');
    }

    public function banks()
{
    return $this->hasMany(Bank::class);
}

    public function certificateEvents()
    {
        return $this->hasMany(CertificateEvent::class);
    }

}