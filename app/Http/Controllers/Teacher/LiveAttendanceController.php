<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Section;
use App\Services\Attendance\QrAttendanceTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Teacher live-QR attendance screen. Displays a QR that the qr() endpoint
 * rotates every few seconds; students scan it to check themselves in. The
 * context (which class/section) is re-derived from the teacher's ownership on
 * every call, so a teacher can only run a live board for their own roster.
 *
 * The QR is rendered client-side (qrcodejs, as elsewhere in the app); qr() just
 * returns the current scan URL, so the token is server-signed and the client
 * only draws it.
 */
class LiveAttendanceController extends Controller
{
    public function show(Request $request)
    {
        $ctx = $this->ownedContext($request);

        return view('teacher.attendance.live', [
            'context' => $ctx['context'],
            'label' => $ctx['label'],
            'window' => QrAttendanceTokenService::DEFAULT_WINDOW,
        ]);
    }

    public function qr(Request $request, QrAttendanceTokenService $tokens)
    {
        $ctx = $this->ownedContext($request);
        $window = QrAttendanceTokenService::DEFAULT_WINDOW;
        $token = $tokens->token($ctx['context'], $window);

        return response()->json([
            'url' => url()->route('attendance.scan', ['c' => $ctx['context'], 't' => $token]),
            'window' => $window,
        ]);
    }

    /* ------------------------------------------------------------------ */

    /** @return array{context: string, label: string} */
    private function ownedContext(Request $request): array
    {
        $userId = Auth::id();

        if ($request->query('type') === 'session' && $request->filled('class_id')) {
            $class = ClassModel::where('teacher_id', $userId)->findOrFail((int) $request->query('class_id'));

            return ['context' => "session:{$class->id}", 'label' => $class->subject->name ?? $class->code];
        }

        if ($request->query('type') === 'daily' && $request->filled('section_id')) {
            $section = Section::where('adviser_id', $userId)->findOrFail((int) $request->query('section_id'));

            return ['context' => "daily:{$section->id}", 'label' => $section->name.' — Homeroom'];
        }

        abort(404);
    }
}
