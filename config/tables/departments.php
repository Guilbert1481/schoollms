<?php

return [

    'labels' => [
        'name' => 'Department Name',
        'head_user_id' => 'Department Head',
    ],

    'columns' => [
        ['key' => 'name', 'label' => 'Department Name'],
        ['key' => 'head_user_id', 'label' => 'Department Head'],
        ['key' => 'created_at', 'label' => 'Created'],
    ],

    'form' => [
        'name',
        'head_user_id',
    ],

    'hidden' => [
        'id',
        'school_id',
        'user_id',
        'updated_at',
    ],

    'auto' => [
        'school_id',
        'user_id',
    ],

    'relations' => [
    'head_user_id' => [
        'table' => 'users',
        'value' => 'id',
        'join_profiles' => true,
        'whereSchool' => true,
    ],
],

];