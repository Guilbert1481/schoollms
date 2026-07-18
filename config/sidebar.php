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
            'tools',
            'communication',
            'settings',
            'logout',
        ],

        'finance_manager' => [
            'finance_billing',
            'finance_payments',
            'finance_student_ledgers',
            'finance_tuition_setup',
            'finance_statements',
            'communication',
            'tools',
            'finance_reports',
            'settings',
            'logout',
        ],

        'admission_manager' => [
            'applicants',
            'screening',
            'endorsement',
            'communication',
            'tools',
            'settings',
            'logout',
        ],

        'registrar' => [
            'registrar_validate_enrollment',
            'registrar_student_registry',
            'registrar_teaching_assignments',
            'registrar_subject_credits',
            'registrar_transcripts',
            'communication',
            'tools',
            'settings',
            'logout',
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
            'tools',
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
            'tools',
            'settings',
            'logout',
        ],

        'principal' => [
            'basic_ed_curriculum',
            'students',
            'faculty',
            'principal_discipline',
            'principal_staff',
            'principal_facilities',
            'principal_reports',
            'approvals',
            'communication',
            'tools',
            'settings',
            'logout',
        ],

        'teacher' => [
            'lesson_studio',
            'my_classes',
            'tests',
            'grading',
            'communication',
            'tools',
            'settings',
            'logout',
        ],

        'student' => [
            'schedules_student',
            'academics_student',
            'applications_student',
            'finance_student',
            'services_student',
            'communication',
            'tools',
            'settings',
            'logout',
        ],

        'parent' => [
            'parent_children',
            'settings',
            'logout',
        ],

        'trainee' => [
            'available_courses',
            'training_courses',
            'training_materials',
            'training_sessions',
            'training_assessment',
            'training_progress',
            'training_certificates',
            'communication',
            'tools',
            'logout',
        ],

        'trainor' => [
            'trainor_dashboard',
            'trainor_courses',
            'trainor_sessions',
            'trainor_materials',
            'trainor_trainees',
            'trainor_attendance',
            'tests',
            'trainor_assessments',
            'trainor_gradebook',
            'trainor_certificates',
            'communication',
            'tools',
            'settings',
            'logout',
        ],

        'training_program_head' => [
            'tph_dashboard',
            'tph_training_courses',
            'programs',
            'tph_trainors',
            'communication',
            'tools',
            'settings',
            'logout',
        ],

        'superadmin' => [
            'platform_home',
            'manage_partners',
            'pricing',
            'system_settings_super',
            'my_profile_super',
            'logout',
        ],

        'course_architect' => [
            'ca_workspace',
            'tests',
            'ca_construction',
            'ca_assets',
            'ca_intelligence',
            'communication',
            'tools',
            'settings',
            'logout',
        ],

        'guidance_counselor' => [
            'flagged_interventions',
            'communication',
            'tools',
            'settings',
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
        | SUPERADMIN
        |--------------------------------------------------------------------------
        */
        'platform_home' => [
            'icon' => 'home',
            'label' => 'Platform Home',
            'route' => 'superadmin.dashboard',
        ],

        'manage_partners' => [
            'icon' => 'building-2',
            'label' => 'Manage Partners',
            'route' => 'superadmin.schools.index',
        ],

        'pricing' => [
            'icon' => 'badge-dollar-sign',
            'label' => 'Pricing',
            'route' => 'superadmin.pricing.index',
        ],

        'system_settings_super' => [
            'icon' => 'settings',
            'label' => 'System Settings',
            'route' => '#',
        ],

        'my_profile_super' => [
            'icon' => 'user',
            'label' => 'My Profile',
            'route' => 'superadmin.profile.edit',
        ],

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
                    'icon' => 'id-card',
                ],
                [
                    'label' => 'Enrollment',
                    'route' => '#',
                    'icon' => 'graduation-cap',
                ],
                [
                    'label' => 'Attendance',
                    'route' => '#',
                    'icon' => 'calendar-check',
                ],
                [
                    'label' => 'Lifecycle',
                    'route' => '#',
                    'icon' => 'activity',
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
                    'icon' => 'layers',
                ],
                [
                    'label' => 'Subjects',
                    'route' => '#',
                    'icon' => 'clipboard-list',
                ],
                [
                    'label' => 'Sections',
                    'route' => '#',
                    'icon' => 'layout-grid',
                ],
                [
                    'label' => 'Grading',
                    'route' => '#',
                    'icon' => 'calculator',
                ],
            ],
        ],

        'lesson_studio' => [
            'icon' => 'book-marked',
            'label' => 'Lesson Studio',
            'children' => [
                [
                    'label' => 'Lessons',
                    'route' => 'teacher.lessons.index',
                    'icon' => 'notebook-pen',
                ],
                [
                    'label' => 'Resources',
                    'route' => 'teacher.lessons.resources.index',
                    'icon' => 'folder-open',
                ],
            ],
        ],

        'my_classes' => [
            'icon' => 'book-open',
            'label' => 'My Classes',
            'children' => [

                [
                    'label' => 'Class List',
                    'route' => '#',
                    'icon' => 'list',
                ],

                [
                    'label' => 'Attendance',
                    'route' => 'teacher.attendance.index',
                    'icon' => 'calendar-check',
                ],

                [
                    'label' => 'Grades',
                    'route' => 'teacher.gradebook.index',
                    'icon' => 'clipboard-list',
                ],

                [
                    'label' => 'Homework',
                    'route' => 'teacher.homework.index',
                    'icon' => 'file-text',
                ],

                [
                    'label' => 'Students',
                    'route' => '#',
                    'icon' => 'users',
                ],

            ],
        ],

        'tests' => [
            'icon' => 'clipboard-check', // or 'file-question'
            'label' => 'Tests',
            'children' => [

                [
                    'label' => 'Create Questions',
                    'route' => 'teacher.question.metadata',
                    'icon' => 'plus-circle',
                ],

                [
                    'label' => 'Question Bank',
                    'route' => 'teacher.question_bank.index',
                    'active' => 'teacher.question_bank.index',
                    'icon' => 'database',
                ],

                [
                    'label' => 'Test Builder',
                    'route' => 'teacher.tests.create',
                    'icon' => 'layout',
                ],

                [
                    'label' => 'Test Management',
                    'route' => 'teacher.tests.management',
                    'active' => 'teacher.tests.management',
                    'icon' => 'settings-2',
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
                    'icon' => 'users-2',
                ],
                [
                    'label' => 'Teaching Load',
                    'route' => '#',
                    'icon' => 'clipboard-check',
                ],
                [
                    'label' => 'Evaluation',
                    'route' => '#',
                    'icon' => 'star',
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
                    'route' => 'finance.billing.index',
                    'active' => 'finance.billing.*',
                    'icon' => 'receipt',
                ],
                [
                    'label' => 'Enrollment Queue',
                    'route' => 'finance.enrollment-queue.index',
                    'active' => 'finance.enrollment-queue.*',
                    'icon' => 'user-check',
                ],
                [
                    'label' => 'Payments',
                    'route' => 'finance.payments.index',
                    'active' => 'finance.payments.*',
                    'icon' => 'credit-card',
                ],
                [
                    'label' => 'Outstanding Accounts',
                    'route' => '#',
                    'icon' => 'alert-circle',
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
                    'icon' => 'presentation',
                ],
                [
                    'label' => 'Student Reports',
                    'route' => '#',
                    'icon' => 'file-bar-chart',
                ],
                [
                    'label' => 'Faculty Reports',
                    'route' => '#',
                    'icon' => 'pie-chart',
                ],
                [
                    'label' => 'Financial Reports',
                    'route' => '#',
                    'icon' => 'line-chart',
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | TOOLS
        |--------------------------------------------------------------------------
        */
        'tools' => [
            'icon' => 'wrench',
            'label' => 'Tools',
            'route' => 'tools.index',
            'roles' => ['admin', 'principal', 'dean'],
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
                    'label' => 'Applications',
                    'route' => 'admission.applicants',
                    'active' => 'admission.applicants',
                    'icon' => 'file-text',
                    'roles' => ['admission_manager', 'finance_manager'],
                ],
                [
                    'label' => 'Applicant Directory',
                    'route' => 'admission.applicant-directory',
                    'active' => 'admission.applicant-directory*',
                    'icon' => 'users',
                    'roles' => ['admission_manager'],
                ],
                [
                    'label' => 'Requirements',
                    'route' => '#',
                    'icon' => 'folder-check',
                    'roles' => ['admission_manager'],
                ],
            ],
        ],

        'registrar_validate_enrollment' => [
            'icon' => 'check-square',
            'label' => 'Validate Enrollment',
            'route' => 'registrar.enrollments.index',
            'active' => 'registrar.enrollments.*',
            'roles' => ['registrar'],
        ],

        /*
        |--------------------------------------------------------------------------
        | PARENT PORTAL
        |--------------------------------------------------------------------------
        */
        'parent_children' => [
            'icon' => 'users',
            'label' => 'My Children',
            'route' => 'parent.dashboard',
            'active' => 'parent.*',
            'roles' => ['parent'],
        ],

        'registrar_student_registry' => [
            'icon' => 'id-card',
            'label' => 'Student Registry',
            'route' => 'registrar.student-registry.index',
            'active' => 'registrar.student-registry.*',
            'roles' => ['registrar'],
        ],

        'registrar_teaching_assignments' => [
            'icon' => 'user-check',
            'label' => 'Teaching Assignments',
            'route' => 'registrar.teaching-assignments.index',
            'active' => 'registrar.teaching-assignments.*',
            'roles' => ['registrar'],
        ],

        'registrar_subject_credits' => [
            'icon' => 'book-open',
            'label' => 'Subject Credit Evaluation',
            'route' => 'registrar.subject-credits.index',
            'active' => 'registrar.subject-credits.*',
            'roles' => ['registrar'],
        ],

        'registrar_transcripts' => [
            'icon' => 'scroll-text',
            'label' => 'Transcript of Records',
            'route' => 'registrar.transcripts.index',
            'active' => 'registrar.transcripts.*',
            'roles' => ['registrar'],
        ],

        /*
        |--------------------------------------------------------------------------
        | FINANCE MANAGER MODULES
        |--------------------------------------------------------------------------
        */
        'finance_billing' => [
            'icon' => 'receipt',
            'label' => 'Billing',
            'route' => 'finance.billing.index',
            'active' => 'finance.billing.index',
            'roles' => ['finance_manager'],
        ],

        'finance_payments' => [
            'icon' => 'credit-card',
            'label' => 'Payments',
            'route' => 'finance.payments.index',
            'active' => 'finance.payments.*',
            'roles' => ['finance_manager'],
        ],

        'finance_student_ledgers' => [
            'icon' => 'book',
            'label' => 'Student Ledgers',
            'route' => 'finance.ledger.index',
            'active' => 'finance.ledger.*',
            'roles' => ['finance_manager'],
        ],

        'finance_statements' => [
            'icon' => 'receipt',
            'label' => 'SOA',
            'route' => 'finance.statements.index',
            'active' => 'finance.statements.*',
            'roles' => ['finance_manager'],
        ],

        'finance_tuition_setup' => [
            'icon' => 'sliders',
            'label' => 'Tuition & Fees',
            'route' => 'finance.tuition-setup.index',
            'active' => 'finance.tuition-setup.*',
            'roles' => ['finance_manager'],
        ],

        'finance_reports' => [
            'icon' => 'bar-chart-3',
            'label' => 'Reports',
            'route' => '#',
            'roles' => ['finance_manager'],
        ],

        'screening' => [
            'icon' => 'clipboard-check',
            'label' => 'Screening',
            'children' => [
                [
                    'label' => 'Entrance Exams',
                    'route' => '#',
                    'icon' => 'edit-3',
                ],
                [
                    'label' => 'Interviews',
                    'route' => '#',
                    'icon' => 'mic',
                ],
                [
                    'label' => 'Approval Decisions',
                    'route' => '#',
                    'icon' => 'check-circle',
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
                    'icon' => 'arrow-right-circle',
                ],
                [
                    'label' => 'Rejected Applications',
                    'route' => '#',
                    'icon' => 'x-circle',
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
            'route' => 'communication.chat.index',
            'roles' => ['admin', 'dean', 'principal', 'admission_manager', 'finance_manager', 'registrar', 'teacher', 'student', 'trainee', 'guidance_counselor'],
        ],

        /*
        |--------------------------------------------------------------------------
        | FLAGGED INTERVENTIONS (Guidance Counselor)
        |--------------------------------------------------------------------------
        */
        'flagged_interventions' => [
            'icon' => 'shield-alert',
            'label' => 'Flagged Interventions',
            'route' => 'guidance_counselor.flagged.index',
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
                    'icon' => 'users',
                    'roles' => ['admin'],
                ],

                [
                    'label' => 'Assignment Management',
                    'route' => 'admin.assignments.indexColleges',
                    'active' => 'admin.assignments.*',
                    'icon' => 'git-branch',
                    'roles' => ['admin'],
                ],

                [
                    'label' => 'Service Plan',
                    'route' => 'admin.service-plan.features',
                    'active' => 'admin.service-plan.*',
                    'icon' => 'credit-card',
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
                    'label' => 'School Settings',
                    'route' => 'school.settings.system.index',
                    'icon' => 'user',
                    'roles' => ['admin'],
                ],

                [
                    'label' => 'Student ID',
                    'route' => 'registrar.settings.student-id.edit',
                    'active' => 'registrar.settings.student-id.*',
                    'icon' => 'id-card',
                    'roles' => ['registrar'],
                ],

                [
                    'label' => 'Documents',
                    'route' => 'registrar.settings.documents.index',
                    'active' => 'registrar.settings.documents.*',
                    'icon' => 'file-check',
                    'roles' => ['registrar'],
                ],

                [
                    'label' => 'Parent Portal',
                    'route' => 'registrar.settings.parent-portal.index',
                    'active' => 'registrar.settings.parent-portal.*',
                    'icon' => 'users',
                    'roles' => ['registrar'],
                ],

                [
                    'label' => 'Grades',
                    'route' => 'principal.settings.grades',
                    'active' => 'principal.settings.grades',
                    'icon' => 'graduation-cap',
                    'roles' => ['principal'],
                ],

                [
                    'label' => 'Attendance',
                    'route' => 'principal.settings.attendance',
                    'active' => 'principal.settings.attendance',
                    'icon' => 'calendar-check',
                    'roles' => ['principal'],
                ],

                [
                    'label' => 'Grading Scheme',
                    'route' => 'principal.settings.grading',
                    'active' => 'principal.settings.grading',
                    'icon' => 'calculator',
                    'roles' => ['principal'],
                ],

                [
                    'label' => 'Attendance',
                    'route' => 'dean.settings.attendance',
                    'active' => 'dean.settings.attendance',
                    'icon' => 'calendar-check',
                    'roles' => ['dean'],
                ],

                [
                    'label' => 'Grading Scheme',
                    'route' => 'dean.settings.grading',
                    'active' => 'dean.settings.grading',
                    'icon' => 'calculator',
                    'roles' => ['dean'],
                ],

                [
                    'label' => 'Profile Settings',
                    'route' => 'settings.profile',

                    'icon' => 'user',
                    'roles' => ['admin', 'dean', 'principal', 'admission_manager', 'teacher', 'student', 'registrar', 'program_head', 'trainer', 'trainee', 'course_architect', 'guidance_counselor', 'superadmin', 'parent'],
                ],

                [
                    'label' => 'System Logs',
                    'route' => '#',
                    'icon' => 'file-text',
                    'roles' => ['admin', 'dean'],
                ],

                [
                    'label' => 'Account Access',
                    'route' => 'school.account-access.index',
                    'icon' => 'users',
                    'roles' => ['admin'],
                ],

                [
                    'label' => 'Enrollment Settings',
                    'route' => 'admission.enrollment-settings.index',
                    'icon' => 'graduation-cap',
                    'roles' => ['admission_manager'],
                ],

                [
                    'label' => 'Sections (Publish)',
                    'route' => 'admission.sections.index',
                    'icon' => 'layers',
                    'roles' => ['admission_manager'],
                ],

                [
                    'label' => 'Master Data',
                    'route' => 'school.settings.master-data.academic_year.index',
                    'icon' => 'graduation-cap',
                    'roles' => ['admin'],
                ],

                /* ---- Finance Manager settings ---- */
                [
                    'label' => 'Transaction Types',
                    'route' => 'finance.settings.transaction-types',
                    'active' => 'finance.settings.transaction-types',
                    'icon' => 'tags',
                    'roles' => ['finance_manager'],
                ],
                [
                    'label' => 'Finance Preferences',
                    'route' => 'finance.settings.preferences',
                    'active' => 'finance.settings.preferences*',
                    'icon' => 'sliders',
                    'roles' => ['finance_manager'],
                ],
                [
                    'label' => 'Email',
                    'route' => 'finance.settings.email',
                    'active' => 'finance.settings.email*',
                    'icon' => 'mail',
                    'roles' => ['finance_manager'],
                ],
            ], // closes children
        ], // closes settings

        /*
    |--------------------------------------------------------------------------
    | SHARED MENU ITEMS (Reusable)
    |--------------------------------------------------------------------------
    */

        'enrollment_growth' => [
            'icon' => 'trending-up',
            'label' => 'Enrollment & Growth',
            'children' => [
                [
                    'label' => 'Overview',
                    'route' => '#',
                    'icon' => 'bar-chart',
                ],
            ],
        ],

        'student_success' => [
            'icon' => 'users',
            'label' => 'Student Success',
            'children' => [
                [
                    'label' => 'Performance',
                    'route' => '#',
                    'icon' => 'activity',
                ],
            ],
        ],

        'financial_performance' => [
            'icon' => 'dollar-sign',
            'label' => 'Financial Performance',
            'children' => [
                [
                    'label' => 'Overview',
                    'route' => '#',
                    'icon' => 'line-chart',
                ],
            ],
        ],

        'quality_assurance' => [
            'icon' => 'shield',
            'label' => 'Quality Assurance',
            'children' => [
                [
                    'label' => 'Reports',
                    'route' => '#',
                    'icon' => 'clipboard',
                ],
            ],
        ],

        'approvals' => [
            'icon' => 'check-square',
            'label' => 'Approvals',
            'children' => [
                [
                    'label' => 'Pending Approvals',
                    'route' => '#',
                    'icon' => 'clock',
                ],
            ],
        ],

        'programs' => [
            'icon' => 'layers',
            'label' => 'Programs',
            'children' => [

                [
                    'label' => 'Program Overview',
                    'route' => 'dean.programs.index',
                    'icon' => 'bar-chart',
                    'roles' => ['dean'],
                ],

                [
                    'label' => 'Curricula',
                    'route' => 'dean.curricula-panel.index',
                    'icon' => 'book',
                    'roles' => ['dean'],
                ],

                [
                    'label' => 'Academic Policies',
                    'route' => 'dean.academic_policies.index',
                    'icon' => 'shield',
                    'roles' => ['dean'],
                ],

                [
                    'label' => 'Subjects',
                    'route' => 'program_head.subjects.index',
                    'icon' => 'book-open',
                    'roles' => ['program_head', 'training_program_head'],
                ],

                [
                    'label' => 'Prospectus',
                    'route' => '#',
                    'icon' => 'file-text',
                    'roles' => ['program_head'],
                ],

                [
                    'label' => 'Curriculum Mapping',
                    'route' => '#',
                    'icon' => 'git-merge',
                    'roles' => ['program_head'],
                ],

                [
                    'label' => 'Revisions',
                    'route' => '#',
                    'icon' => 'refresh-cw',
                    'roles' => ['program_head'],
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

        /*
        |--------------------------------------------------------------------------
        | COURSE ARCHITECT
        |--------------------------------------------------------------------------
        */
        'ca_workspace' => [
            'icon' => 'layout-dashboard',
            'label' => 'Workspace',
            'children' => [

                [
                    'label' => 'Path Visualizer',
                    'route' => 'course-architect.path-visualizer.index',
                    'icon' => 'git-branch',
                ],
                [
                    'label' => 'Preview Mode',
                    'route' => 'course-architect.preview.index',
                    'icon' => 'play-circle',
                ],
            ],
        ],

        'ca_construction' => [
            'icon' => 'wrench',
            'label' => 'Construction',
            'children' => [
                [
                    'label' => 'Lesson Studio',
                    'route' => 'course-architect.lesson-studio.index',
                    'icon' => 'notebook-pen',
                ],
                [
                    'label' => 'Assessment Lab',
                    'route' => 'course-architect.assessment-lab.index',
                    'icon' => 'flask-conical',
                ],
            ],
        ],

        'ca_assets' => [
            'icon' => 'archive',
            'label' => 'Assets',
            'children' => [
                [
                    'label' => 'Resource Vault',
                    'route' => 'course-architect.resource-vault.index',
                    'icon' => 'folder-lock',
                ],
                [
                    'label' => 'Media Optimizer',
                    'route' => 'course-architect.media-optimizer.index',
                    'icon' => 'film',
                ],
            ],
        ],

        'ca_intelligence' => [
            'icon' => 'brain-circuit',
            'label' => 'Intelligence',
            'children' => [
                [
                    'label' => 'Learning Analytics',
                    'route' => 'course-architect.learning-analytics.index',
                    'icon' => 'line-chart',
                ],
                [
                    'label' => 'Production Reports',
                    'route' => 'course-architect.production-reports.index',
                    'icon' => 'clipboard-check',
                ],
            ],
        ],

        // Direct link to the tabbed curriculum page (Subjects Masterlist /
        // Grade Level Subjects / AY & Sessions live as tabs inside it), so no
        // sub-items are needed in the sidebar.
        'basic_ed_curriculum' => [
            'icon' => 'graduation-cap',
            'label' => 'Curriculum',
            'route' => 'principal.curricula-panel.subjects',
        ],

        // Placeholder nav items (pages not built yet) — route '#' so the sidebar
        // never breaks. Point each at a real route once its page exists.
        'principal_discipline' => [
            'icon' => 'shield-alert',
            'label' => 'Discipline',
            'route' => '#',
        ],
        'principal_staff' => [
            'icon' => 'users',
            'label' => 'Staff',
            'route' => '#',
        ],
        'principal_facilities' => [
            'icon' => 'building-2',
            'label' => 'Facilities',
            'route' => '#',
        ],
        'principal_reports' => [
            'icon' => 'bar-chart-3',
            'label' => 'Reports',
            'route' => '#',
        ],

        'curriculum' => [
            'icon' => 'book',
            'label' => 'Curriculum',
            'children' => [

                [
                    'label' => 'Prospectus Review',
                    'route' => '#',
                    'icon' => 'file-text',
                ],

                [
                    'label' => 'Curriculum Mapping',
                    'route' => '#',
                    'icon' => 'git-merge',
                ],

                [
                    'label' => 'Revision Requests',
                    'route' => '#',
                    'icon' => 'refresh-cw',
                ],

            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | STUDENT SPECIFIC MODULES (Non-Shared)
        |--------------------------------------------------------------------------
        */
        'schedules_student' => [
            'icon' => 'book',
            'label' => 'My Schedule',
            'route' => '#',
        ],

        'academics_student' => [
            'icon' => 'graduation-cap',
            'label' => 'Academics',
            'children' => [
                [
                    'label' => 'My Subjects',
                    'route' => 'student.subjects.index',
                    'icon' => 'book',
                ],
                [
                    'label' => 'Grades',
                    'route' => 'student.report-card',
                    'active' => 'student.report-card',
                    'icon' => 'file-text',
                ],
                [
                    'label' => 'Transcript',
                    'route' => 'student.transcript.index',
                    'icon' => 'scroll-text',
                ],
                [
                    'label' => 'Homework',
                    'route' => 'student.homework.index',
                    'active' => 'student.homework.*',
                    'icon' => 'clipboard-list',
                ],
                [
                    'label' => 'Attendance',
                    'route' => '#',
                    'icon' => 'user-check',
                    // Logic note: You can later hide this if user is 'modular'
                ],
            ],
        ],

        'finance_student' => [
            'icon' => 'wallet',
            'label' => 'Finance',
            'children' => [
                [
                    'label' => 'Balance & SOA',
                    'route' => 'student.finance.index',
                    'active' => 'student.finance.index',
                    'icon' => 'receipt',
                ],
                [
                    'label' => 'Payment History',
                    'route' => 'student.finance.payments',
                    'active' => 'student.finance.payments',
                    'icon' => 'history',
                ],
                [
                    'label' => 'Billing Details',
                    'route' => 'student.finance.invoices',
                    'active' => 'student.finance.invoices',
                    'icon' => 'receipt-text',
                ],
            ],
        ],

        'services_student' => [
            'icon' => 'git-pull-request',
            'label' => 'Services',
            'children' => [
                [
                    'label' => 'Modality Request',
                    'route' => '#',
                    'icon' => 'refresh-cw',
                ],
                [
                    'label' => 'Document Request',
                    'route' => '#',
                    'icon' => 'file-plus',
                ],
                [
                    'label' => 'Clearance Status',
                    'route' => '#',
                    'icon' => 'check-circle',
                ],
            ],
        ],

        'applications_student' => [
            'icon' => 'clipboard-list',
            'label' => 'Applications',
            'route' => 'student.applications.index',
        ],

        /*
        |--------------------------------------------------------------------------
        | TRAINEE SPECIFIC MODULES (Non-Shared)
        |--------------------------------------------------------------------------
        */
        'available_courses' => [
            'icon' => 'layout-grid',
            'label' => 'Available Courses',
            'route' => 'training.trainee.available-courses',
        ],

        'training_courses' => [
            'icon' => 'book-open',
            'label' => 'My Courses',
            'route' => 'training.trainee.courses',
        ],

        'training_materials' => [
            'icon' => 'file-text',
            'label' => 'Materials',
            'route' => 'training.trainee.materials',
        ],

        'training_sessions' => [
            'icon' => 'calendar',
            'label' => 'Sessions',
            'route' => 'training.trainee.sessions',
        ],

        'training_assessment' => [
            'icon' => 'clipboard-check',
            'label' => 'Assessment',
            'route' => 'training.trainee.assessment',
        ],

        'training_progress' => [
            'icon' => 'bar-chart-3',
            'label' => 'Progress',
            'route' => 'training.trainee.progress',
        ],

        'training_certificates' => [
            'icon' => 'award',
            'label' => 'Certificates',
            'route' => 'training.trainee.certificates',
        ],

        /*
        |--------------------------------------------------------------------------
        | TRAINOR SPECIFIC MODULES (Non-Shared)
        |--------------------------------------------------------------------------
        */

        'trainor_courses' => [
            'icon' => 'book-open',
            'label' => 'My Courses',
            'route' => 'training.trainor.courses',
        ],

        /*
        |--------------------------------------------------------------------------
        | TRAINING PROGRAM HEAD MODULES
        |--------------------------------------------------------------------------
        */

        'tph_training_courses' => [
            'icon' => 'book',
            'label' => 'Training Courses',
            'route' => 'training.program_head.courses.index',
        ],

        'tph_trainors' => [
            'icon' => 'users',
            'label' => 'Trainors',
            'route' => 'training.program_head.trainors.index',
        ],

        'trainor_sessions' => [
            'icon' => 'calendar',
            'label' => 'Sessions',
            'route' => 'training.trainor.sessions',
        ],

        'trainor_materials' => [
            'icon' => 'file-text',
            'label' => 'Materials',
            'route' => 'training.trainor.materials',
        ],

        'trainor_trainees' => [
            'icon' => 'users',
            'label' => 'Trainees',
            'route' => 'training.trainor.trainees',
        ],

        'trainor_attendance' => [
            'icon' => 'check-square',
            'label' => 'Attendance',
            'route' => 'training.trainor.attendance',
        ],

        'trainor_assessments' => [
            'icon' => 'clipboard-check',
            'label' => 'Assessments',
            'route' => 'training.trainor.assessments',
        ],

        'trainor_gradebook' => [
            'icon' => 'bar-chart-3',
            'label' => 'Gradebook',
            'route' => 'training.trainor.gradebook',
        ],

        'trainor_certificates' => [
            'icon' => 'award',
            'label' => 'Certificates',
            'route' => 'training.trainor.certificates',
        ],

    ],
];
