<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SETTINGS ROUTES
|--------------------------------------------------------------------------
|
| This file handles ALL settings-related routes.
| Sidebar visibility ≠ security.
| Middleware here enforces actual access control.
|
*/

Route::middleware(['auth'])
    ->prefix('settings')
    ->name('settings.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | 1️⃣  ACCESSIBLE TO ALL ROLES
        |--------------------------------------------------------------------------
        */

        Route::get('/profile', [ProfileController::class, 'index'])
            ->name('profile');

        Route::post('/profile', [ProfileController::class, 'update'])
            ->name('profile.update');

        Route::post('/profile/password', [ProfileController::class, 'updatePassword'])
            ->name('profile.password');

        // Turn off the user's own 2FA (enabling goes through the /2fa/setup flow).
        Route::post('/profile/2fa/disable', [ProfileController::class, 'disableTwoFactor'])
            ->name('profile.2fa.disable');

        // Admin-only mail / SMS configuration. Authorization is enforced
        // inside the controller (so the routes themselves can sit beside
        // the public profile endpoints).
        Route::post('/profile/smtp', [ProfileController::class, 'updateSmtp'])->name('profile.smtp');
        Route::post('/profile/sms', [ProfileController::class, 'updateSms'])->name('profile.sms');
        Route::post('/profile/smtp/test', [ProfileController::class, 'sendTestEmail'])->name('profile.smtp.test');

        // 👉 ADD MORE "ALL USERS" SETTINGS BELOW
        // Route::get('/notifications', ...)->name('notifications');

        /*
        |--------------------------------------------------------------------------
        | 2️⃣  ADMIN ONLY SETTINGS
        |--------------------------------------------------------------------------
        */

        Route::group([], function () {

            // UserController not yet implemented — placeholder commented out.
            // Route::resource('users', UserController::class)->names('users');

            Route::get('/subscription', fn () => view('settings.subscription'))
                ->name('subscription');

            Route::get('/quote', fn () => view('settings.quote'))
                ->name('quote');

            // 👉 ADD MORE ADMIN SETTINGS BELOW
            // Route::get('/system', ...)->name('system');

        });

        /*
        |--------------------------------------------------------------------------
        | 3️⃣  ADMIN + DEAN SETTINGS
        |--------------------------------------------------------------------------
        */

        Route::middleware(['role:admin,dean'])->group(function () {

            Route::get('/logs', fn () => view('settings.logs'))
                ->name('logs.index');

            // 👉 ADD MORE ADMIN + DEAN SETTINGS BELOW
            // Route::get('/audit', ...)->name('audit.index');

        });

        /*
        |--------------------------------------------------------------------------
        | 4️⃣  DEAN ONLY SETTINGS
        |--------------------------------------------------------------------------
        */

        Route::middleware(['role:dean'])->group(function () {

            Route::get('/academic-controls', fn () => view('settings.academic'))
                ->name('academic.index');

            // 👉 ADD MORE DEAN SETTINGS BELOW

        });

        /*
        |--------------------------------------------------------------------------
        | 5️⃣  FUTURE ROLE EXPANSION SECTION
        |--------------------------------------------------------------------------
        |
        | Example for program_head, finance, etc.
        | Add new role-based groups here.
        |
        */

        Route::middleware(['role:program_head'])->group(function () {

            Route::get('/program-config', fn () => view('settings.program'))
                ->name('program.index');

            // 👉 ADD MORE PROGRAM HEAD SETTINGS BELOW

        });

    });
