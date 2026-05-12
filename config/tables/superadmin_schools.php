<?php

return [

    'labels' => [
        'school_name'   => 'Institution',
        'code'          => 'Code',
        'domain'        => 'Domain',
        'slug'          => 'Slug',
        'country'       => 'Country',
        'type'          => 'Type',
        'type_label'    => 'Type',
        'plan_name'     => 'Plan',
        'modules_count' => 'Add-ons',
        'status_label'  => 'Status',
        'first_name'    => 'Admin First Name',
        'last_name'     => 'Admin Last Name',
        'email'         => 'Admin Email',
        'password'      => 'Password',
        'password_confirmation' => 'Confirm Password',
    ],

    'columns' => [
        ['key' => 'school_name',   'label' => 'Institution'],
        ['key' => 'slug',          'label' => 'Slug'],
        ['key' => 'country',       'label' => 'Country'],
        ['key' => 'type_label',    'label' => 'Type'],
        ['key' => 'plan_name',     'label' => 'Plan'],
        ['key' => 'modules_count', 'label' => 'Add-ons'],
        ['key' => 'status_label',  'label' => 'Status'],
    ],

    'form' => [
        'school_name',
        'code',
        'domain',
        'slug',
        'country',
        'type',
        'first_name',
        'last_name',
        'email',
        'password',
        'password_confirmation',
    ],

    'types' => [
        'type'     => ['select' => ['school' => 'School', 'freelance' => 'Freelance']],
        'password' => ['input' => 'password'],
        'password_confirmation' => ['input' => 'password'],
        'email'    => ['input' => 'email'],
    ],

];
