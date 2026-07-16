<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates an unattended attendance capture device by a shared key it
 * presents in the `X-Device-Key` header, compared in constant time against
 * config('attendance.device_secret').
 *
 * Fails SHUT: when no secret is configured the endpoint rejects everything, so
 * a deployment that has not provisioned a device key cannot be posted to. This
 * is the transport guard for the prepared device seam; per-device credentials
 * are future work (see App\Services\Attendance\DeviceAttendanceService).
 */
class VerifyAttendanceDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('attendance.device_secret', '');
        $provided = (string) $request->header('X-Device-Key', '');

        if ($expected === '' || $provided === '' || ! hash_equals($expected, $provided)) {
            return response()->json(['ok' => false, 'message' => 'Invalid or missing device key.'], 401);
        }

        return $next($request);
    }
}
