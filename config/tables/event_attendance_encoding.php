<?php

return [
    'labels' => [
        'recipient_name' => 'Recipient',
        'roles_label' => 'Roles',
        'status_label' => 'Status',
        'time_in_label' => 'Time In',
        'time_out_label' => 'Time Out',
        'capture_source_label' => 'Source',
    ],

    'columns' => [
        ['key' => 'recipient_name', 'label' => 'Recipient'],
        ['key' => 'roles_label', 'label' => 'Roles'],
        ['key' => 'status_label', 'label' => 'Status'],
        ['key' => 'time_in_label', 'label' => 'Time In'],
        ['key' => 'time_out_label', 'label' => 'Time Out'],
        ['key' => 'capture_source_label', 'label' => 'Source'],
    ],

    'form' => [],

    'hidden' => [
        'event_id',
        'custom_fields',
        'created_at',
        'updated_at',
    ],

    'auto' => [],

    'relations' => [],
];
