<?php

return [

    'labels' => [
        'name'           => 'Curriculum',
        'version'        => 'Version',
        'program_id'     => 'Program',
        'terms_per_year' => 'Terms / Year',
        'is_active'      => 'Active',
    ],

    'columns' => [
        ['key' => 'name',           'label' => 'Curriculum'],
        ['key' => 'version',        'label' => 'Version'],
        ['key' => 'program_id',     'label' => 'Program'],
        ['key' => 'terms_per_year', 'label' => 'Terms / Year'],
        ['key' => 'is_active',      'label' => 'Active'],
    ],

    'form' => [
        'name',
        'version',
        'program_id',
        'terms_per_year',
        'is_active',
    ],

    'hidden' => [
        'id',
        'school_id',
        'created_at',
        'updated_at',
    ],

    'auto' => [
        'school_id',
    ],

    'relations' => [
        'program_id' => [
            'table'       => 'programs',
            'value'       => 'id',
            'whereSchool' => true,
        ],
    ],

];
