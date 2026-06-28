<?php

/*
 * Column config for the student-portal Transcript table.
 *
 * Used by resources/views/student/transcript/index.blade.php via the
 * shareable <x-table.table> component. The `raw` flag on `final_grade`
 * and `status` lets the controller emit colour-coded HTML pills:
 *   - green  → Passed / numeric passing grade
 *   - red    → Failed
 *   - blue   → Credited (transferee)
 */
return [

    'labels' => [
        'term'              => 'Term',
        'subject_code'      => 'Subject Code',
        'descriptive_title' => 'Descriptive Title',
        'final_grade'       => 'Final Grade',
        'units'             => 'Units',
        'status'            => 'Status',
    ],

    'columns' => [
        ['key' => 'term',              'label' => 'Term',              'raw' => true, 'width' => '110px'],
        ['key' => 'subject_code',      'label' => 'Subject Code',                    'width' => '245px'],
        ['key' => 'descriptive_title', 'label' => 'Descriptive Title',               'width' => 'auto'],
        ['key' => 'final_grade',       'label' => 'Final Grade',       'raw' => true, 'width' => '110px'],
        ['key' => 'units',             'label' => 'Units',                            'width' => '110px'],
        ['key' => 'status',            'label' => 'Status',            'raw' => true, 'width' => '110px'],
    ],

    'hidden' => [
        'id',
    ],
];
