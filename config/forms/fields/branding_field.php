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

    // ---- Printable document letterhead (enrolment preview / PDF) -------------
    // Each image is rendered onto the A4 paper and scaled proportionally to the
    // paper width (or full page, for the background). When a header/footer image
    // is set, the default coloured band is hidden in its place.

    'school_header' => [
        'label'   => 'Document Header',
        'type'    => 'file',
        'roles'   => ['admin'],
        'help'    => 'Full-width header band for printed documents. Fits within the Header Space (height) and scales by ratio. Tip: size it so width:height matches paper-width : header-space.',
        'preview' => 'band',
        'upload'  => [
            'disk' => 'public',
            'path' => 'schools/{school_id}/header'
        ],
        'save_to' => [
            [
                'model'  => SchoolProfile::class,
                'column' => 'school_header',
                'where'  => ['school_id' => 'auth_school_id'],
            ],
        ],
    ],

    'header_space' => [
        'label'   => 'Header Space',
        'type'    => 'number',
        'roles'   => ['admin'],
        'default' => 1,
        'step'    => 0.25,
        'min'     => 0,
        'unit'    => 'in',
        'help'    => 'Space reserved for the header on the page (inches).',
        'save_to' => [
            [
                'model'  => SchoolProfile::class,
                'column' => 'header_space',
                'where'  => ['school_id' => 'auth_school_id'],
            ],
        ],
    ],

    'school_footer' => [
        'label'   => 'Document Footer',
        'type'    => 'file',
        'roles'   => ['admin'],
        'help'    => 'Full-width footer band for printed documents. Fits within the Footer Space (height) and scales by ratio. Tip: size it so width:height matches paper-width : footer-space.',
        'preview' => 'band',
        'upload'  => [
            'disk' => 'public',
            'path' => 'schools/{school_id}/footer'
        ],
        'save_to' => [
            [
                'model'  => SchoolProfile::class,
                'column' => 'school_footer',
                'where'  => ['school_id' => 'auth_school_id'],
            ],
        ],
    ],

    'footer_space' => [
        'label'   => 'Footer Space',
        'type'    => 'number',
        'roles'   => ['admin'],
        'default' => 0.5,
        'step'    => 0.25,
        'min'     => 0,
        'unit'    => 'in',
        'help'    => 'Space reserved for the footer on the page (inches).',
        'save_to' => [
            [
                'model'  => SchoolProfile::class,
                'column' => 'footer_space',
                'where'  => ['school_id' => 'auth_school_id'],
            ],
        ],
    ],

    'school_background' => [
        'label'   => 'Document Background (Whole Page)',
        'type'    => 'file',
        'roles'   => ['admin'],
        'help'    => 'Whole-page background sized to A4 (ratio ~1:1.414, e.g. 2480×3508px). Scales to the full paper proportionally behind the content.',
        'preview' => 'page',
        'upload'  => [
            'disk' => 'public',
            'path' => 'schools/{school_id}/background'
        ],
        'save_to' => [
            [
                'model'  => SchoolProfile::class,
                'column' => 'school_background',
                'where'  => ['school_id' => 'auth_school_id'],
            ],
        ],
    ],

];