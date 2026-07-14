<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A per-school role subscription: one row per (school, role_key) the school is
 * entitled to. Mirrors {@see SchoolModule}. The catalog of valid role keys
 * lives in config/roles.php.
 *
 * This is an entitlement/UX gate (which roles a school can assign users to);
 * it is not access control — that stays with the `role:` route middleware.
 */
class SchoolRole extends Model
{
    protected $table = 'school_roles';

    protected $fillable = [
        'school_id',
        'role_key',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Catalog helpers (read config/roles.php — the fixed platform role list)
    |--------------------------------------------------------------------------
    */

    /** @return array<string, array{label: string, segment: string}> */
    public static function catalog(): array
    {
        return (array) config('roles.catalog', []);
    }

    /** All valid, subscribable role keys. @return list<string> */
    public static function catalogKeys(): array
    {
        return array_keys(static::catalog());
    }

    /** Role keys pre-checked when a new partner is first added. @return list<string> */
    public static function defaultKeys(): array
    {
        return array_values(array_intersect(
            (array) config('roles.defaults', []),
            static::catalogKeys(),
        ));
    }

    /**
     * The catalog grouped by segment, in the configured display order, for
     * rendering the Add/Edit Partner checklist.
     *
     * @return array<string, array<string, array{label: string, segment: string}>>
     */
    public static function groupedCatalog(): array
    {
        $catalog = static::catalog();
        $order = (array) config('roles.segment_order', []);

        $grouped = [];
        foreach ($order as $segment) {
            $grouped[$segment] = [];
        }

        foreach ($catalog as $key => $meta) {
            $segment = $meta['segment'];

            // A catalog segment not listed in segment_order still gets shown.
            $grouped[$segment][$key] = $meta;
        }

        // Drop any segment that ended up with no roles.
        return array_filter($grouped, static fn ($roles) => $roles !== []);
    }

    /** Human label for a role key, falling back to a title-cased key. */
    public static function label(string $key): string
    {
        return static::catalog()[$key]['label'] ?? ucfirst(str_replace('_', ' ', $key));
    }
}
