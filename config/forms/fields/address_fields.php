<?php

use App\Models\SchoolProfile;

return [

    'unit_number' => [
        'label' => 'Unit Number',
        'type'  => 'text',
        'roles' => ['admin','registrar'],
        'save_to' => [[
            'model'  => SchoolProfile::class,
            'column' => 'unit_number',
            'where'  => ['school_id' => 'auth_school_id'],
        ]],
    ],

    'building' => [
        'label' => 'Subdivision / Building',
        'type'  => 'text',
        'roles' => ['admin','registrar'],
        'save_to' => [[
            'model'  => SchoolProfile::class,
            'column' => 'building',
            'where'  => ['school_id' => 'auth_school_id'],
        ]],
    ],

    'phase' => [
        'label' => 'Phase / Zone',
        'type'  => 'text',
        'roles' => ['admin','registrar'],
        'save_to' => [[
            'model'  => SchoolProfile::class,
            'column' => 'phase',
            'where'  => ['school_id' => 'auth_school_id'],
        ]],
    ],

    'street' => [
        'label' => 'Street',
        'type'  => 'text',
        'roles' => ['admin','registrar'],
        'save_to' => [[
            'model'  => SchoolProfile::class,
            'column' => 'street',
            'where'  => ['school_id' => 'auth_school_id'],
        ]],
    ],

    'barangay' => [
        'label' => 'Barangay',
        'type'  => 'text',
        'roles' => ['admin','registrar'],
        'save_to' => [[
            'model'  => SchoolProfile::class,
            'column' => 'barangay',
            'where'  => ['school_id' => 'auth_school_id'],
        ]],
    ],

    'district' => [
        'label' => 'District',
        'type'  => 'text',
        'roles' => ['admin','registrar'],
        'save_to' => [[
            'model'  => SchoolProfile::class,
            'column' => 'district',
            'where'  => ['school_id' => 'auth_school_id'],
        ]],
    ],

    'city' => [
        'label' => 'City / Municipality',
        'type'  => 'text',
        'roles' => ['admin','registrar'],
        'save_to' => [[
            'model'  => SchoolProfile::class,
            'column' => 'city',
            'where'  => ['school_id' => 'auth_school_id'],
        ]],
    ],

    'province' => [
        'label' => 'Province',
        'type'  => 'text',
        'roles' => ['admin','registrar'],
        'save_to' => [[
            'model'  => SchoolProfile::class,
            'column' => 'province',
            'where'  => ['school_id' => 'auth_school_id'],
        ]],
    ],

    'region' => [
        'label' => 'Region / State',
        'type'  => 'text',
        'roles' => ['admin','registrar'],
        'save_to' => [[
            'model'  => SchoolProfile::class,
            'column' => 'region',
            'where'  => ['school_id' => 'auth_school_id'],
        ]],
    ],

    'country' => [
        'label' => 'Country',
        'type'  => 'text',
        'roles' => ['admin','registrar'],
        'save_to' => [[
            'model'  => SchoolProfile::class,
            'column' => 'country',
            'where'  => ['school_id' => 'auth_school_id'],
        ]],
    ],

    'zip_code' => [
        'label' => 'Zip Code',
        'type'  => 'text',
        'roles' => ['admin','registrar'],
        'save_to' => [[
            'model'  => SchoolProfile::class,
            'column' => 'zip_code',
            'where'  => ['school_id' => 'auth_school_id'],
        ]],
    ],

];