<?php

use App\Http\Controllers\ParentPortal\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PARENT PORTAL
|--------------------------------------------------------------------------
|
| Users with role = 'parent' (auto-provisioned from guardian emails).
| Parents are cross-school (school_id = NULL): every route resolves the
| child through the parent_student links via ResolvesChildren — the
| role middleware alone is NOT sufficient authorization here.
|
*/

Route::middleware(['web', 'auth', 'role:parent'])
    ->prefix('parent')
    ->name('parent.')
    ->group(function () {

        Route::get('dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        Route::post('children/{student}/select', [DashboardController::class, 'selectChild'])
            ->name('children.select');
    });
