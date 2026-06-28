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
                'drop_out_students',
                'student_at_risk',
                'teachers',
                'revenue',
                'outstanding_total',
            ],
        ],

        'academics' => [
            'summary_cards' => [
                'teachers',
                'students',
            ],
        ],

        'admission_manager' => [
            'summary_cards' => [
                'new_applications',
            ],
        ],

        'finance_manager' => [
            'summary_cards' => [
                'awaiting_payment',
                'revenue',
                'outstanding_total',
                'students',
            ],
        ],

        'teacher' => [
            'summary_cards' => [
                'students',
            ],
        ],

        'student' => [
            'summary_cards' => [
                'outstanding_student',
                'attendance_student',
                'GWA_student',
                'task_student',
                'subject_at_risk',
                
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

        'outstanding_student' => [
            'title' => 'Outstanding Balance',
            'icon'  => 'alert-circle',
            'color' => 'red',
        ],

        'outstanding_total' => [
            'title' => 'Outstanding Balance',
            'icon'  => 'alert-circle',
            'color' => 'red',
        ],

        'awaiting_payment' => [
            'title' => 'Awaiting Payment',
            'icon'  => 'file-text',
            'color' => 'violet',
        ],

        'new_applications_student' => [
            'title' => 'New Applications',
            'icon'  => 'file-text',
            'color' => 'orange',
        ],

        'attendance_student' => [
            'title' => 'Attendance',
            'icon'  => 'file-text',
            'color' => 'orange',
        ],

        'GWA_student' => [
            'title' => 'GWA',
            'icon'  => 'file-text',
            'color' => 'orange',
        ],

        'task_student' => [
            'title' => 'Task Completion',
            'icon'  => 'file-text',
            'color' => 'orange',
        ],

        'subject_at_risk' => [
            'title' => 'Subject At Risk',
            'icon'  => 'file-text',
            'color' => 'orange',
        ],

        'drop_out_students' => [
            'title' => 'Drop Out',
            'icon'  => 'file-text',
            'color' => 'orange',
        ],

        'student_at_risk' => [
            'title' => 'Student At Risk',
            'icon'  => 'file-text',
            'color' => 'orange',
        ],

    ],

];
