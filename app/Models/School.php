<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        if (! $this->plan_expires_at) {
            return false;
        }

        return Carbon::now()->gt($this->plan_expires_at);
    }

    /**
     * Calculate remaining trial days.
     */
    public function getTrialDaysLeftAttribute(): int
    {
        if (! $this->plan_expires_at) {
            return 0;
        }

        $days = now()->diffInDays($this->plan_expires_at, false);

        return $days > 0 ? (int) $days : 0;
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Hosts (subdomains / custom domains) that resolve to this school.
     */
    public function domains()
    {
        return $this->hasMany(SchoolDomain::class);
    }

    /**
     * The canonical host for this school, used to build absolute URLs and to
     * bounce a user back to their own tenant. Prefers an explicit primary
     * domain row, then any domain row, then the legacy `domain` column, and
     * finally the "<slug>.<base-domain>" convention.
     */
    public function primaryHost(): ?string
    {
        // 1. An explicit canonical host (e.g. the school's own custom domain).
        $host = $this->domains()->where('is_primary', true)->value('host')
            ?? $this->domains()->orderBy('id')->value('host');

        if ($host) {
            return $host;
        }

        // 2. The base-domain subdomain built from the slug — always a host we
        //    serve, so it is the safe default target for redirects.
        $base = config('tenancy.primary_base_domain');
        if ($base && $this->slug) {
            return $this->slug.'.'.$base;
        }

        // 3. Legacy single custom-domain column (last resort).
        if (! empty($this->domain)) {
            return $this->domain;
        }

        return null;
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

    /**
     * The per-school role subscriptions (which catalog roles this school may
     * assign users to). See {@see SchoolRole} and config/roles.php.
     */
    public function roleSubscriptions()
    {
        return $this->hasMany(SchoolRole::class);
    }

    /**
     * The enabled role keys for this school, intersected with the current
     * catalog so a stale/removed key never leaks through. If the school has no
     * subscription rows at all (legacy), fall back to the full catalog so the
     * User Management page keeps working exactly as before.
     *
     * @return list<string>
     */
    public function enabledRoleKeys(): array
    {
        $rows = SchoolRole::query()
            ->where('school_id', $this->getKey())
            ->pluck('is_enabled', 'role_key');

        if ($rows->isEmpty()) {
            return SchoolRole::catalogKeys();
        }

        // Keep only rows whose toggle is on, then intersect with the current
        // catalog so a stale/removed key never leaks through.
        $enabled = $rows->filter()->keys()->all();

        return array_values(array_intersect($enabled, SchoolRole::catalogKeys()));
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
