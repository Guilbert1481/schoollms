<?php

return [

    'roles' => [

        'superadmin' => [
            'trend_charts' => ['school_growth'],
        ],

        'admin' => [
            'trend_charts' => [
                'revenue_trend',
                'enrollment_trend',
            ],
        ],

        'academics' => [
            'trend_charts' => [
                'enrollment_trend',
            ],
        ],

        'admission' => [
            'trend_charts' => [
                'application_trend',
            ],
        ],

        'admission_manager' => [
            'trend_charts' => [
                'application_trend',
            ],
        ],

        'student' => [
            'trend_charts' => [
                'application_trend',
            ],
        ],
    ],

    'definitions' => [

        'revenue_trend' => [
            'title' => 'Monthly Revenue Trend',
            'type'  => 'line',
            'color' => 'green',
        ],

        'enrollment_trend' => [
            'title' => 'Enrollment Trend',
            'type'  => 'bar',
            'color' => 'indigo',
        ],

        'application_trend' => [
            'title' => 'Application Trend',
            'type'  => 'line',
            'color' => 'orange',
        ],

        'school_growth' => [
            'title' => 'School Growth Trend',
            'type'  => 'line',
            'color' => 'blue',
        ],
    ],
];
