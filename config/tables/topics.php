<?php

return [

    'labels' => [
        'name'           => 'Topic Name',
        'lessons_count'  => 'Lessons',
    ],

    'columns' => [
        ['key' => 'name',          'label' => 'Topic Name'],
        ['key' => 'lessons_count', 'label' => 'Lessons'],
    ],

    'hidden' => [
        'id',
        'subject_id',
        'school_id',
        'sort_order',
        'created_at',
        'updated_at',
    ],

];
