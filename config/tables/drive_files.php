<?php

return [

    // Roles allowed to reach this table through school/system/dynamic/*.
    // Drive uses its own routes (driveEdit/driveDelete); nothing reaches the generic endpoints, so keep this minimal.
    'roles' => ['admin', 'superadmin'],

    'labels' => [
        'name' => 'Name',
        'type_label' => 'Type',
        'owner_name' => 'Owner',
        'last_editor_name' => 'Updated by',
        'updated_at_label' => 'Updated',
    ],

    'columns' => [
        ['key' => 'name',              'label' => 'Name'],
        ['key' => 'type_label',        'label' => 'Type'],
        ['key' => 'owner_name',        'label' => 'Owner'],
        ['key' => 'last_editor_name',  'label' => 'Updated by'],
        ['key' => 'updated_at_label',  'label' => 'Updated'],
    ],

];
