<?php

use App\Models\SchoolProfile;
use App\Models\Modality;

return [

    'modalities' => [
        'label'         => '',
        'type'          => 'checkbox_dropdown_db',
        'source_model'  => Modality::class,
        'display'       => 'name',
        'value'         => 'id',
        'model'         => 'pivot:school_modalities',
        'roles'         => ['admin'],
    ],

    'school_days' => [
        'label'   => 'School Days',
        'type'    => 'checkbox_dropdown_static',
        'options' => ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'],
        'roles'   => ['admin'],
        'save_to' => [[
            'model'  => SchoolProfile::class,
            'column' => 'school_days',
            'where'  => ['school_id' => 'auth_school_id'],
        ]],
    ],

];