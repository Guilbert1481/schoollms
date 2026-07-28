<?php

return [

    // Roles allowed to reach this table through school/system/dynamic/*.
    // School Settings > Master Data > Organization, and Admin > Assignments.
    'roles' => ['admin', 'superadmin'],

    'labels' => [
        'code' => 'Code',
        'name' => 'Office Name',
        'office_type_id' => 'Type',
        'office_head_id' => 'Assigned Head',
    ],

    'columns' => [
        ['key' => 'code',             'label' => 'Code'],
        ['key' => 'name',             'label' => 'Office'],
        ['key' => 'office_type_name', 'label' => 'Type'],
        ['key' => 'office_head_name', 'label' => 'Assigned Head'],
    ],

    'form' => [
        'code',
        'name',
        'office_type_id',
    ],

];
