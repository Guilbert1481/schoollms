<?php

use App\Models\SchoolProfile;

return [

    'school_name' => [
        'label'  => 'School Name',
        'type'   => 'text',
        'roles'  => ['admin'],
        'locked' => true,
        'hint'   => 'Managed by the platform. To change the school name, submit a request with supporting documents to the superadmin.',
        'save_to' => [[
            'model'  => SchoolProfile::class,
            'column' => 'school_name',
            'where'  => ['school_id' => 'auth_school_id'],
        ]],
    ],

    'established_year' => [
        'label' => 'Established Year',
        'type'  => 'number',
        'roles' => ['admin'],
        'save_to' => [[
            'model'  => SchoolProfile::class,
            'column' => 'established_year',
            'where'  => ['school_id' => 'auth_school_id'],
        ]],
    ],

    'business_type' => [
        'label'   => 'Type of Business',
        'type'    => 'select',
        'options' => ['Sole Proprietor', 'Partnership', 'Corporation'],
        'roles'   => ['admin'],
        'save_to' => [[
            'model'  => SchoolProfile::class,
            'column' => 'business_type',
            'where'  => ['school_id' => 'auth_school_id'],
        ]],
    ],

    'tax_number' => [
        'label' => 'Tax Number',
        'type'  => 'text',
        'roles' => ['admin'],
        'save_to' => [[
            'model'  => SchoolProfile::class,
            'column' => 'tax_number',
            'where'  => ['school_id' => 'auth_school_id'],
        ]],
    ],

    'sss_number' => [
        'label' => 'Social Security Number',
        'type'  => 'text',
        'roles' => ['admin'],
        'save_to' => [[
            'model'  => SchoolProfile::class,
            'column' => 'sss_number',
            'where'  => ['school_id' => 'auth_school_id'],
        ]],
    ],

    'business_permit_number' => [
        'label' => 'Business Permit Number',
        'type'  => 'text',
        'roles' => ['admin'],
        'save_to' => [[
            'model'  => SchoolProfile::class,
            'column' => 'business_permit_number',
            'where'  => ['school_id' => 'auth_school_id'],
        ]],
    ],

];