<?php

return [

    'labels' => [
        'name'                => 'Subject Name',
        'code'                => 'Code',
        'category'            => 'Category',
        'topics_count'        => 'Topics',
        'lessons_count'       => 'Lessons',
        'competencies_count'  => 'Competencies',
        'is_active'           => 'Status',
        'description'         => 'Description',
    ],

    'columns' => [
        ['key' => 'name',               'label' => 'Subject Name'],
        ['key' => 'code',               'label' => 'Code'],
        ['key' => 'category',           'label' => 'Category'],
        ['key' => 'topics_count',       'label' => 'Topics'],
        ['key' => 'lessons_count',      'label' => 'Lessons'],
        ['key' => 'competencies_count', 'label' => 'Competencies'],
        ['key' => 'is_active',          'label' => 'Status'],
    ],

    'form' => [
        'name',
        'code',
        'category',
        'description',
        'active',
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

    'relations' => [],

];
