<?php

return [

    // Roles allowed to reach this table through school/system/dynamic/*.
    // Dean > Curricula Panel (dean) and Principal > Curricula Panel (role:principal). Deliberately EXCLUDES course_architect / subject_coordinator / teacher - subjects are Principal- or Program-Head-owned and the Lesson Studio refuses to edit them.
    'roles' => ['admin', 'superadmin', 'dean', 'principal'],

    'labels' => [
        'name' => 'Subject Name',
        'code' => 'Code',
        'category' => 'Category',
        'topics_count' => 'Topics',
        'lessons_count' => 'Lessons',
        'competencies_count' => 'Competencies',
        'is_active' => 'Status',
        'description' => 'Description',
    ],

    'columns' => [
        ['key' => 'name',               'label' => 'Subject Name'],
        ['key' => 'code',               'label' => 'Code'],
        ['key' => 'category',           'label' => 'Category'],
        ['key' => 'topics_count',       'label' => 'Topics'],
        ['key' => 'lessons_count',      'label' => 'Lessons'],
        ['key' => 'competencies_count', 'label' => 'Competencies'],
        ['key' => 'is_active',          'label' => 'Status'],
    ],

    'form' => [
        'name',
        'code',
        'category',
        'description',
        'active',
    ],

    'hidden' => [
        'id',
        'school_id',
        'created_at',
        'updated_at',
    ],

    'auto' => [
        'school_id',
    ],

    'relations' => [],

];
