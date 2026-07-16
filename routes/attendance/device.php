<?php

use App\Http\Controllers\Attendance\DeviceAttendanceController;
use Illuminate\Support\Facades\Route;

/*
 * Unattended capture-device ingestion (biometric / facial). No web session:
 * the device authenticates with a shared key (attendance.device) and the tenant
 * is resolved from the request host (school.resolve). This is the prepared seam
 * for future hardware — see App\Services\Attendance\DeviceAttendanceService.
 */
Route::middleware(['school.resolve', 'attendance.device'])
    ->post('attendance/device/checkin', [DeviceAttendanceController::class, 'checkin'])
    ->name('attendance.device.checkin');
