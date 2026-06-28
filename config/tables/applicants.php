<?php

/*
 * Column config for the Admissions "Applicant Directory" master list.
 *
 * Used by resources/views/admission/applicants.blade.php via <x-table.table>.
 * `raw` flagged columns receive pre-rendered HTML from the controller.
 */
return [

    'columns' => [
        ['key' => 'applicant',      'label' => 'Applicant',   'raw' => true, 'width' => 'auto'],
        ['key' => 'student_number', 'label' => 'Student No.', 'width' => '140px'],
        ['key' => 'program',        'label' => 'Programme',   'width' => '260px'],
        ['key' => 'enrollee_type',  'label' => 'Type',        'width' => '110px'],
        ['key' => 'year_level',     'label' => 'Year/Term',   'width' => '240px'],
        ['key' => 'status',         'label' => 'Status', 'raw' => true, 'width' => '130px'],
        ['key' => 'submitted_at',   'label' => 'Submitted',   'width' => '140px'],
    ],
];
