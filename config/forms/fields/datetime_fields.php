<?php

use App\Models\SchoolProfile;

return [

    'opening_date' => [
        'label' => 'Opening Date',
        'type'  => 'date',
        'roles' => ['admin'],
        'save_to' => [[
            'model'  => SchoolProfile::class,
            'column' => 'opening_date',
            'where'  => ['school_id' => 'auth_school_id'],
        ]],
    ],

    'opening_time' => [
        'label' => 'Opening Time',
        'type'  => 'time',
        'roles' => ['admin'],
        'save_to' => [[
            'model'  => SchoolProfile::class,
            'column' => 'opening_time',
            'where'  => ['school_id' => 'auth_school_id'],
        ]],
    ],

    'orientation_datetime' => [
        'label' => 'Orientation Schedule',
        'type'  => 'datetime',
        'roles' => ['admin'],
        'save_to' => [[
            'model'  => SchoolProfile::class,
            'column' => 'orientation_datetime',
            'where'  => ['school_id' => 'auth_school_id'],
        ]],
    ],

];