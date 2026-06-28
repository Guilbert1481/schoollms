<?php

use App\Http\Controllers\Dean\AcademicPoliciesController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:dean,admin,superadmin'])
    ->prefix('dean/academic-policies')
    ->name('dean.academic_policies.')
    ->controller(AcademicPoliciesController::class)
    ->group(function () {
        Route::get('/',                    'index')->name('index');
        Route::get('/create',              'create')->name('create');
        Route::post('/',                   'store')->name('store');
        Route::get('/{policy}/edit',       'edit')->name('edit');
        Route::put('/{policy}',            'update')->name('update');
        Route::delete('/{policy}',         'destroy')->name('destroy');
        Route::post('/{policy}/toggle',    'toggle')->name('toggle');
    });
