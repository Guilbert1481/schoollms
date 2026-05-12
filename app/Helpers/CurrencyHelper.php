<?php

namespace App\Helpers;

use App\Models\SchoolProfile;

class CurrencyHelper
{
    /**
     * ISO country (name or code) => currency info.
     */
    protected static array $map = [
        'philippines'    => ['code' => 'PHP', 'symbol' => '₱'],
        'ph'             => ['code' => 'PHP', 'symbol' => '₱'],
        'united states'  => ['code' => 'USD', 'symbol' => '$'],
        'usa'            => ['code' => 'USD', 'symbol' => '$'],
        'us'             => ['code' => 'USD', 'symbol' => '$'],
        'united kingdom' => ['code' => 'GBP', 'symbol' => '£'],
        'uk'             => ['code' => 'GBP', 'symbol' => '£'],
        'canada'         => ['code' => 'CAD', 'symbol' => 'C$'],
        'australia'      => ['code' => 'AUD', 'symbol' => 'A$'],
        'japan'          => ['code' => 'JPY', 'symbol' => '¥'],
        'china'          => ['code' => 'CNY', 'symbol' => '¥'],
        'singapore'      => ['code' => 'SGD', 'symbol' => 'S$'],
        'malaysia'       => ['code' => 'MYR', 'symbol' => 'RM'],
        'indonesia'      => ['code' => 'IDR', 'symbol' => 'Rp'],
        'thailand'       => ['code' => 'THB', 'symbol' => '฿'],
        'vietnam'        => ['code' => 'VND', 'symbol' => '₫'],
        'india'          => ['code' => 'INR', 'symbol' => '₹'],
        'germany'        => ['code' => 'EUR', 'symbol' => '€'],
        'france'         => ['code' => 'EUR', 'symbol' => '€'],
        'spain'          => ['code' => 'EUR', 'symbol' => '€'],
        'italy'          => ['code' => 'EUR', 'symbol' => '€'],
    ];

    public static function fromCountry(?string $country): array
    {
        $key = strtolower(trim((string) $country));
        return self::$map[$key] ?? ['code' => 'PHP', 'symbol' => '₱'];
    }

    /**
     * Resolve currency for the authenticated user's school.
     */
    public static function forCurrentSchool(): array
    {
        $schoolId = auth()->user()->school_id ?? null;

        if (!$schoolId) {
            return self::fromCountry(null);
        }

        $profile = SchoolProfile::where('school_id', $schoolId)->first();
        $country = $profile->country ?? null;

        return self::fromCountry($country);
    }
}
