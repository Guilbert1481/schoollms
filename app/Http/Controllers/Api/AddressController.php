<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Address lookup endpoints used by the residential-address React component.
 *
 * Countries + dial codes are served from a static list. Philippine geographic
 * data (regions → provinces → cities → barangays) is read from JSON files
 * placed in resources/data/ph-address/. If those files are missing we return
 * empty arrays so the UI stays functional.
 */
class AddressController extends Controller
{
    public function countries(): array
    {
        return self::countryList();
    }

    public function regions(Request $request): array
    {
        $country = strtoupper((string) $request->query('country', 'PH'));

        if ($country !== 'PH') {
            return [];
        }

        return $this->loadPhData('regions') ?: [];
    }

    public function provinces(Request $request): array
    {
        $region = (string) $request->query('region', '');
        if ($region === '') return [];

        $all = $this->loadPhData('provinces') ?: [];
        return array_values(array_filter($all, fn ($row) => ($row['region'] ?? null) === $region));
    }

    public function cities(Request $request): array
    {
        $country  = strtoupper((string) $request->query('country', 'PH'));
        $province = (string) $request->query('province', '');
        $region   = (string) $request->query('region', '');

        $all = $this->loadPhData('cities') ?: [];

        if ($country !== 'PH') {
            // Non-PH: callers may pass a region (state) to filter by.
            return $region === ''
                ? $all
                : array_values(array_filter($all, fn ($row) => ($row['region'] ?? null) === $region));
        }

        // PH: cities are normally filtered by province. NCR (and any other
        // region with no provinces) skips that step, so allow filtering by
        // region when no province is provided.
        if ($province !== '') {
            return array_values(array_filter($all, fn ($row) => ($row['province'] ?? null) === $province));
        }
        if ($region !== '') {
            return array_values(array_filter($all, fn ($row) => ($row['region'] ?? null) === $region));
        }
        return [];
    }

    public function barangays(Request $request): array
    {
        $city = (string) $request->query('city', '');
        if ($city === '') return [];

        $all = $this->loadPhData('barangays') ?: [];
        return array_values(array_filter($all, fn ($row) => ($row['city'] ?? null) === $city));
    }

    /**
     * Look up a Philippine ZIP code by either:
     *   ?cityCode=<PSGC code>   (preferred)
     *   ?cityName=<text>        (fallback, case-insensitive)
     */
    public function zip(Request $request): array
    {
        $cityCode = (string) $request->query('cityCode', '');
        $cityName = trim((string) $request->query('cityName', ''));

        $map = $this->loadPhZipMap();

        if ($cityCode !== '' && isset($map['by_code'][$cityCode])) {
            return ['zip' => $map['by_code'][$cityCode]];
        }
        if ($cityName !== '') {
            $key = mb_strtolower($cityName);
            if (isset($map['by_name'][$key])) {
                return ['zip' => $map['by_name'][$key]];
            }
        }
        return ['zip' => null];
    }

    protected function loadPhZipMap(): array
    {
        return Cache::remember('ph_address.zip_map', 3600, function () {
            $path = resource_path('data/ph-address/zip-codes.json');
            if (! is_file($path)) return ['by_code' => [], 'by_name' => []];
            $raw  = json_decode(file_get_contents($path), true) ?: [];

            $byCode = [];
            $byName = [];
            foreach ($raw as $key => $value) {
                if ($key === '' || $key[0] === '_') continue; // skip _comment
                if (ctype_digit((string) $key) && strlen((string) $key) >= 9) {
                    $byCode[(string) $key] = (string) $value;
                } else {
                    $byName[mb_strtolower((string) $key)] = (string) $value;
                }
            }
            return ['by_code' => $byCode, 'by_name' => $byName];
        });
    }

    protected function loadPhData(string $key): ?array
    {
        return Cache::remember("ph_address.$key", 3600, function () use ($key) {
            $path = resource_path("data/ph-address/{$key}.json");
            if (! is_file($path)) return [];
            $data = json_decode(file_get_contents($path), true);
            return is_array($data) ? $data : [];
        });
    }

    /**
     * ISO alpha-2 + dial code list. Trimmed down for clarity; extend as needed.
     */
    protected static function countryList(): array
    {
        return [
            ['value' => 'PH', 'label' => 'Philippines',          'dialCode' => '+63'],
            ['value' => 'US', 'label' => 'United States',        'dialCode' => '+1'],
            ['value' => 'CA', 'label' => 'Canada',               'dialCode' => '+1'],
            ['value' => 'AU', 'label' => 'Australia',            'dialCode' => '+61'],
            ['value' => 'GB', 'label' => 'United Kingdom',       'dialCode' => '+44'],
            ['value' => 'JP', 'label' => 'Japan',                'dialCode' => '+81'],
            ['value' => 'KR', 'label' => 'South Korea',          'dialCode' => '+82'],
            ['value' => 'CN', 'label' => 'China',                'dialCode' => '+86'],
            ['value' => 'HK', 'label' => 'Hong Kong',            'dialCode' => '+852'],
            ['value' => 'SG', 'label' => 'Singapore',            'dialCode' => '+65'],
            ['value' => 'MY', 'label' => 'Malaysia',             'dialCode' => '+60'],
            ['value' => 'ID', 'label' => 'Indonesia',            'dialCode' => '+62'],
            ['value' => 'TH', 'label' => 'Thailand',             'dialCode' => '+66'],
            ['value' => 'VN', 'label' => 'Vietnam',              'dialCode' => '+84'],
            ['value' => 'IN', 'label' => 'India',                'dialCode' => '+91'],
            ['value' => 'AE', 'label' => 'United Arab Emirates', 'dialCode' => '+971'],
            ['value' => 'SA', 'label' => 'Saudi Arabia',         'dialCode' => '+966'],
            ['value' => 'DE', 'label' => 'Germany',              'dialCode' => '+49'],
            ['value' => 'FR', 'label' => 'France',               'dialCode' => '+33'],
            ['value' => 'ES', 'label' => 'Spain',                'dialCode' => '+34'],
            ['value' => 'IT', 'label' => 'Italy',                'dialCode' => '+39'],
            ['value' => 'NL', 'label' => 'Netherlands',          'dialCode' => '+31'],
            ['value' => 'NZ', 'label' => 'New Zealand',          'dialCode' => '+64'],
            ['value' => 'BR', 'label' => 'Brazil',               'dialCode' => '+55'],
            ['value' => 'MX', 'label' => 'Mexico',               'dialCode' => '+52'],
        ];
    }
}
