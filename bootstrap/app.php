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
            | SCHOOL MODULE
            |--------------------------------------------------------------------------
            */
            __DIR__.'/../routes/school/account-access.php',
            __DIR__.'/../routes/school/settings/school.php',
            __DIR__.'/../routes/school/settings/signatory.php',
            __DIR__.'/../routes/school/settings/term.php',
            __DIR__.'/../routes/school/settings/system.php',
            __DIR__.'/../routes/school/settings/master-data/academic-year.php',
            __DIR__.'/../routes/school/settings/master-data/calendar.php',
            __DIR__.'/../routes/school/settings/master-data/organization.php',
            __DIR__.'/../routes/school/settings/master-data/facilities.php',
            __DIR__.'/../routes/school/settings/master-data/training.php',
            __DIR__.'/../routes/school/settings/master-data/events.php',
            __DIR__.'/../routes/admin/education-nodes.php',
            __DIR__.'/../routes/school/system/dynamic.php',
                        

                        /*
            |--------------------------------------------------------------------------
            | SUPERADMIN MODULE
            |--------------------------------------------------------------------------
            */

            
            __DIR__.'/../routes/superadmin/schools.php',
            __DIR__.'/../routes/superadmin/pricing.php',
            __DIR__.'/../routes/superadmin/subscriptions.php',
            __DIR__.'/../routes/superadmin/dashboard.php',


            /*
            |--------------------------------------------------------------------------
            | ADMIN MODULE
            |--------------------------------------------------------------------------
            */
            __DIR__.'/../routes/admin/assignments.php',
            __DIR__.'/../routes/admin/service-plan.php',


            /*
            |--------------------------------------------------------------------------
            | ADMISSION MODULE
            |--------------------------------------------------------------------------
            */
            __DIR__.'/../routes/admission/sections.php',
            __DIR__.'/../routes/admission/dashboard.php',


            /*
            |--------------------------------------------------------------------------
            | REGISTRAR MODULE
            |--------------------------------------------------------------------------
            */
            __DIR__.'/../routes/staff/registrar.php',


            /*
            |--------------------------------------------------------------------------
            | TEACHER MODULES (bootstrap/app.php)
            |--------------------------------------------------------------------------
            */
            __DIR__.'/../routes/teacher/metadata.php',
            __DIR__.'/../routes/teacher/mcq-builder.php',
            __DIR__.'/../routes/teacher/dashboard.php',
            __DIR__.'/../routes/teacher/lessons/lessons.php',
            __DIR__.'/../routes/teacher/lessons/resources.php',

            /*
            |--------------------------------------------------------------------------
            | COURSE ARCHITECT MODULES
            |--------------------------------------------------------------------------
            */
            __DIR__.'/../routes/course-architect/workspace.php',
            __DIR__.'/../routes/course-architect/construction.php',
            __DIR__.'/../routes/course-architect/assets.php',
            __DIR__.'/../routes/course-architect/intelligence.php',
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
            | STUDENT MODULES
            |--------------------------------------------------------------------------
            */
            __DIR__.'/../routes/student/dashboard.php',
            __DIR__.'/../routes/student/subjects.php',
            __DIR__.'/../routes/student/transcript.php',
            __DIR__.'/../routes/student/applications.php',
            __DIR__.'/../routes/student/training/training.php',
            __DIR__.'/../routes/address.php',

            
            /*
            |--------------------------------------------------------------------------
            | TRAINING MODULES
            |--------------------------------------------------------------------------
            */
            __DIR__.'/../routes/training/trainee/dashboard.php',
            __DIR__.'/../routes/training/trainee/courses.php',
            __DIR__.'/../routes/training/trainee/sessions.php',
            __DIR__.'/../routes/training/trainee/materials.php',
            __DIR__.'/../routes/training/trainee/certificates.php',
            __DIR__.'/../routes/training/trainee/attendance.php',
            __DIR__.'/../routes/training/trainee/assessment.php',
            __DIR__.'/../routes/training/trainee/progress.php',

            /*
            |--------------------------------------------------------------------------
            | TRAINOR MODULES
            |--------------------------------------------------------------------------
            */
            __DIR__.'/../routes/training/trainor/dashboard.php',
            __DIR__.'/../routes/training/trainor/courses.php',
            __DIR__.'/../routes/training/trainor/sessions.php',
            __DIR__.'/../routes/training/trainor/materials.php',
            __DIR__.'/../routes/training/trainor/trainees.php',
            __DIR__.'/../routes/training/trainor/attendance.php',
            __DIR__.'/../routes/training/trainor/assessments.php',
            __DIR__.'/../routes/training/trainor/gradebook.php',
            __DIR__.'/../routes/training/trainor/certificates.php',
            __DIR__.'/../routes/school/settings/master-data/certificates.php',

            /*
            |--------------------------------------------------------------------------
            | TRAINING PROGRAM HEAD MODULES
            |--------------------------------------------------------------------------
            */
            __DIR__.'/../routes/training/program-head.php',
            __DIR__.'/../routes/training/program-head/courses.php',
            __DIR__.'/../routes/training/program-head/trainors.php',


            /*
            |--------------------------------------------------------------------------
            | DEAN MODULE
            |--------------------------------------------------------------------------
            */

            __DIR__.'/../routes/dean/dashboard.php',
            __DIR__.'/../routes/dean/programs.php',
            __DIR__.'/../routes/dean/curricula.php',
            __DIR__.'/../routes/dean/curricula_panel.php',
            __DIR__.'/../routes/dean/academic_policies.php',

            

            /*
            |--------------------------------------------------------------------------
            | PRINCIPAL MODULE (Basic Education)
            |--------------------------------------------------------------------------
            */

            __DIR__.'/../routes/principal/dashboard.php',
            __DIR__.'/../routes/principal/curricula_panel.php',
            __DIR__.'/../routes/principal/ay_terms.php',


            /*
            |--------------------------------------------------------------------------
            | STAFF / PROGRAM HEAD MODULES
            |--------------------------------------------------------------------------
            */
            __DIR__.'/../routes/staff/program_head/subjects.php',
            __DIR__.'/../routes/staff/program_head/topic_lesson.php',
            __DIR__.'/../routes/staff/program_head/lessons_competency.php',
           
            
            __DIR__.'/../routes/admission/enrollment-settings.php',

            /*
            |--------------------------------------------------------------------------
            | GLOBAL MODULES
            |--------------------------------------------------------------------------
            */
            __DIR__.'/../routes/student/enrollment.php',
            __DIR__.'/../routes/communication.php',
            __DIR__.'/../routes/superpriority.php',
            __DIR__.'/../routes/profile/photo.php',
            __DIR__.'/../routes/reusable/cascading-dropdown.php',

            __DIR__.'/../routes/forms.php',
            /*
            |--------------------------------------------------------------------------
            | MODULAR ROUTING PLACEHOLDER (SIDEBAR)
            |--------------------------------------------------------------------------
            */

            __DIR__.'/../routes/modular_routing/settings.php',

            
            /*Reusable routes*/
            __DIR__.'/../routes/reusable/assignable.php',

            /*Finance routes*/
            __DIR__.'/../routes/finance/payments.php',
            __DIR__.'/../routes/finance/billing.php',
            __DIR__.'/../routes/finance/accounts.php',
            __DIR__.'/../routes/student/finance.php',

            /*Tools routes*/
            __DIR__.'/../routes/tools/index.php',
            __DIR__.'/../routes/tools/scientific-calculator.php',
            __DIR__.'/../routes/tools/editpdf.php',
            __DIR__.'/../routes/tools/camera.php',
            __DIR__.'/../routes/tools/pdftoimage.php',
            __DIR__.'/../routes/tools/budget.php',
            __DIR__.'/../routes/tools/drive.php',
            __DIR__.'/../routes/tools/games.php',
            __DIR__.'/../routes/tools/video-conference/index.php',
            __DIR__.'/../routes/tools/video-conference/rooms.php',
            __DIR__.'/../routes/tools/video-conference/sessions.php',
            __DIR__.'/../routes/tools/video-conference/meeting-room.php',
            __DIR__.'/../routes/tools/video-conference/participants.php',
            __DIR__.'/../routes/tools/video-conference/chat.php',
            __DIR__.'/../routes/tools/video-conference/raise-hand.php',
            __DIR__.'/../routes/tools/video-conference/screen-share.php',
            __DIR__.'/../routes/tools/video-conference/recording.php',
            __DIR__.'/../routes/tools/video-conference/whiteboard.php',
            __DIR__.'/../routes/tools/video-conference/notes.php',
            __DIR__.'/../routes/tools/video-conference/buzz-in.php',
            __DIR__.'/../routes/tools/video-conference/attendance.php',
            __DIR__.'/../routes/tools/video-conference/permissions.php',
            __DIR__.'/../routes/tools/video-conference/history.php',

            /*Scheduler routes*/
            __DIR__.'/../routes/scheduler/scheduler.php',

            /*Guidance Counselor routes*/
            __DIR__.'/../routes/guidance_counselor/dashboard.php',

            /*Website routes*/
            __DIR__.'/../routes/website/file.php',


        ],

        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )


    ->withMiddleware(function (Middleware $middleware) {

        $middleware->web(append: [
            \App\Http\Middleware\EnsureSchoolIsActive::class,
        ]);

        $middleware->alias([

            'role'             => \App\Http\Middleware\CheckRole::class,
            '2fa'              => \App\Http\Middleware\TwoFactorMiddleware::class,
            'subscription'     => \App\Http\Middleware\CheckSubscription::class,
            'multi_redirect'   => \App\Http\Middleware\RedirectAfterLogin::class,
            'school.active'    => \App\Http\Middleware\EnsureSchoolIsActive::class,
            'enrollment.open'  => \App\Http\Middleware\EnsureEnrollmentOpen::class,

        ]);

    })


    ->withExceptions(function (Exceptions $exceptions) {
        // Session expired / CSRF mismatch (HTTP 419):
        // bounce the user back to their school's public website
        // instead of the global /login page.
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            return \App\Support\ExpiredSessionRedirect::handle($request);
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($e->getStatusCode() === 419) {
                return \App\Support\ExpiredSessionRedirect::handle($request);
            }
        });
    })


    ->create();
