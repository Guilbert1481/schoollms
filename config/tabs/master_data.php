<?php

return [

    [
        'label' => 'AY & Terms',
        'route' => 'school.settings.master-data.academic_year.index',
        'active' => 'school.settings.master-data.academic_year.index*',
        'roles' => ['admin'],
    ],

    [
        'label' => 'Calendar',
        'route' => 'school.settings.master-data.calendar.index',
        'active' => 'school.settings.master-data.calendar.*',
        'roles' => ['admin'],
    ],

    [
        'label' => 'Position',
        'route' => 'school.settings.master-data.organization.indexPositions',
        'active' => 'school.settings.master-data.organization.indexPositions*',
        'roles' => ['admin'],
    ],

    [
        'label' => 'Facilities',
        'route' => 'school.settings.master.facilities.index',
        'active' => 'school.settings.master.facilities.*',
        'roles' => ['admin'],
    ],

    [
        'label' => 'Training',
        'route' => 'school.settings.master.training.index',
        'active' => 'school.settings.master.training.*',
        'roles' => ['admin'],
        'children' => require __DIR__.'/dropdown/training.php',
    ],

    [
        'label' => 'Certificates',
        'route' => 'school.settings.master-data.certificates.index',
        'active' => 'school.settings.master-data.certificates.*',
        'roles' => ['admin'],
        'children' => require __DIR__.'/dropdown/certificates.php',
    ],

    [
        'label' => 'Event',
        'route' => 'school.settings.master-data.events.index',
        'active' => 'school.settings.master-data.events.*',
        'roles' => ['admin'],
        'children' => require __DIR__.'/dropdown/event.php',
    ],

    [
        'label' => 'Education Levels',
        'route' => 'admin.education-nodes.index',
        'active' => 'admin.education-nodes.*',
        'roles' => ['admin'],
    ],

];