<?php

return [
    [
        'label' => 'Event Setup',
        'route' => 'school.settings.master-data.events.index',
        'active' => 'school.settings.master-data.events.*',
        'params' => ['mode' => 'event'],
        'active_query' => [
            'key' => 'mode',
            'value' => 'event',
        ],
    ],
    [
        'label' => 'Attendance',
        'route' => 'school.settings.master-data.events.index',
        'active' => 'school.settings.master-data.events.*',
        'params' => ['mode' => 'attendance'],
        'active_query' => [
            'key' => 'mode',
            'value' => 'attendance',
        ],
    ],
];
