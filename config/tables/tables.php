<?php

/*
 * Registry for the generic CRUD endpoints (school/system/dynamic/*).
 *
 * BEING LISTED HERE IS A GRANT OF WRITE ACCESS. Anything registered can be
 * read/updated through BaseCrudController by any role on that table's `roles`
 * allowlist — bypassing whatever rules the owning module enforces on its own
 * screens. So register a table only when a page actually drives it through the
 * generic endpoints, and give every entry a `roles` key (see ACCESS_CONTROL.md
 * Recipe B). A table with no `roles` key is refused for everyone.
 *
 * NOT registered on purpose:
 *   topics, lessons — config/tables/topics.php and lessons.php exist but nothing
 *   reads them, and no page drives those tables through the generic endpoints.
 *   Registering them would let anyone on the route's role list restructure a
 *   curriculum outline directly, sidestepping the Lesson Studio's ownership
 *   rules (Program Head owns the higher-ed outline; only a subject_coordinator
 *   may edit a basic-ed one). Keep them out.
 */

return [
    'departments' => require __DIR__.'/departments.php',
    'colleges' => require __DIR__.'/colleges.php',
    'programs' => require __DIR__.'/programs.php',
    'offices' => require __DIR__.'/offices.php',
    'table_actions' => require __DIR__.'/table-actions.php',
    'certificate_templates' => require __DIR__.'/certificate/certificate_templates.php',
    'drive_files' => require __DIR__.'/drive_files.php',
    'curriculums' => require __DIR__.'/curriculums.php',
    'subjects' => require __DIR__.'/subjects.php',
    'subject_offerings' => require __DIR__.'/subject_offerings.php',
];
