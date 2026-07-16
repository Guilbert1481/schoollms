<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Services\Attendance\DeviceAttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Ingestion endpoint for unattended attendance devices (biometric / facial
 * terminals). The device authenticates with a shared key (attendance.device
 * middleware); the tenant is the school resolved from the request host, never
 * anything the device asserts. Thin — all work is in DeviceAttendanceService.
 */
class DeviceAttendanceController extends Controller
{
    public function checkin(Request $request, DeviceAttendanceService $service): JsonResponse
    {
        $school = current_school();

        if (! $school) {
            return response()->json(['ok' => false, 'message' => 'No school is resolved for this device host.'], 422);
        }

        $data = $request->validate([
            'context' => ['required', 'string', 'max:64'],
            'student_number' => ['required', 'string', 'max:64'],
            'method' => ['required', Rule::in([AttendanceRecord::METHOD_BIOMETRIC, AttendanceRecord::METHOD_FACIAL])],
            'time' => ['nullable', 'date_format:H:i'],
        ]);

        $result = $service->record(
            (int) $school->id,
            $data['context'],
            $data['student_number'],
            $data['method'],
            $data['time'] ?? null,
        );

        return response()->json($result, $result['ok'] ? 200 : 422);
    }
}
