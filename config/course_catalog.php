<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Course Catalog
    |--------------------------------------------------------------------------
    | Generic catalog used by the Trainee → Available Courses flow.
    |
    | A "catalog" is a named group of programs. Each program has a list of
    | subjects grouped by category. The flow picks a catalog for a given
    | EnrollmentSetting using CatalogResolver (see App\Helpers\CourseCatalog):
    |
    |   1. Match by session name/title slug (e.g. "LET Review April 2026"
    |      → "let-review-april-2026" → try "let-review", "let_review").
    |   2. Match by EnrollmentType name slug (e.g. "Seminar" → "seminar").
    |   3. Fallback to `default_catalog`.
    */

    'default_catalog' => 'generic',

    /*
    | Category labels are shared across all catalogs so the tab bar is
    | consistent. You can add more categories — just use their keys in a
    | program's `categories` array and each subject's `category` field.
    */
    'category_labels' => [
        'all'            => 'All',
        'gen_ed'         => 'General Education',
        'prof_ed'        => 'Professional Education',
        'specialization' => 'Specialization',
        'professional'   => 'Professional',
        'sub_pro'        => 'Sub-Professional',
        'core'           => 'Core Modules',
        'elective'       => 'Electives',
    ],

    'catalogs' => [

        /*
        |------------------------------------------------------------------
        | LET REVIEW
        |------------------------------------------------------------------
        */
        'let-review' => [
            'label'    => 'LET Review',
            'programs' => [

                'beed' => [
                    'code'        => 'BEED',
                    'name'        => 'Bachelor of Elementary Education',
                    'description' => 'Review for aspiring elementary school teachers. Covers General Education and Professional Education strands required for the LET.',
                    'categories'  => ['all', 'gen_ed', 'prof_ed'],
                    'subjects' => [
                        ['code' => 'ENG',    'name' => 'English',                          'category' => 'gen_ed',  'topics' => 12, 'questions' => 150, 'amount' => 350.00],
                        ['code' => 'FIL',    'name' => 'Filipino',                         'category' => 'gen_ed',  'topics' => 10, 'questions' => 120, 'amount' => 350.00],
                        ['code' => 'MATH',   'name' => 'Mathematics',                      'category' => 'gen_ed',  'topics' => 15, 'questions' => 180, 'amount' => 400.00],
                        ['code' => 'SCI',    'name' => 'Science',                          'category' => 'gen_ed',  'topics' => 14, 'questions' => 170, 'amount' => 400.00],
                        ['code' => 'SS',     'name' => 'Social Studies',                   'category' => 'gen_ed',  'topics' => 11, 'questions' => 140, 'amount' => 350.00],
                        ['code' => 'PRINC',  'name' => 'Principles of Teaching',           'category' => 'prof_ed', 'topics' => 9,  'questions' => 110, 'amount' => 380.00],
                        ['code' => 'CURR',   'name' => 'Curriculum Development',           'category' => 'prof_ed', 'topics' => 8,  'questions' => 100, 'amount' => 380.00],
                        ['code' => 'ASSESS', 'name' => 'Assessment of Learning',           'category' => 'prof_ed', 'topics' => 10, 'questions' => 120, 'amount' => 380.00],
                        ['code' => 'CHILD',  'name' => 'Child and Adolescent Development', 'category' => 'prof_ed', 'topics' => 9,  'questions' => 115, 'amount' => 380.00],
                        ['code' => 'FACIL',  'name' => 'Facilitating Learning',            'category' => 'prof_ed', 'topics' => 8,  'questions' => 100, 'amount' => 380.00],
                    ],
                ],

                'bsed' => [
                    'code'        => 'BSED',
                    'name'        => 'Bachelor of Secondary Education',
                    'description' => 'Review for aspiring high school teachers. Covers General, Professional, and Specialization strands required for the LET.',
                    'categories'  => ['all', 'gen_ed', 'prof_ed', 'specialization'],
                    'subjects' => [
                        ['code' => 'ENG',     'name' => 'English',                          'category' => 'gen_ed',         'topics' => 12, 'questions' => 150, 'amount' => 350.00],
                        ['code' => 'FIL',     'name' => 'Filipino',                         'category' => 'gen_ed',         'topics' => 10, 'questions' => 120, 'amount' => 350.00],
                        ['code' => 'MATH',    'name' => 'Mathematics',                      'category' => 'gen_ed',         'topics' => 15, 'questions' => 180, 'amount' => 400.00],
                        ['code' => 'SCI',     'name' => 'Science',                          'category' => 'gen_ed',         'topics' => 14, 'questions' => 170, 'amount' => 400.00],
                        ['code' => 'SS',      'name' => 'Social Studies',                   'category' => 'gen_ed',         'topics' => 11, 'questions' => 140, 'amount' => 350.00],
                        ['code' => 'PRINC',   'name' => 'Principles of Teaching',           'category' => 'prof_ed',        'topics' => 9,  'questions' => 110, 'amount' => 380.00],
                        ['code' => 'CURR',    'name' => 'Curriculum Development',           'category' => 'prof_ed',        'topics' => 8,  'questions' => 100, 'amount' => 380.00],
                        ['code' => 'ASSESS',  'name' => 'Assessment of Learning',           'category' => 'prof_ed',        'topics' => 10, 'questions' => 120, 'amount' => 380.00],
                        ['code' => 'CHILD',   'name' => 'Child and Adolescent Development', 'category' => 'prof_ed',        'topics' => 9,  'questions' => 115, 'amount' => 380.00],
                        ['code' => 'FACIL',   'name' => 'Facilitating Learning',            'category' => 'prof_ed',        'topics' => 8,  'questions' => 100, 'amount' => 380.00],
                        ['code' => 'SP-MATH', 'name' => 'Specialization: Mathematics',      'category' => 'specialization', 'topics' => 16, 'questions' => 200, 'amount' => 450.00],
                        ['code' => 'SP-ENG',  'name' => 'Specialization: English',          'category' => 'specialization', 'topics' => 14, 'questions' => 180, 'amount' => 450.00],
                        ['code' => 'SP-SCI',  'name' => 'Specialization: Biological Science','category'=> 'specialization', 'topics' => 15, 'questions' => 185, 'amount' => 450.00],
                        ['code' => 'SP-SS',   'name' => 'Specialization: Social Studies',   'category' => 'specialization', 'topics' => 13, 'questions' => 160, 'amount' => 450.00],
                        ['code' => 'SP-FIL',  'name' => 'Specialization: Filipino',         'category' => 'specialization', 'topics' => 12, 'questions' => 150, 'amount' => 450.00],
                        ['code' => 'SP-TLE',  'name' => 'Specialization: TLE',              'category' => 'specialization', 'topics' => 14, 'questions' => 170, 'amount' => 450.00],
                    ],
                ],
            ],
        ],

        /*
        |------------------------------------------------------------------
        | CIVIL SERVICE REVIEW
        |------------------------------------------------------------------
        */
        'civil-service-review' => [
            'label'    => 'Civil Service Review',
            'programs' => [

                'professional' => [
                    'code'        => 'CSE-PRO',
                    'name'        => 'Civil Service — Professional',
                    'description' => 'Comprehensive review for the Civil Service Professional Exam.',
                    'categories'  => ['all', 'professional'],
                    'subjects' => [
                        ['code' => 'VERBAL', 'name' => 'Verbal Reasoning',       'category' => 'professional', 'topics' => 8,  'questions' => 120, 'amount' => 300.00],
                        ['code' => 'NUM',    'name' => 'Numerical Reasoning',    'category' => 'professional', 'topics' => 10, 'questions' => 150, 'amount' => 300.00],
                        ['code' => 'ANALYT', 'name' => 'Analytical Reasoning',   'category' => 'professional', 'topics' => 9,  'questions' => 130, 'amount' => 300.00],
                        ['code' => 'CLERIC', 'name' => 'Clerical Operations',    'category' => 'professional', 'topics' => 7,  'questions' => 100, 'amount' => 300.00],
                        ['code' => 'GENINFO','name' => 'General Information',    'category' => 'professional', 'topics' => 6,  'questions' => 90,  'amount' => 300.00],
                    ],
                ],

                'sub-professional' => [
                    'code'        => 'CSE-SUB',
                    'name'        => 'Civil Service — Sub-Professional',
                    'description' => 'Targeted review for the Civil Service Sub-Professional Exam.',
                    'categories'  => ['all', 'sub_pro'],
                    'subjects' => [
                        ['code' => 'VERBAL', 'name' => 'Verbal Reasoning',     'category' => 'sub_pro', 'topics' => 7,  'questions' => 100, 'amount' => 250.00],
                        ['code' => 'NUM',    'name' => 'Numerical Reasoning',  'category' => 'sub_pro', 'topics' => 8,  'questions' => 120, 'amount' => 250.00],
                        ['code' => 'CLERIC', 'name' => 'Clerical Operations',  'category' => 'sub_pro', 'topics' => 6,  'questions' => 90,  'amount' => 250.00],
                        ['code' => 'GENINFO','name' => 'General Information',  'category' => 'sub_pro', 'topics' => 5,  'questions' => 80,  'amount' => 250.00],
                    ],
                ],
            ],
        ],

        /*
        |------------------------------------------------------------------
        | SEMINAR
        |------------------------------------------------------------------
        */
        'seminar' => [
            'label'    => 'Seminar',
            'programs' => [

                'teacher-development' => [
                    'code'        => 'SEM-TD',
                    'name'        => 'Teacher Development Seminar',
                    'description' => 'Short seminars for in-service teachers — pedagogy, assessment, and classroom management.',
                    'categories'  => ['all', 'core', 'elective'],
                    'subjects' => [
                        ['code' => 'PEDA',    'name' => 'Modern Pedagogy',           'category' => 'core',     'topics' => 4, 'questions' => 40, 'amount' => 500.00],
                        ['code' => 'ASSESS',  'name' => 'Classroom Assessment',      'category' => 'core',     'topics' => 4, 'questions' => 40, 'amount' => 500.00],
                        ['code' => 'MGMT',    'name' => 'Classroom Management',      'category' => 'core',     'topics' => 3, 'questions' => 30, 'amount' => 500.00],
                        ['code' => 'DIGI',    'name' => 'Digital Tools for Teachers','category' => 'elective', 'topics' => 5, 'questions' => 50, 'amount' => 600.00],
                        ['code' => 'SEL',     'name' => 'Social-Emotional Learning', 'category' => 'elective', 'topics' => 4, 'questions' => 40, 'amount' => 600.00],
                    ],
                ],

                'student-development' => [
                    'code'        => 'SEM-SD',
                    'name'        => 'Student Development Seminar',
                    'description' => 'Career, leadership, and study-skills seminars for students.',
                    'categories'  => ['all', 'core', 'elective'],
                    'subjects' => [
                        ['code' => 'STUDY',   'name' => 'Study Skills',          'category' => 'core',     'topics' => 3, 'questions' => 30, 'amount' => 250.00],
                        ['code' => 'LEAD',    'name' => 'Leadership',            'category' => 'core',     'topics' => 4, 'questions' => 40, 'amount' => 250.00],
                        ['code' => 'CAREER',  'name' => 'Career Planning',       'category' => 'core',     'topics' => 3, 'questions' => 30, 'amount' => 250.00],
                        ['code' => 'SPEAK',   'name' => 'Public Speaking',       'category' => 'elective', 'topics' => 4, 'questions' => 40, 'amount' => 300.00],
                        ['code' => 'FIN',     'name' => 'Financial Literacy',    'category' => 'elective', 'topics' => 5, 'questions' => 50, 'amount' => 300.00],
                    ],
                ],
            ],
        ],

        /*
        |------------------------------------------------------------------
        | TRAINING
        |------------------------------------------------------------------
        */
        'training' => [
            'label'    => 'Training',
            'programs' => [

                'technical-skills' => [
                    'code'        => 'TR-TECH',
                    'name'        => 'Technical Skills Training',
                    'description' => 'Hands-on, skill-based training across technical disciplines.',
                    'categories'  => ['all', 'core', 'elective'],
                    'subjects' => [
                        ['code' => 'MS-OFFICE', 'name' => 'Microsoft Office Essentials', 'category' => 'core',     'topics' => 6, 'questions' => 60,  'amount' => 700.00],
                        ['code' => 'WEB',       'name' => 'Intro to Web Development',    'category' => 'core',     'topics' => 8, 'questions' => 80,  'amount' => 900.00],
                        ['code' => 'DB',        'name' => 'Database Fundamentals',       'category' => 'core',     'topics' => 7, 'questions' => 70,  'amount' => 900.00],
                        ['code' => 'DATA',     'name' => 'Data Analytics with Excel',    'category' => 'elective', 'topics' => 6, 'questions' => 60,  'amount' => 800.00],
                        ['code' => 'NET',       'name' => 'Basic Networking',            'category' => 'elective', 'topics' => 5, 'questions' => 50,  'amount' => 800.00],
                    ],
                ],

                'professional-skills' => [
                    'code'        => 'TR-PROF',
                    'name'        => 'Professional Skills Training',
                    'description' => 'Soft-skills and workplace-readiness training programs.',
                    'categories'  => ['all', 'core', 'elective'],
                    'subjects' => [
                        ['code' => 'COMM', 'name' => 'Business Communication',   'category' => 'core',     'topics' => 4, 'questions' => 40, 'amount' => 500.00],
                        ['code' => 'TEAM', 'name' => 'Teamwork & Collaboration', 'category' => 'core',     'topics' => 3, 'questions' => 30, 'amount' => 500.00],
                        ['code' => 'LEAD', 'name' => 'Leadership Essentials',    'category' => 'elective', 'topics' => 4, 'questions' => 40, 'amount' => 550.00],
                        ['code' => 'TIME', 'name' => 'Time Management',          'category' => 'elective', 'topics' => 3, 'questions' => 30, 'amount' => 500.00],
                    ],
                ],
            ],
        ],

        /*
        |------------------------------------------------------------------
        | GENERIC (Fallback) — shown when no catalog matches the session
        |------------------------------------------------------------------
        */
        'generic' => [
            'label'    => 'Courses',
            'programs' => [

                'standard' => [
                    'code'        => 'STD',
                    'name'        => 'Standard Program',
                    'description' => 'Default set of subjects offered for this session.',
                    'categories'  => ['all', 'core', 'elective'],
                    'subjects' => [
                        ['code' => 'CORE1', 'name' => 'Core Subject 1', 'category' => 'core',     'topics' => 5, 'questions' => 50, 'amount' => 300.00],
                        ['code' => 'CORE2', 'name' => 'Core Subject 2', 'category' => 'core',     'topics' => 5, 'questions' => 50, 'amount' => 300.00],
                        ['code' => 'ELEC1', 'name' => 'Elective 1',     'category' => 'elective', 'topics' => 4, 'questions' => 40, 'amount' => 350.00],
                        ['code' => 'ELEC2', 'name' => 'Elective 2',     'category' => 'elective', 'topics' => 4, 'questions' => 40, 'amount' => 350.00],
                    ],
                ],
            ],
        ],

    ],

    /*
    | Aliases: alternative keys that should resolve to a canonical catalog.
    | Used by App\Helpers\CourseCatalog::resolveForSession() when matching
    | a session's name/title/enrollment-type to a catalog key.
    */
    'aliases' => [
        'let_review'            => 'let-review',
        'let'                   => 'let-review',
        'licensure'             => 'let-review',
        'civil_service_review'  => 'civil-service-review',
        'civil_service'         => 'civil-service-review',
        'cse'                   => 'civil-service-review',
        'seminars'              => 'seminar',
        'trainings'             => 'training',
        'review_classes'        => 'let-review',
        'review'                => 'let-review',
    ],
];
