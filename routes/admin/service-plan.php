<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin/service-plan')
    ->name('admin.service-plan.')
    ->middleware(['auth', 'role:admin,superadmin'])
    ->group(function () {

        Route::view('/features', 'admin.service-plan.index')
            ->name('features');

        Route::view('/addons', 'admin.service-plan.index')
            ->name('addons');

});
