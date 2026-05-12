<?php

return [

    [
        'label' => 'Certificate Templates',
        'route' => 'school.settings.master-data.certificates.index',
        'params' => ['section' => 'templates'],
        'active' => 'school.settings.master-data.certificates.index',
        'active_query' => [
            'key' => 'section',
            'value' => 'templates',
        ],
    ],

    [
        'label' => 'Certificate Events',
        'route' => 'school.settings.master-data.certificates.index',
        'params' => ['section' => 'events'],
        'active' => 'school.settings.master-data.certificates.index',
        'active_query' => [
            'key' => 'section',
            'value' => 'events',
        ],
    ],

];
