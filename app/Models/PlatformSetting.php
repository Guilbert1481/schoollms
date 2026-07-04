<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Platform-wide (cross-school) key/value settings edited by the superadmin.
 * Unlike FinanceSetting (per school) this is a single global store.
 */
class PlatformSetting extends Model
{
    /** Default system fee (in the platform currency, e.g. PHP) when none is set. */
    public const DEFAULT_SYSTEM_FEE = 10;

    protected $fillable = ['key', 'value'];

    /** Read a setting, falling back to $default when it is missing/blank. */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = static::query()->where('key', $key)->value('value');

        return ($value === null || $value === '') ? $default : $value;
    }

    /** Create or update a setting. */
    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => (string) $value]);
    }

    /**
     * The online-checkout convenience fee, in whole currency units. Always a
     * non-negative number; falls back to DEFAULT_SYSTEM_FEE when unset/invalid.
     */
    public static function systemFee(): float
    {
        $raw = static::get('system_fee', self::DEFAULT_SYSTEM_FEE);

        return is_numeric($raw) ? max(0, round((float) $raw, 2)) : (float) self::DEFAULT_SYSTEM_FEE;
    }
}
