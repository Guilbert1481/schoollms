<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolProfile extends Model
{
    protected $fillable = [
        'school_id',
        'school_name',
        'school_logo',
        'school_seal',
        'school_hero',
        'school_header',
        'school_footer',
        'school_background',
        'header_space',
        'footer_space',
        'address',
        'contact_number',
        'mobile_number',
        'fax_number',
        'website',
        'email',
        'motto',
        'vision',
        'mission',
        'head_name',
        'head_title',
        'registrar_name',
        'registrar_title',
        'established_year',
        'business_type',
        'tax_number',
        'sss_number',
        'business_permit_number',
    ];

    protected $casts = [
        'header_space' => 'float',
        'footer_space' => 'float',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $profile) {
            $parts = array_filter([
                $profile->unit_number,
                $profile->building,
                $profile->phase,
                $profile->street,
                $profile->barangay,
                $profile->district,
                $profile->city,
                $profile->province,
                $profile->region,
                $profile->country,
                $profile->zip_code,
            ], fn($v) => $v !== null && $v !== '');

            if (!empty($parts)) {
                $profile->address = implode(', ', $parts);
            }
        });
    }
}