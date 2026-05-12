<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tiered Moderation System
    |--------------------------------------------------------------------------
    |
    | warning  = discipline / profanity / bullying
    | critical = safety / mental health / severe threats
    |
    */

    'levels' => [

        'warning' => [
            // Profanity / Bullying
            'idiot',
            'stupid',
            'moron',
            'loser',
            'hate',
            'hate you',
            'worthless',
            'fuck',
            'bitch',
            'asshole',
            'damn',
            'bastard',
            'shit',
            'dick',
            'piss',
            'cunt',
            'dumbass',
        ],

        'critical' => [
            // Self-harm / Crisis
            'suicide',
            'kill',
            'want to die',
            'cut myself',
            'self harm',

            // Severe Violence
            'bomb',
            'shoot',
            'murder',
            'attack',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Targets
    |--------------------------------------------------------------------------
    |
    | When a flagged message is detected, every user whose `users.role`
    | matches one of the listed roles will receive a FlaggedChatNotification.
    | Keep these role keys aligned with the strings stored in users.role.
    |
    */

    'notify_roles' => [
        'warning'  => ['admin', 'guidance_counselor'],
        'critical' => ['admin', 'guidance_counselor'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Office Document Preview (LibreOffice headless)
    |--------------------------------------------------------------------------
    | Path to the `soffice` executable used to convert Word/Excel/PowerPoint
    | documents to PDF on the fly so they can be previewed in the browser.
    | Leave the default null/false-y if LibreOffice is not installed — the
    | preview modal will then just show a "Preview unavailable" message.
    */
    'libreoffice_path' => env('LIBREOFFICE_PATH', 'C:\\Program Files\\LibreOffice\\program\\soffice.exe'),

];
