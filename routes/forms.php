<?php

use App\Http\Controllers\FormController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {

    Route::post('/form/{formKey}/save', [FormController::class, 'save'])
        ->name('form.save')
        ->middleware('throttle:uploads'); // H6 — file-bearing endpoint

});
