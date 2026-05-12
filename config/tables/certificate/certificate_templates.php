<?php

return [

    'labels' => [
        'name' => 'Template Name',
        'certificate_type' => 'Type',
        'category' => 'Category',
    ],

    'columns' => [
        ['key' => 'id', 'label' => 'ID'],
        ['key' => 'name', 'label' => 'Template Name'],
        ['key' => 'certificate_type', 'label' => 'Type'],
        ['key' => 'category', 'label' => 'Category'],
    ],

    'form' => [
        'name',
        'certificate_type',
        'category',
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