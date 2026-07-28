<?php

/*
 * Column config for the Registrar's "Transcript of Records" master list.
 *
 * Used by resources/views/registrar/transcripts/index.blade.php via the
 * shareable <x-table.table> component. The `raw` flag on `status` lets the
 * controller emit a colour-coded pill.
 */
return [

    'labels' => [
        'full_name' => 'Full Name',
        'year_level' => 'Year Level',
        'student_id' => 'Student ID',
        'program' => 'Program',
        'status' => 'Status',
        'gv_grades' => 'Grades',
        'gv_form137' => 'Form 137',
    ],

    'columns' => [
        ['key' => 'full_name',  'label' => 'Full Name',  'width' => 'auto'],
        ['key' => 'year_level', 'label' => 'Year Level', 'width' => '130px'],
        ['key' => 'student_id', 'label' => 'Student ID', 'width' => '160px'],
        ['key' => 'program',    'label' => 'Program',    'width' => '260px'],
        ['key' => 'status',     'label' => 'Status', 'raw' => true, 'width' => '140px'],
        // Registrar-owned grade-view visibility toggles (raw switch cells).
        ['key' => 'gv_grades',  'label' => 'Grades',   'raw' => true, 'width' => '90px'],
        ['key' => 'gv_form137', 'label' => 'Form 137', 'raw' => true, 'width' => '90px'],
    ],

    'hidden' => [
        'id',
    ],
];
