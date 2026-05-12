<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AddressController;

Route::prefix('api/address')->name('api.address.')->group(function () {
    Route::get('countries',  [AddressController::class, 'countries'])->name('countries');
    Route::get('regions',    [AddressController::class, 'regions'])->name('regions');
    Route::get('provinces',  [AddressController::class, 'provinces'])->name('provinces');
    Route::get('cities',     [AddressController::class, 'cities'])->name('cities');
    Route::get('barangays',  [AddressController::class, 'barangays'])->name('barangays');
    Route::get('zip',        [AddressController::class, 'zip'])->name('zip');
});
