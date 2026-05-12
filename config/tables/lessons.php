<?php

return [

    'labels' => [
        'name'                => 'Lesson Name',
        'competencies_count'  => 'Competencies',
        'resources_count'     => 'Resources',
    ],

    'columns' => [
        ['key' => 'name',              'label' => 'Lesson Name'],
        ['key' => 'resources_count',   'label' => 'Resources'],
    ],

    'hidden' => [
        'id',
        'topic_id',
        'school_id',
        'sort_order',
        'created_at',
        'updated_at',
    ],

];
