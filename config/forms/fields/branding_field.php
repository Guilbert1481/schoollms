<?php

use App\Models\SchoolProfile;

return [

    'school_logo' => [
        'label' => 'School Logo',
        'type'  => 'file',
        'roles' => ['admin'],
        'upload' => [
            'disk' => 'public',
            'path' => 'schools/{school_id}/logo'
        ],
        'save_to' => [
            [
                'model'  => SchoolProfile::class,
                'column' => 'school_logo',
                'where'  => ['school_id' => 'auth_school_id'],
            ],
        ],
    ],

    'school_seal' => [
        'label' => 'School Seal',
        'type'  => 'file',
        'roles' => ['admin'],
        'upload' => [
            'disk' => 'public',
            'path' => 'schools/{school_id}/seal'
        ],
        'save_to' => [
            [
                'model'  => SchoolProfile::class,
                'column' => 'school_seal',
                'where'  => ['school_id' => 'auth_school_id'],
            ],
        ],
    ],

    'school_hero' => [
        'label' => 'School Hero',
        'type'  => 'file',
        'roles' => ['admin'],
        'help'  => 'Wide banner image displayed below the top navigation on your public site.',
        'upload' => [
            'disk' => 'public',
            'path' => 'schools/{school_id}/hero'
        ],
        'save_to' => [
            [
                'model'  => SchoolProfile::class,
                'column' => 'school_hero',
                'where'  => ['school_id' => 'auth_school_id'],
            ],
        ],
    ],

];