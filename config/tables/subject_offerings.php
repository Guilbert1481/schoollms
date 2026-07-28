<?php

return [

    // Roles allowed to reach this table through school/system/dynamic/*.
    // School Settings only; its tables use per-term keys, so nothing reaches the generic endpoints today.
    'roles' => ['admin', 'superadmin'],

    'labels' => [
        'subject_label' => 'Subject',
        'program_label' => 'Program',
        'year_level' => 'Year Level',
        'status_label' => 'Status',
        'irregular_label' => 'Irregular?',
        'offering_code' => 'Code',
    ],

    'columns' => [
        ['key' => 'subject_label',   'label' => 'Subject'],
        ['key' => 'program_label',   'label' => 'Program'],
        ['key' => 'year_level',      'label' => 'Year'],
        ['key' => 'status_label',    'label' => 'Status', 'raw' => true],
        ['key' => 'irregular_label', 'label' => 'Irregular?'],
        ['key' => 'offering_code',   'label' => 'Code'],
    ],

    'hidden' => [
        'id',
        'school_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ],

    'auto' => [],

    'relations' => ['subject', 'program', 'term'],

];
