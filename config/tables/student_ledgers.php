<?php

return [
    'labels' => [
        'full_name'   => 'Full Name',
        'student_id'  => 'Student ID',
        'email'       => 'Email',
        'year_term'   => 'Year/Term',
        'program'     => 'Program',
        'enrolled_at' => 'Enrolled On',
    ],

    'columns' => [
        ['key' => 'full_name',   'label' => 'Full Name',   'width' => 'auto'],
        ['key' => 'student_id',  'label' => 'Student ID',  'width' => '140px'],
        ['key' => 'email',       'label' => 'Email',       'width' => '220px'],
        ['key' => 'year_term',   'label' => 'Year/Term',   'width' => '240px'],
        ['key' => 'program',     'label' => 'Program',     'width' => '260px'],
        ['key' => 'enrolled_at', 'label' => 'Enrolled On', 'width' => '130px'],
    ],

    'hidden' => [
        'id',
    ],
];
