<?php

return [

    [
        'label'  => 'My Drive',
        'route'  => 'tools.drive.index',
        'active' => 'tools.drive.index',
        'active_query' => [
            'key'     => 'scope',
            'value'   => 'my',
            'default' => true, // treat missing ?scope as "my"
        ],
    ],

    [
        'label'  => 'Shared with me',
        'route'  => 'tools.drive.index',
        'params' => ['scope' => 'shared'],
        'active' => 'tools.drive.index',
        'active_query' => [
            'key'   => 'scope',
            'value' => 'shared',
        ],
    ],

];
