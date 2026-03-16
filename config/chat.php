<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tiered Moderation System
    |--------------------------------------------------------------------------
    |
    | warning  = discipline-related
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
            'worthless',
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

        'warning' => [
            'idiot',
            'stupid',
            'moron',
            'loser',
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

    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Targets
    |--------------------------------------------------------------------------
    */

    'notify_roles' => [
        'warning'  => ['admin'],
        'critical' => ['admin', 'guidance'],
    ],

];
