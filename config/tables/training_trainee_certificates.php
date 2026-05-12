<?php

return [
    'labels' => [
        'training_type_name_label' => 'Training Type',
        'course_name_label' => 'Course Name',
        'certificate_number_label' => 'Certificate #',
        'date_issued_label' => 'Date Issued',
    ],

    'columns' => [
        ['key' => 'training_type_name_label', 'label' => 'Training Type'],
        ['key' => 'course_name_label', 'label' => 'Course Name'],
        ['key' => 'certificate_number_label', 'label' => 'Certificate #'],
        ['key' => 'date_issued_label', 'label' => 'Date Issued'],
    ],

    'form' => [],

    'hidden' => [
        'training_enrollment_id',
        'file_path',
        'created_at',
        'updated_at',
    ],

    'auto' => [],

    'relations' => [],
];
