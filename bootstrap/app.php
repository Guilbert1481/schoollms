<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))

    ->withRouting(

        web: [

            __DIR__.'/../routes/web.php',

            /*
            |--------------------------------------------------------------------------
            | SUPERADMIN MODULE
            |--------------------------------------------------------------------------
            */

            
            __DIR__.'/../routes/superadmin/schools.php',
            __DIR__.'/../routes/superadmin/pricing.php',
            __DIR__.'/../routes/superadmin/subscriptions.php',


            /*
            |--------------------------------------------------------------------------
            | ADMIN MODULE
            |--------------------------------------------------------------------------
            */

           
            __DIR__.'/../routes/admin/assignments.php',


            /*
            |--------------------------------------------------------------------------
            | TEACHER MODULES (bootstrap/app.php)
            |--------------------------------------------------------------------------
            */
            __DIR__.'/../routes/teacher/metadata.php',
            __DIR__.'/../routes/teacher/mcq-builder.php',
            __DIR__.'/../routes/teacher/dashboard.php',
            __DIR__.'/../routes/teacher/tf-builder.php',
            __DIR__.'/../routes/teacher/identification-builder.php',
            __DIR__.'/../routes/teacher/matching-type-builder.php',
            __DIR__.'/../routes/teacher/essay-builder.php',
            __DIR__.'/../routes/teacher/mtf-builder.php',
            __DIR__.'/../routes/teacher/enumeration-builder.php',
            __DIR__.'/../routes/teacher/fib-builder.php',
            
            __DIR__.'/../routes/teacher/test-builder.php',
            __DIR__.'/../routes/teacher/test-builder-cascade-dropdown.php',
            __DIR__.'/../routes/teacher/test/test-builder/print.php',
            __DIR__.'/../routes/teacher/test/test-builder/answer-key.php',
            

            /*
            |--------------------------------------------------------------------------
            | TEACHER MODULES
            |--------------------------------------------------------------------------
            */


            

            


            /*
            |--------------------------------------------------------------------------
            | DEAN MODULE
            |--------------------------------------------------------------------------
            */

            __DIR__.'/../routes/dean/dashboard.php',
             __DIR__.'/../routes/dean/programs.php',

            

            /*
            |--------------------------------------------------------------------------
            | STAFF / PROGRAM HEAD MODULES
            |--------------------------------------------------------------------------
            */
            __DIR__.'/../routes/staff/program_head/subjects.php',
            __DIR__.'/../routes/staff/program_head/topic_lesson.php',
            __DIR__.'/../routes/staff/program_head/lessons_competency.php',
           
            

            /*
            |--------------------------------------------------------------------------
            | GLOBAL MODULES
            |--------------------------------------------------------------------------
            */

            __DIR__.'/../routes/communication.php',
            __DIR__.'/../routes/superpriority.php',
            __DIR__.'/../routes/profile/photo.php',
            __DIR__.'/../routes/reusable/cascading-dropdown.php',


            /*
            |--------------------------------------------------------------------------
            | MODULAR ROUTING PLACEHOLDER (SIDEBAR)
            |--------------------------------------------------------------------------
            */

            __DIR__.'/../routes/modular_routing_settings.php',

            
            /*Reusable routes*/
            __DIR__.'/../routes/reusable/assignable.php',


        ],

        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )


    ->withMiddleware(function (Middleware $middleware) {

        $middleware->alias([

            'role'           => \App\Http\Middleware\CheckRole::class,
            '2fa'            => \App\Http\Middleware\TwoFactorMiddleware::class,
            'subscription'   => \App\Http\Middleware\CheckSubscription::class,
            'multi_redirect' => \App\Http\Middleware\RedirectAfterLogin::class,

        ]);

    })


    ->withExceptions(function (Exceptions $exceptions) {
        //
    })


    ->create();
