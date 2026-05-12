<?php

return [

    

    [
        'label' => 'System Settings',
        'route' => 'school.settings.system.index',
        'active' => 'school.settings.system.index*',
        'roles' => ['admin'],
    ],

    [
        'label' => 'School Settings',
        'route' => 'school.settings.school.index',
        'active' => 'school.settings.school.index*',
        'roles' => ['admin'],
    ],

];