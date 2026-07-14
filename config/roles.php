<?php

/*
|--------------------------------------------------------------------------
| Subscribable Role Catalog
|--------------------------------------------------------------------------
|
| The fixed set of staff/academic roles a school may be entitled to. The
| superadmin picks from this list when registering (or editing) a partner
| school; the chosen keys are stored per-school in the `school_roles` table
| and gate which roles a school admin can assign users to (see
| App\Models\SchoolRole and UserManagementController).
|
| This is an ENTITLEMENT/UX catalog — it governs which roles a school can
| provision users into. What a role is *allowed to do* is still enforced by
| the `role:` route middleware (ADR-0002); the two layers complement.
|
| The keys below are the canonical `users.role` strings (snake_case) and were
| reconciled against every source of truth in the app: the `role:` route
| middleware, code that queries by role (approval routing, office-head /
| assignment lookups), the User model helpers, and config/sidebar.php.
| `parent` (auto-provisioned, cross-school) and `superadmin` (platform) are
| deliberately excluded — they are never school-subscribed.
|
*/

return [

    // key => [label, segment]. Segment groups the checkboxes on the
    // Add/Edit Partner form so basic-ed vs higher-ed roles are easy to pick.
    'catalog' => [
        // Core — nearly every institution needs these.
        'admin' => ['label' => 'Administrator', 'segment' => 'Core'],
        'registrar' => ['label' => 'Registrar', 'segment' => 'Core'],
        'teacher' => ['label' => 'Teacher', 'segment' => 'Core'],
        'student' => ['label' => 'Student', 'segment' => 'Core'],
        'finance_manager' => ['label' => 'Finance Manager', 'segment' => 'Core'],
        'admission_manager' => ['label' => 'Admission Manager', 'segment' => 'Core'],
        'guidance_counselor' => ['label' => 'Guidance Counselor', 'segment' => 'Core'],

        // Administration & Operations.
        'office_head' => ['label' => 'Office Head', 'segment' => 'Administration & Operations'],
        'hr_manager' => ['label' => 'HR Manager', 'segment' => 'Administration & Operations'],

        // Basic Education (K-12, incl. Senior High).
        'principal' => ['label' => 'Principal', 'segment' => 'Basic Education'],
        'subject_coordinator' => ['label' => 'Subject Coordinator (SHS)', 'segment' => 'Basic Education'],

        // Higher Education (college / university).
        'academics' => ['label' => 'VP Academics', 'segment' => 'Higher Education'],
        'dean' => ['label' => 'Dean', 'segment' => 'Higher Education'],
        'program_head' => ['label' => 'Program Head', 'segment' => 'Higher Education'],

        // Training & Freelance.
        'course_architect' => ['label' => 'Course Architect', 'segment' => 'Training & Freelance'],
        'trainor' => ['label' => 'Trainer', 'segment' => 'Training & Freelance'],
        'trainee' => ['label' => 'Trainee', 'segment' => 'Training & Freelance'],
        'training_program_head' => ['label' => 'Training Program Head', 'segment' => 'Training & Freelance'],
    ],

    // Pre-checked when the Add New Partner form first opens. The superadmin
    // can change these before saving.
    'defaults' => ['admin', 'teacher', 'student'],

    // Display order for the segment groups on the form.
    'segment_order' => [
        'Core',
        'Administration & Operations',
        'Basic Education',
        'Higher Education',
        'Training & Freelance',
    ],
];
