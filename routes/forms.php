<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FormController;

Route::middleware(['web', 'auth'])->group(function () {

    Route::post('/form/{formKey}/save', [FormController::class, 'save'])
        ->name('form.save')
        ->middleware('throttle:uploads'); // H6 — file-bearing endpoint

    

});