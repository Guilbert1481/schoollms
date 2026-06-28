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
        'full_name'  => 'Full Name',
        'year_level' => 'Year Level',
        'student_id' => 'Student ID',
        'program'    => 'Program',
        'status'     => 'Status',
    ],

    'columns' => [
        ['key' => 'full_name',  'label' => 'Full Name',  'width' => 'auto'],
        ['key' => 'year_level', 'label' => 'Year Level', 'width' => '130px'],
        ['key' => 'student_id', 'label' => 'Student ID', 'width' => '160px'],
        ['key' => 'program',    'label' => 'Program',    'width' => '260px'],
        ['key' => 'status',     'label' => 'Status', 'raw' => true, 'width' => '140px'],
    ],

    'hidden' => [
        'id',
    ],
];
