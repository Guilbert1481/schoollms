<?php

return [
    'labels' => [
        'event_name' => 'Event Name',
        'event_type' => 'Type',
        'categories' => 'Categories',
        'roles' => 'Roles',
        'start_date_label' => 'Start Date',
        'end_date_label' => 'End Date',
        'start_time_label' => 'Start Time',
        'end_time_label' => 'End Time',
    ],

    'columns' => [
        ['key' => 'event_name', 'label' => 'Event Name'],
        ['key' => 'event_type_label', 'label' => 'Type'],
        ['key' => 'categories', 'label' => 'Categories'],
        ['key' => 'roles', 'label' => 'Roles'],
        ['key' => 'start_date_label', 'label' => 'Start Date'],
        ['key' => 'end_date_label', 'label' => 'End Date'],
        ['key' => 'start_time_label', 'label' => 'Start Time'],
        ['key' => 'end_time_label', 'label' => 'End Time'],
    ],

    'form' => [
        'event_name',
        'event_type',
        'event_types',
        'role_types',
        'certificate_title_default',
        'date_issued_default',
        'description',
    ],

    'hidden' => [
        'school_id',
        'template_id',
        'metadata',
        'created_at',
        'updated_at',
    ],

    'auto' => [
        'school_id',
    ],

    'relations' => [],
];
