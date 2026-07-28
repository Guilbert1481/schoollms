<?php

$base = [

    'edit' => [
        'name' => 'edit',
        'label' => 'Edit',
        'class' => 'bg-blue-500 text-white',
        'type' => 'modal',
    ],

    'delete' => [
        'name' => 'delete',
        'label' => 'Delete',
        'class' => 'bg-red-500 text-white',
        'type' => 'delete',
    ],

    'preview' => [
        'name' => 'preview',
        'label' => 'Preview',
        'class' => 'bg-green-500 text-white',
        'type' => 'link',
    ],

];

return [

    'departments' => [
        array_merge($base['edit'], ['modal' => 'departmentEditModal']),
        $base['delete'],
    ],

    'colleges' => [
        [
            'name' => 'assign',
            'label' => 'Assign',
            'class' => 'bg-emerald-600 text-white',
            'type' => 'modal',
            'modal' => 'collegeAssignModal',
            'handler' => "openCollegeModal('collegeAssignModal', {id})",
        ],
        [
            'name' => 'edit',
            'label' => 'Edit',
            'class' => 'bg-blue-500 text-white',
            'type' => 'modal',
            'modal' => 'collegeEditModal',
            'handler' => "openCollegeModal('collegeEditModal', {id})",
        ],
        $base['delete'],
    ],

    'programs' => [
        [
            'name' => 'assign',
            'label' => 'Assign',
            'class' => 'bg-emerald-600 text-white',
            'type' => 'modal',
            'modal' => 'programAssignModal',
            'handler' => "openProgramModal('programAssignModal', {id})",
        ],
        [
            'name' => 'edit',
            'label' => 'Edit',
            'class' => 'bg-blue-500 text-white',
            'type' => 'modal',
            'modal' => 'programEditModal',
            'handler' => "openProgramModal('programEditModal', {id})",
        ],
        $base['delete'],
    ],

    'offices' => [
        [
            'name' => 'assign',
            'label' => 'Assign',
            'class' => 'bg-emerald-600 text-white',
            'type' => 'modal',
            'modal' => 'officeAssignModal',
            'handler' => "openOfficeModal('officeAssignModal', {id})",
        ],
        [
            'name' => 'edit',
            'label' => 'Edit',
            'class' => 'bg-blue-500 text-white',
            'type' => 'modal',
            'modal' => 'officeEditModal',
            'handler' => "openOfficeModal('officeEditModal', {id})",
        ],
        $base['delete'],
    ],

    'certificate_templates' => [
        [
            'name' => 'create',
            'label' => 'Create',
            'class' => 'bg-indigo-600 text-white',
            'type' => 'link',
            'route' => 'school.settings.master-data.certificates.builder.withId',
        ],

        [
            'name' => 'edit',
            'label' => 'Edit',
            'class' => 'bg-blue-500 text-white',
            'type' => 'modal',
            'modal' => 'certificateEditModal',
        ],

        [
            'name' => 'preview',
            'label' => 'Preview',
            'class' => 'bg-green-600 text-white',
            'type' => 'js',
            'handler' => 'openCertificateTemplatePreviewModal',
        ],

        [
            'name' => 'delete',
            'label' => 'Delete',
            'class' => 'bg-red-600 text-white',
            'type' => 'delete',
        ],
    ],

    'certificate_events' => [
        [
            'name' => 'manage',
            'label' => 'Manage',
            'class' => 'bg-emerald-50 text-emerald-700 border border-emerald-300',
            'type' => 'js',
            'handler' => 'manage-recipients',
        ],

        [
            'name' => 'edit',
            'label' => 'Edit',
            'class' => 'bg-blue-50 text-blue-700 border border-blue-300',
            'type' => 'js',
            'handler' => 'edit-event',
        ],

        [
            'name' => 'delete',
            'label' => 'Delete',
            'class' => 'bg-rose-50 text-rose-700 border border-rose-300',
            'type' => 'delete',
            'handler' => 'delete-event',
        ],
    ],

    'event_forms' => [
        [
            'name' => 'edit',
            'label' => 'Edit',
            'class' => 'bg-blue-50 text-blue-700 border border-blue-300',
            'type' => 'js',
            'handler' => 'openEventFormEditModal',
        ],
        [
            'name' => 'send',
            'label' => 'Send',
            'class' => 'bg-indigo-50 text-indigo-700 border border-indigo-300',
            'type' => 'js',
            'handler' => 'sendEventFormLink',
        ],
        [
            'name' => 'delete',
            'label' => 'Delete',
            'class' => 'bg-rose-50 text-rose-700 border border-rose-300',
            'type' => 'delete',
        ],
    ],

    'event_attendance' => [
        [
            'name' => 'manage',
            'label' => 'Manage Attendance',
            'class' => 'bg-emerald-50 text-emerald-700 border border-emerald-300',
            'type' => 'js',
            'handler' => 'openEventAttendanceSheet',
        ],
    ],

    'event_attendance_encoding' => [
        [
            'name' => 'update',
            'label' => 'Update',
            'class' => 'bg-blue-50 text-blue-700 border border-blue-300',
            'type' => 'js',
            'handler' => 'openAttendanceEntryModal',
        ],
    ],

    'training_trainee_certificates' => [
        [
            'name' => 'preview',
            'label' => 'Preview',
            'class' => 'bg-indigo-50 text-indigo-700 border border-indigo-300',
            'type' => 'js',
            'handler' => 'openTraineeCertificatePreview',
        ],
    ],

    'drive_files' => [
        [
            'name' => 'edit',
            'label' => 'Edit',
            'class' => 'bg-emerald-600 text-white hover:bg-emerald-700',
            'type' => 'js',
            'handler' => 'driveEdit',
        ],
        [
            'name' => 'share',
            'label' => 'Share',
            'class' => 'bg-slate-200 text-slate-700 hover:bg-slate-300',
            'type' => 'js',
            'handler' => 'driveShare',
        ],
        [
            'name' => 'delete',
            'label' => 'Delete',
            'class' => 'bg-rose-600 text-white hover:bg-rose-700',
            'type' => 'js',
            'handler' => 'driveDelete',
        ],
    ],

    'superadmin_schools' => [
        [
            'name' => 'view',
            'label' => 'View',
            'class' => 'bg-indigo-500 text-white hover:bg-indigo-600',
            'type' => 'link',
            'route' => 'superadmin.schools.show',
        ],
        [
            'name' => 'edit',
            'label' => 'Edit',
            'class' => 'bg-blue-500 text-white hover:bg-blue-600',
            'type' => 'js',
            'handler' => 'openSchoolEditModal',
        ],
        [
            'name' => 'modules',
            'label' => 'Add-ons',
            'class' => 'bg-emerald-500 text-white hover:bg-emerald-600',
            'type' => 'js',
            'handler' => 'openSchoolModulesModal',
        ],
        [
            'name' => 'delete',
            'label' => 'Delete',
            'class' => 'bg-red-500 text-white hover:bg-red-600',
            'type' => 'delete',
        ],
    ],

    'curriculums' => [
        array_merge($base['edit'], ['modal' => 'curriculumEditModal']),
        $base['delete'],
    ],

    'subjects' => [
        [
            'name' => 'edit',
            'label' => 'Edit',
            'class' => 'bg-blue-500 text-white hover:bg-blue-600',
            'type' => 'modal',
            'modal' => 'subjectEditModal',
            'handler' => 'openSubjectEditModal({id})',
        ],
        [
            'name' => 'delete',
            'label' => 'Delete',
            'class' => 'bg-red-500 text-white hover:bg-red-600',
            'type' => 'js',
            'handler' => 'confirmDeleteSubject',
        ],
    ],

    'subject_offerings' => [
        [
            'name' => 'delete',
            'label' => 'Remove',
            'class' => 'bg-red-50 text-red-700 border border-red-200 hover:bg-red-100',
            'type' => 'delete',
        ],
    ],

    // Generic delete-only action used by Lesson Studio folder rows.
    // Handler is a JS bridge (see lesson-studio.blade.php) that POSTs
    // a hidden DELETE form to the correct route per folder type.
    'lesson_studio_folder' => [
        [
            'name' => 'edit',
            'label' => 'Edit',
            'class' => 'bg-blue-500 text-white hover:bg-blue-600',
            'type' => 'js',
            'handler' => 'phLessonStudioEdit',
        ],
        [
            'name' => 'delete',
            'label' => 'Delete',
            'class' => 'bg-red-500 text-white hover:bg-red-600',
            'type' => 'js',
            'handler' => 'phLessonStudioDelete',
        ],
    ],

    // Course Architect / Subject Coordinator Lesson Studio — Topic (L1) and
    // Lesson (L2) rows. Subjects (L0) get no actions: the Principal owns them.
    // All three are 'js' because the destroy/update routes take {type} AND {id},
    // which the generic delete handler (route($deleteRoute, $id)) cannot build.
    'lesson_studio_ca_folder' => [
        [
            'name' => 'view',
            'label' => 'View',
            'class' => 'bg-indigo-50 text-indigo-600 hover:bg-indigo-100',
            'type' => 'js',
            'handler' => 'lsFolderOpen',
        ],
        [
            'name' => 'edit',
            'label' => 'Rename',
            'class' => 'bg-amber-500 text-white hover:bg-amber-600',
            'type' => 'js',
            'handler' => 'lsFolderEdit',
        ],
        [
            'name' => 'delete',
            'label' => 'Delete',
            'class' => 'bg-red-500 text-white hover:bg-red-600',
            'type' => 'js',
            'handler' => 'lsFolderDelete',
        ],
    ],

    // Upload a resource straight from the lesson list (Level 2) without drilling
    // into the lesson. Shown only to content-adders (course_architect /
    // subject_coordinator); the handler opens the quick-upload modal.
    'lesson_studio_ca_upload' => [
        [
            'name' => 'upload',
            'label' => 'Upload',
            'class' => 'bg-emerald-600 text-white hover:bg-emerald-700',
            'type' => 'js',
            'handler' => 'lsUploadResource',
        ],
    ],

    'transcript_master' => [
        [
            'name' => 'view',
            'label' => 'View Transcript',
            'class' => 'bg-indigo-600 text-white hover:bg-indigo-700',
            'type' => 'link',
            'route' => 'registrar.transcripts.show',
        ],
    ],

];
