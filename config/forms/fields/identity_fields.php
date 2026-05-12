<?php

use App\Models\SchoolProfile;

return [

    'motto' => [
        'label' => 'Motto',
        'type'  => 'text',
        'roles' => ['admin','dean'],
        'save_to' => [[
            'model'  => SchoolProfile::class,
            'column' => 'motto',
            'where'  => ['school_id' => 'auth_school_id'],
        ]],
    ],

    'vision' => [
        'label' => 'Vision',
        'type'  => 'textarea',
        'roles' => ['admin','dean'],
        'save_to' => [[
            'model'  => SchoolProfile::class,
            'column' => 'vision',
            'where'  => ['school_id' => 'auth_school_id'],
        ]],
    ],

    'mission' => [
        'label' => 'Mission',
        'type'  => 'textarea',
        'roles' => ['admin','dean'],
        'save_to' => [[
            'model'  => SchoolProfile::class,
            'column' => 'mission',
            'where'  => ['school_id' => 'auth_school_id'],
        ]],
    ],

];