<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A host (subdomain or custom domain) that resolves to a school.
 *
 * This table is the source of truth for host-based tenant resolution, so it is
 * intentionally NOT scoped by BelongsToSchool — it is what *establishes* the
 * school for a request, before any tenant scope exists.
 */
class SchoolDomain extends Model
{
    protected $fillable = [
        'school_id',
        'host',
        'type',
        'is_primary',
        'is_verified',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_verified' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Hostnames are case-insensitive; store them normalised so the unique
        // index and lookups behave predictably.
        static::saving(function (SchoolDomain $domain) {
            $domain->host = strtolower(trim((string) $domain->host));
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
