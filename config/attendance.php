<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Device capture secret
    |--------------------------------------------------------------------------
    |
    | Shared key an unattended attendance device (biometric / facial terminal)
    | presents in the `X-Device-Key` header to POST to the device check-in
    | endpoint. Leave unset to keep that endpoint CLOSED — the guard fails shut
    | when no secret is configured.
    |
    | This is a single deployment-wide key for the prepared seam. Real hardware
    | provisioning (per-device keys mapped to a physical location/context) is
    | future work; see App\Services\Attendance\DeviceAttendanceService.
    |
    */

    'device_secret' => env('ATTENDANCE_DEVICE_SECRET'),

];
