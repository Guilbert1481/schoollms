<?php

return [
    // Changing "Deadlines" to "Tasks"
    'deadline' => 'Task',
    'deadlines' => 'Tasks',
    'create_deadline' => 'Create New Task',
    'no_deadlines' => 'No tasks found.',


    /*
    |--------------------------------------------------------------------------
    | Sidebar & Navigation
    |--------------------------------------------------------------------------
    */
    'nav' => [
        'dashboard'   => 'Dashboard',
        'assignments' => 'Assignments', // Your "Deadlines" replacement
        'students'    => 'Students',
        'teachers'    => 'Teachers',
        'chat'        => 'Messages',
        'settings'    => 'Settings',
    ],

    /*
    |--------------------------------------------------------------------------
    | Shared Terminology (Core Objects)
    |--------------------------------------------------------------------------
    */
    'core' => [
        'assignment' => 'Assignment',
        'student'    => 'Student',
        'teacher'    => 'Teacher',
        'school'     => 'School',
        'department' => 'Department',
    ],

    /*
    |--------------------------------------------------------------------------
    | Actions & Buttons
    |--------------------------------------------------------------------------
    */
    'actions' => [
        'create' => 'Create New :item',
        'edit'   => 'Edit',
        'delete' => 'Delete',
        'save'   => 'Save Changes',
        'cancel' => 'Cancel',
        'search' => 'Search...',
    ],

    /*
    |--------------------------------------------------------------------------
    | Table & Form Fields
    |--------------------------------------------------------------------------
    */
    'fields' => [
        'title'      => 'Title',
        'due_date'   => 'Due Date',
        'status'     => 'Status',
        'created_at' => 'Date Created',
    ],

];