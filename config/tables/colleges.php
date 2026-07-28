<?php

return [

    // Roles allowed to reach this table through school/system/dynamic/*.
    // Admin > Assignments (role:admin,superadmin).
    'roles' => ['admin', 'superadmin'],

    'labels' => [
        'code' => 'Code',
        'name' => 'College Name',
        'description' => 'Description',
        'dean_id' => 'Assigned Dean',
    ],

    'columns' => [
        ['key' => 'code',        'label' => 'Code'],
        ['key' => 'name',        'label' => 'College'],
        ['key' => 'description', 'label' => 'Description'],
        ['key' => 'dean_name',   'label' => 'Assigned Dean'],
    ],

    'form' => [
        'code',
        'name',
        'description',
    ],

];
