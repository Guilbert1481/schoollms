<?php

return [

    // Roles allowed to reach this table through school/system/dynamic/*.
    // Admin > Assignments (admin) and the Dean curricula tabs (role:dean,admin,superadmin).
    'roles' => ['admin', 'superadmin', 'dean'],

    'labels' => [
        'code' => 'Code',
        'name' => 'Program',
        'college_id' => 'College',
        'program_head_id' => 'Program Head',
    ],

    'columns' => [
        ['key' => 'code',             'label' => 'Code'],
        ['key' => 'name',             'label' => 'Program'],
        ['key' => 'college_name',     'label' => 'College'],
        ['key' => 'program_head_name', 'label' => 'Program Head'],
    ],

    'form' => [
        'code',
        'name',
        'college_id',
    ],

];
