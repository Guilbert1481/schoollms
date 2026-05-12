<?php

return [
    'labels' => [
        'event_name' => 'Event Name',
        'start_date_label' => 'Start Date',
        'end_date_label' => 'End Date',
        'attendance_total_days_label' => 'Attendance Days',
    ],

    'columns' => [
        ['key' => 'event_name', 'label' => 'Event Name'],
        ['key' => 'start_date_label', 'label' => 'Start Date'],
        ['key' => 'end_date_label', 'label' => 'End Date'],
        ['key' => 'attendance_total_days_label', 'label' => 'Attendance Days'],
    ],

    'form' => [],

    'hidden' => [
        'school_id',
        'metadata',
        'created_at',
        'updated_at',
    ],

    'auto' => [],

    'relations' => [],
];
