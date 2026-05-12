<?php

use App\Models\SchoolProfile;

return [

    'head_title' => [
        'label' => 'School Head Title',
        'type'  => 'text',
        'roles' => ['admin'],
        'save_to' => [
            [
                'model'  => SchoolProfile::class,
                'column' => 'head_title',
                'where'  => ['school_id' => 'auth_school_id'],
            ],
        ],
    ],

    'head_name' => [
        'label' => 'School Head Name',
        'type'  => 'text',
        'roles' => ['admin'],
        'save_to' => [
            [
                'model'  => SchoolProfile::class,
                'column' => 'head_name',
                'where'  => ['school_id' => 'auth_school_id'],
            ],
        ],
    ],

    'registrar_title' => [
        'label' => 'Registrar Title',
        'type'  => 'text',
        'roles' => ['admin'],
        'save_to' => [
            [
                'model'  => SchoolProfile::class,
                'column' => 'registrar_title',
                'where'  => ['school_id' => 'auth_school_id'],
            ],
        ],
    ],

    'registrar_name' => [
        'label' => 'Registrar Name',
        'type'  => 'text',
        'roles' => ['admin'],
        'save_to' => [
            [
                'model'  => SchoolProfile::class,
                'column' => 'registrar_name',
                'where'  => ['school_id' => 'auth_school_id'],
            ],
        ],
    ],

];