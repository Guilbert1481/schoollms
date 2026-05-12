<?php

return [

    'school_settings' => [

        'roles' => ['admin','registrar','dean'],

        'layout' => [

            'school_profile' => [
                [
                    ['field' => 'school_name', 'col_span' => 3],
                    ['field' => 'established_year', 'col_span' => 1],
                ],
                [
                    ['field' => 'business_type', 'col_span' => 1],
                    ['field' => 'tax_number', 'col_span' => 1],
                    ['field' => 'sss_number', 'col_span' => 1],
                    ['field' => 'business_permit_number', 'col_span' => 1],
                ],
            ],

            'address' => [
                [
                    ['field' => 'unit_number', 'col_span' => 1],
                    ['field' => 'building', 'col_span' => 1],
                    ['field' => 'phase', 'col_span' => 1],
                    ['field' => 'street', 'col_span' => 1],
                ],
                [
                    ['field' => 'barangay', 'col_span' => 1],
                    ['field' => 'district', 'col_span' => 1],
                    ['field' => 'city', 'col_span' => 1],
                    ['field' => 'province', 'col_span' => 1],
                ],
                [
                    ['field' => 'region', 'col_span' => 1],
                    ['field' => 'country', 'col_span' => 1],
                    ['field' => 'zip_code', 'col_span' => 1],
                ],
            ],

            'contact' => [
                [
                    ['field' => 'contact_number', 'col_span' => 1],
                    ['field' => 'mobile_number', 'col_span' => 1],
                    ['field' => 'fax_number', 'col_span' => 1],
                ],
                [
                    ['field' => 'website', 'col_span' => 2],
                    ['field' => 'email', 'col_span' => 2],
                ],
            ],

            'identity' => [
                [
                    ['field' => 'motto', 'col_span' => 4],
                ],
                [
                    ['field' => 'vision', 'col_span' => 4],
                ],
                [
                    ['field' => 'mission', 'col_span' => 4],
                ],
            ],

            'administration' => [
                [
                    ['field' => 'head_title', 'col_span' => 2],
                    ['field' => 'head_name', 'col_span' => 2],
                ],
                [
                    ['field' => 'registrar_title', 'col_span' => 2],
                    ['field' => 'registrar_name', 'col_span' => 2],
                ],
            ],

            'branding' => [
                [
                    ['field' => 'school_logo', 'col_span' => 2],
                    ['field' => 'school_seal', 'col_span' => 2],
                ],
            ],

            'modalities' => [
                [
                    ['field' => 'modalities', 'col_span' => 1],
                ],
            ],


            'bank_information' => [
                [
                    ['field' => 'bank_name', 'col_span' => 2],
                    ['field' => 'account_name', 'col_span' => 2],
                ],
                [
                    ['field' => 'account_number', 'col_span' => 2],
                ],
            ],

            

        ],
    ],

    'event_form_setup' => [
        'roles' => ['admin'],
        'layout' => [
            'event_form_setup' => [
                [
                    ['field' => 'event_name', 'col_span' => 4],
                ],
                [
                    ['field' => 'event_type', 'col_span' => 4],
                ],
                [
                    ['field' => 'event_types', 'col_span' => 2],
                    ['field' => 'role_types', 'col_span' => 2],
                ],
                [
                    ['field' => 'certificate_title_default', 'col_span' => 2],
                    ['field' => 'date_issued_default', 'col_span' => 2],
                ],
                [
                    ['field' => 'description', 'col_span' => 4],
                ],
            ],
        ],
    ],

];