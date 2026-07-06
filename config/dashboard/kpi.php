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
                'active_subjects_student',
                'pending_assignments_student',
                'progress_student',
                'GWA_student',
                'task_student',
                'subject_at_risk',
                'outstanding_student',
                'units_student',
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
            'title'  => 'Outstanding Balance',
            'icon'   => 'wallet',
            'color'  => 'amber',
            'accent' => 'amber',
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

        // ---- Student dashboard cards (values via StudentDashboardService) ----

        'active_subjects_student' => [
            'title'  => 'Active Subjects',
            'icon'   => 'book-open',
            'color'  => 'indigo',
            'accent' => 'indigo',
        ],

        'pending_assignments_student' => [
            'title'  => 'Pending Assignments',
            'icon'   => 'clipboard-list',
            'color'  => 'amber',
            'accent' => 'amber',
        ],

        'progress_student' => [
            'title'  => 'Overall Progress',
            'icon'   => 'gauge',
            'color'  => 'emerald',
            'accent' => 'emerald',
        ],

        'GWA_student' => [
            'title'  => 'Current GWA',
            'icon'   => 'trending-up',
            'color'  => 'sky',
            'accent' => 'sky',
        ],

        'task_student' => [
            'title'  => 'Completed Tasks',
            'icon'   => 'check-circle',
            'color'  => 'emerald',
            'accent' => 'emerald',
        ],

        'subject_at_risk' => [
            'title'  => 'Subjects at Risk',
            'icon'   => 'alert-triangle',
            'color'  => 'rose',
            'accent' => 'rose',
        ],

        'units_student' => [
            'title'  => 'Enrolled Units',
            'icon'   => 'graduation-cap',
            'color'  => 'violet',
            'accent' => 'violet',
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
