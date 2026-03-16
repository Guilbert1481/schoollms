<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Role → Allowed Sections Mapping
    |--------------------------------------------------------------------------
    */

    'roles' => [

        'admin' => [
            'students',
            'academics',
            'faculty',
            'finance',
            'analytics',
            'communication',
            'settings',
            'logout',
        ],


        'admission' => [
            'applicants',
            'screening',
            'endorsement',
            'communication',
        ],

        'program_head' => [
            'enrollment_growth',
            'student_success',
            'faculty',
            'programs',
            'academic_operations',
            'financial_performance',
            'quality_assurance',
            'approvals',
            'communication',
            'settings',
            'logout',
        ],


        'dean' => [
            'programs',
            'students',
            'faculty',
            'curriculum',
            'financial_performance',
            'quality_assurance',
            'approvals',
            'communication',
            'settings',
            'logout',
        ],

        'teacher' => [
            'my_classes',
            'tests',
            'grading',
            'communication',
            'settings',
            'logout',
        ],

        'student' => [
            'mysubjects',
            'grades',
            'communication',
            'logout',
        ],
    ],


    /*
    |---------------------------------------------------------------------------------------------------
    | Master Menu Definition
    |---------------------------------------------------------------------------------------------------
    */

    'menu' => [

        /*
        |--------------------------------------------------------------------------
        | STUDENTS
        |--------------------------------------------------------------------------
        */
        'students' => [
            'icon' => 'users',
            'label' => 'Students',
            'children' => [
                [
                    'label' => 'Student Directory',
                    'route' => '#',
                    'icon'  => 'id-card',
                ],
                [
                    'label' => 'Enrollment',
                    'route' => '#',
                    'icon'  => 'graduation-cap',
                ],
                [
                    'label' => 'Attendance',
                    'route' => '#',
                    'icon'  => 'calendar-check',
                ],
                [
                    'label' => 'Lifecycle',
                    'route' => '#',
                    'icon'  => 'activity',
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | ACADEMICS
        |--------------------------------------------------------------------------
        */
        'academics' => [
            'icon' => 'book-open',
            'label' => 'Academics',
            'children' => [
                [
                    'label' => 'Programs',
                    'route' => '#',
                    'icon'  => 'layers',
                ],
                [
                    'label' => 'Subjects',
                    'route' => '#',
                    'icon'  => 'clipboard-list',
                ],
                [
                    'label' => 'Sections',
                    'route' => '#',
                    'icon'  => 'layout-grid',
                ],
                [
                    'label' => 'Grading',
                    'route' => '#',
                    'icon'  => 'calculator',
                ],
            ],
        ],


        'my_classes' => [
            'icon'  => 'book-open',
            'label' => 'My Classes',
            'children' => [

                [
                    'label' => 'Class List',
                    'route' => '#',
                    'icon'  => 'list',
                ],

                [
                    'label' => 'Attendance',
                    'route' => '#',
                    'icon'  => 'calendar-check',
                ],

                [
                    'label' => 'Grades',
                    'route' => '#',
                    'icon'  => 'clipboard-list',
                ],

                [
                    'label' => 'Assignments',
                    'route' => '#',
                    'icon'  => 'file-text',
                ],

                [
                    'label' => 'Students',
                    'route' => '#',
                    'icon'  => 'users',
                ],

            ],
        ],

        'tests' => [
            'icon'  => 'clipboard-check', // or 'file-question'
            'label' => 'Tests',
            'children' => [

                [
                    'label' => 'Create Questions',
                    'route' => 'teacher.question.metadata',
                    'icon'  => 'plus-circle',
                ],

                [
                    'label' => 'Question Bank',
                    'route' => '#',
                    'icon'  => 'database',
                ],

                [
                    'label' => 'Test Builder',
                    'route' => 'teacher.tests.create',
                    'icon'  => 'layout',
                ],

                [
                    'label' => 'Test Management',
                    'route' => '#',
                    'icon'  => 'settings-2',
                ],

            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | FACULTY
        |--------------------------------------------------------------------------
        */
        'faculty' => [
            'icon' => 'presentation',
            'label' => 'Faculty',
            'children' => [
                [
                    'label' => 'Teachers',
                    'route' => '#',
                    'icon'  => 'users-2',
                ],
                [
                    'label' => 'Teaching Load',
                    'route' => '#',
                    'icon'  => 'clipboard-check',
                ],
                [
                    'label' => 'Evaluation',
                    'route' => '#',
                    'icon'  => 'star',
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | FINANCE
        |--------------------------------------------------------------------------
        */
        'finance' => [
            'icon' => 'banknote',
            'label' => 'Finance',
            'children' => [
                [
                    'label' => 'Billing',
                    'route' => '#',
                    'icon'  => 'receipt',
                ],
                [
                    'label' => 'Payments',
                    'route' => '#',
                    'icon'  => 'credit-card',
                ],
                [
                    'label' => 'Outstanding Accounts',
                    'route' => '#',
                    'icon'  => 'alert-circle',
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | ANALYTICS
        |--------------------------------------------------------------------------
        */
        'analytics' => [
            'icon' => 'bar-chart-3',
            'label' => 'Analytics',
            'children' => [
                [
                    'label' => 'Teacher Reports',
                    'route' => '#',
                    'icon'  => 'presentation',
                ],
                [
                    'label' => 'Student Reports',
                    'route' => '#',
                    'icon'  => 'file-bar-chart',
                ],
                [
                    'label' => 'Faculty Reports',
                    'route' => '#',
                    'icon'  => 'pie-chart',
                ],
                [
                    'label' => 'Financial Reports',
                    'route' => '#',
                    'icon'  => 'line-chart',
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | ADMISSION OFFICE MODULES
        |--------------------------------------------------------------------------
        */
        'applicants' => [
            'icon' => 'user-plus',
            'label' => 'Applicants',
            'children' => [
                [
                    'label' => 'Applicant Directory',
                    'route' => '#',
                    'icon'  => 'users',
                ],
                [
                    'label' => 'Applications',
                    'route' => '#',
                    'icon'  => 'file-text',
                ],
                [
                    'label' => 'Requirements',
                    'route' => '#',
                    'icon'  => 'folder-check',
                ],
            ],
        ],

        'screening' => [
            'icon' => 'clipboard-check',
            'label' => 'Screening',
            'children' => [
                [
                    'label' => 'Entrance Exams',
                    'route' => '#',
                    'icon'  => 'edit-3',
                ],
                [
                    'label' => 'Interviews',
                    'route' => '#',
                    'icon'  => 'mic',
                ],
                [
                    'label' => 'Approval Decisions',
                    'route' => '#',
                    'icon'  => 'check-circle',
                ],
            ],
        ],

        'endorsement' => [
            'icon' => 'send',
            'label' => 'Endorsement',
            'children' => [
                [
                    'label' => 'For Enrollment',
                    'route' => '#',
                    'icon'  => 'arrow-right-circle',
                ],
                [
                    'label' => 'Rejected Applications',
                    'route' => '#',
                    'icon'  => 'x-circle',
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | COMMUNICATION
        |--------------------------------------------------------------------------
        */
        'communication' => [
            'icon' => 'mail',
            'label' => 'Communication',
            'children' => [

                [
                    'label' => 'Announcements',
                    'route' => 'communication.announcements.index',
                    'active' => 'communication.announcements.*',
                    'icon'  => 'megaphone',
                ],

                [
                    'label' => 'Deadlines',
                    'route' => 'communication.deadlines.index',
                    'active' => 'communication.deadlines.*',
                    'icon'  => 'calendar-clock',
                ],

                [
                    'label' => 'Chat',
                    'route' => 'communication.chat.index',
                    'active' => 'communication.chat.*',
                    'icon'  => 'message-circle',
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | SETTINGS
        |--------------------------------------------------------------------------
        */
        'settings' => [
            'icon' => 'settings',
            'label' => 'Settings',
            'children' => [

                [
                    'label' => 'User Management',
                    'route' => 'settings.users.index',
                    'active' => 'settings.users.*',
                    'icon'  => 'users',
                    'roles' => ['admin'],
                ],

                [
                    'label' => 'Assignment Management',
                    'route' => 'admin.assignments.index',
                    'active' => 'admin.assignments.*',
                    'icon'  => 'git-branch',
                    'roles' => ['admin'],
                ],

                [
                    'label' => 'Subscription',
                    'route' => '#',
                    'active' => 'settings.subscription.*',
                    'icon'  => 'credit-card',
                    'roles' => ['admin'],
                ],

                [
                    'label' => 'Add-ons',
                    'route' => '#',
                    'active' => 'settings.addons.*',
                    'icon'  => 'puzzle',
                    'roles' => ['admin'],
                ],

                [
                    'label' => 'Quote',
                    'route' => 'admin.quotes.index',
                    'active' => 'admin.quotes.*',
                    'icon' => 'quote',
                    'roles' => ['admin'],
                ],

                [
                    'label' => 'Profile Settings',
                    'route' => '#',
                    'icon'  => 'user',
                    'roles' => ['admin','dean','admission','teacher','student'],
                ],

                [
                    'label' => 'System Logs',
                    'route' => '#',
                    'icon'  => 'file-text',
                    'roles' => ['admin','dean'],
                ],

            ], // closes children
        ], // closes settings




    /*
    |--------------------------------------------------------------------------
    | SHARED MENU ITEMS (Reusable)
    |--------------------------------------------------------------------------
    */

    'enrollment_growth' => [
        'icon'  => 'trending-up',
        'label' => 'Enrollment & Growth',
        'children' => [
            [
                'label' => 'Overview',
                'route' => '#',
                'icon'  => 'bar-chart',
            ],
        ],
    ],

    'student_success' => [
        'icon'  => 'users',
        'label' => 'Student Success',
        'children' => [
            [
                'label' => 'Performance',
                'route' => '#',
                'icon'  => 'activity',
            ],
        ],
    ],

    'financial_performance' => [
        'icon'  => 'dollar-sign',
        'label' => 'Financial Performance',
        'children' => [
            [
                'label' => 'Overview',
                'route' => '#',
                'icon'  => 'line-chart',
            ],
        ],
    ],

    'quality_assurance' => [
        'icon'  => 'shield',
        'label' => 'Quality Assurance',
        'children' => [
            [
                'label' => 'Reports',
                'route' => '#',
                'icon'  => 'clipboard',
            ],
        ],
    ],

    'approvals' => [
        'icon'  => 'check-square',
        'label' => 'Approvals',
        'children' => [
            [
                'label' => 'Pending Approvals',
                'route' => '#',
                'icon'  => 'clock',
            ],
        ],
    ],

    'programs' => [
        'icon'  => 'layers',
        'label' => 'Programs',
        'children' => [

            [
                'label' => 'Program Overview',
                'route' => 'dean.programs.index',
                'icon'  => 'bar-chart',
                'roles' => ['dean'],
            ],

            [
                'label' => 'Subjects',
                'route' => 'program_head.subjects.index',
                'icon'  => 'book-open',
            ],

            [
                'label' => 'Prospectus',
                'route' => '#',
                'icon'  => 'file-text',
            ],

            [
                'label' => 'Curriculum Mapping',
                'route' => '#',
                'icon'  => 'git-merge',
            ],

            [
                'label' => 'Revisions',
                'route' => '#',
                'icon'  => 'refresh-cw',
            ],

        ],
    ],


        /*
        |--------------------------------------------------------------------------
        | LOGOUT
        |--------------------------------------------------------------------------
        */
        'logout' => [
            'icon' => 'log-out',
            'label' => 'Logout',
            'route' => 'logout',
            'method' => 'post', // special handling
        ],


    

    'curriculum' => [
        'icon'  => 'book',
        'label' => 'Curriculum',
        'children' => [

            [
                'label' => 'Prospectus Review',
                'route' => '#',
                'icon'  => 'file-text',
            ],

            [
                'label' => 'Curriculum Mapping',
                'route' => '#',
                'icon'  => 'git-merge',
            ],

            [
                'label' => 'Revision Requests',
                'route' => '#',
                'icon'  => 'refresh-cw',
            ],

        ],
    ],
        ],
];
