<?php

use App\Http\Controllers\Dean\CurriculaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:dean,admin,superadmin'])
    ->prefix('dean/curricula')
    ->name('dean.curricula.')
    ->controller(CurriculaController::class)
    ->group(function () {
        Route::get('/',                   'index')->name('index');
        Route::get('/create',             'create')->name('create');
        Route::post('/',                  'store')->name('store');
        Route::get('/{curriculum}',       'show')->name('show');
        Route::get('/{curriculum}/edit',  'edit')->name('edit');
        Route::put('/{curriculum}',       'update')->name('update');
        Route::delete('/{curriculum}',    'destroy')->name('destroy');
        Route::post('/{curriculum}/toggle','toggle')->name('toggle');
    });
