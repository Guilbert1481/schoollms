@php
    $studentName = trim(($student?->first_name ?? '').' '.($student?->last_name ?? '')) ?: 'the student';
    $schoolName  = $school?->school_name ?? config('app.name');
@endphp
<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; color: #1f2937; max-width: 640px; margin: 0 auto;">
    <p>Dear {{ $recipientName ?: 'Parent / Guardian' }},</p>

    <p>
        We are writing to inform you that an enrolment application has been
        submitted for <strong>{{ $studentName }}</strong> at <strong>{{ $schoolName }}</strong>.
    </p>

    <p>
        A copy of the complete application is attached to this email as a PDF
        document for your records. Please review the information carefully and
        contact the registrar's office if any corrections are needed.
    </p>

    <p style="margin-top: 24px; color: #475569; font-size: 12px;">
        Reference Number:
        <strong>ENR-{{ str_pad($enrollment->id, 6, '0', STR_PAD_LEFT) }}</strong><br>
        Submission Date: {{ $enrollment->created_at?->format('M d, Y g:i A') }}
    </p>

    <p style="margin-top: 24px;">
        Sincerely,<br>
        <strong>{{ $schoolName }}</strong><br>
        Office of the Registrar
    </p>

    <hr style="border:none;border-top:1px solid #e2e8f0;margin-top:24px;">
    <p style="font-size: 11px; color: #94a3b8;">
        This is an automated message. Please do not reply directly to this email.
    </p>
</body>
</html>
