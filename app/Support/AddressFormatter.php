<?php

namespace App\Support;

class AddressFormatter
{
    /**
     * Compose a single-line physical address from a SchoolProfile-like object.
     * Returns null when no parts are available. Never throws.
     */
    public static function forProfile($profile): ?string
    {
        if (!$profile) {
            return null;
        }

        try {
            $fields = ['unit_number', 'building', 'phase', 'street', 'barangay',
                       'district', 'city', 'province', 'region', 'country', 'zip_code'];

            $parts = [];
            foreach ($fields as $f) {
                $v = $profile->{$f} ?? null;
                if ($v !== null && $v !== '') {
                    $parts[] = $v;
                }
            }

            $composed = implode(', ', $parts);
            if ($composed !== '') {
                return $composed;
            }

            return $profile->address ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
