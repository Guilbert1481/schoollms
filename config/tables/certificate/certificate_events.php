<?php

return [

    'labels' => [
        'event_name' => 'Event',
        'event_type' => 'Type',
        'template_name' => 'Template',
        'recipients_count' => 'Recipients',
        'date_issued_default' => 'Issued Date',
    ],

    'columns' => [
        ['key' => 'id', 'label' => 'ID'],
        ['key' => 'event_name', 'label' => 'Event'],
        ['key' => 'event_type', 'label' => 'Type'],
        ['key' => 'template_name', 'label' => 'Template'],
        ['key' => 'recipients_count', 'label' => 'Recipients'],
        ['key' => 'date_issued_default', 'label' => 'Issued Date'],
        ['key' => 'actions', 'label' => 'Actions'],
    ],

    'form' => [
        'event_name',
        'event_type',
        'template_id',
        'certificate_title_default',
        'date_issued_default',
    ],

    'hidden' => [
        'school_id',
        'metadata',
        'created_at',
        'updated_at',
    ],

    'auto' => [
        'school_id',
    ],

    'relations' => [
        'template' => 'template_id',
    ],

];
