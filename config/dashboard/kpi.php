<?php



return [

    'styles' => [

        'primary' => [
            'bg'   => 'bg-blue-50 dark:bg-blue-900/20',
            'text' => 'text-blue-600',
        ],

        'secondary' => [
            'bg'   => 'bg-indigo-50 dark:bg-indigo-900/20',
            'text' => 'text-indigo-600',
        ],

        'info' => [
            'bg'   => 'bg-cyan-50 dark:bg-cyan-900/20',
            'text' => 'text-cyan-600',
        ],

        'success' => [
            'bg'   => 'bg-green-50 dark:bg-green-900/20',
            'text' => 'text-green-600',
        ],

        'warning' => [
            'bg'   => 'bg-yellow-50 dark:bg-yellow-900/20',
            'text' => 'text-yellow-600',
        ],

        'danger' => [
            'bg'   => 'bg-red-50 dark:bg-red-900/20',
            'text' => 'text-red-600',
        ],

    ],
    /*
    |--------------------------------------------------------------------------
    | ROLE → KPI VISIBILITY
    |--------------------------------------------------------------------------
    */

    'roles' => [

        'superadmin' => [
            'summary_cards' => [
                'active_schools',
            ],
        ],

        'admin' => [
            'summary_cards' => [
                'students',
                'teachers',
                'revenue',
                'outstanding',
            ],
        ],

        'academics' => [
            'summary_cards' => [
                'teachers',
                'students',
            ],
        ],

        'admission' => [
            'summary_cards' => [
                'new_applications',
            ],
        ],

        'teacher' => [
            'summary_cards' => [
                'students',
            ],
        ],

        'student' => [
            'summary_cards' => [
                // future KPIs for student dashboard
            ],
        ],
    ],



    /*
    |--------------------------------------------------------------------------
    | SUMMARY CARD DEFINITIONS (Row 1)
    |--------------------------------------------------------------------------
    */

    'summary_cards' => [

        'active_schools' => [
            'title' => 'Active Schools',
            'icon'  => 'building',
            'color' => 'green',
        ],

        'students' => [
            'title' => 'Enrolled Students',
            'icon'  => 'users',
            'color' => 'indigo',
        ],

        'teachers' => [
            'title' => 'Active Teachers',
            'icon'  => 'briefcase',
            'color' => 'blue',
        ],

        'revenue' => [
            'title' => 'Revenue This Month',
            'icon'  => 'banknote',
            'color' => 'green',
        ],

        'outstanding' => [
            'title' => 'Outstanding Balance',
            'icon'  => 'alert-circle',
            'color' => 'red',
        ],

        'new_applications' => [
            'title' => 'New Applications',
            'icon'  => 'file-text',
            'color' => 'orange',
        ],

    ],

];
