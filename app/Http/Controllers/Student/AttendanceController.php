<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Academics → Attendance: the student's own attendance records — month by
 * month with per-status counts and an attendance rate (present, late, and
 * excused all count as attended, matching the dashboard's rate logic).
 */
class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->where('school_id', $user->school_id)->first();
        abort_unless($student, 403);

        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'status' => ['nullable', Rule::in(AttendanceRecord::STATUSES)],
        ]);

        $month = Carbon::createFromFormat('Y-m', $validated['month'] ?? now()->format('Y-m'))->startOfMonth();
        $status = $validated['status'] ?? null;

        $records = AttendanceRecord::query()
            ->where('student_id', $student->id)
            ->whereBetween('attendance_date', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->with(['class.subject', 'section'])
            ->orderByDesc('attendance_date')
            ->orderByDesc('time_in')
            ->get();

        // Summary always covers the whole month regardless of the status filter.
        $monthRecords = $status
            ? AttendanceRecord::query()
                ->where('student_id', $student->id)
                ->whereBetween('attendance_date', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()])
                ->get(['status'])
            : $records;

        $counts = collect(AttendanceRecord::STATUSES)
            ->mapWithKeys(fn ($s) => [$s => $monthRecords->where('status', $s)->count()]);
        $total = $monthRecords->count();
        $attended = $monthRecords->whereIn('status', ['present', 'late', 'excused'])->count();

        return view('student.attendance.index', [
            'records' => $records,
            'month' => $month,
            'status' => $status,
            'counts' => $counts,
            'total' => $total,
            'rate' => $total > 0 ? (int) round($attended / $total * 100) : null,
        ]);
    }
}
