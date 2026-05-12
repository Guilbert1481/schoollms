<?php

use App\Models\SchoolProfile;

return [

    'contact_number' => [
        'label' => 'Contact Number',
        'type'  => 'text',
        'roles' => ['admin','registrar'],
        'save_to' => [[
            'model'  => SchoolProfile::class,
            'column' => 'contact_number',
            'where'  => ['school_id' => 'auth_school_id'],
        ]],
    ],

    'mobile_number' => [
        'label' => 'Mobile Number',
        'type'  => 'text',
        'roles' => ['admin','registrar'],
        'save_to' => [[
            'model'  => SchoolProfile::class,
            'column' => 'mobile_number',
            'where'  => ['school_id' => 'auth_school_id'],
        ]],
    ],

    'fax_number' => [
        'label' => 'Fax Number',
        'type'  => 'text',
        'roles' => ['admin','registrar'],
        'save_to' => [[
            'model'  => SchoolProfile::class,
            'column' => 'fax_number',
            'where'  => ['school_id' => 'auth_school_id'],
        ]],
    ],

    'website' => [
        'label' => 'Website',
        'type'  => 'text',
        'roles' => ['admin','registrar'],
        'save_to' => [[
            'model'  => SchoolProfile::class,
            'column' => 'website',
            'where'  => ['school_id' => 'auth_school_id'],
        ]],
    ],

    'email' => [
        'label' => 'Email',
        'type'  => 'email',
        'roles' => ['admin','registrar'],
        'save_to' => [[
            'model'  => SchoolProfile::class,
            'column' => 'email',
            'where'  => ['school_id' => 'auth_school_id'],
        ]],
    ],

];