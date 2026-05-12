<?php

return [

    'event_name' => [
        'label' => 'Event Name',
        'type' => 'text',
        'required' => true,
        'roles' => ['admin'],
    ],

    'event_type' => [
        'label' => 'Main Event Type',
        'type' => 'text',
        'placeholder' => 'e.g. Intramurals, Academic Festival',
        'roles' => ['admin'],
    ],

    'event_types' => [
        'label' => 'Event Categories / Activities',
        'type' => 'tag_input',
        'required' => true,
        'placeholder' => 'e.g. Basketball, Workshop A, Quiz Bee, Talent Showcase',
        'help' => 'Add one per item relevant to this event (sports, sessions, competitions, activities, etc.).',
        'roles' => ['admin'],
    ],

    'role_types' => [
        'label' => 'Role Types for This Event',
        'type' => 'tag_input',
        'required' => true,
        'placeholder' => 'e.g. Participant, Attendee, Speaker, Coach, Referee, Judge',
        'help' => 'Add one per role that applies to this event.',
        'roles' => ['admin'],
    ],

    'certificate_title_default' => [
        'label' => 'Default Certificate Title',
        'type' => 'text',
        'roles' => ['admin'],
    ],

    'date_issued_default' => [
        'label' => 'Default Issued Date',
        'type' => 'date',
        'roles' => ['admin'],
    ],

    'description' => [
        'label' => 'Form Description (optional)',
        'type' => 'textarea',
        'rows' => 3,
        'placeholder' => 'Explain the event form for participants.',
        'roles' => ['admin'],
    ],

];
