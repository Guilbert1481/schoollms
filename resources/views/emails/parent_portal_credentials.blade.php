<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; color: #1f2937; max-width: 640px; margin: 0 auto;">
    <p>Dear {{ $parentName ?: 'Parent / Guardian' }},</p>

    <p>
        A Parent Portal account has been created for you by
        <strong>{{ $schoolName }}</strong> so you can follow
        <strong>{{ $studentName }}</strong>'s enrollment, grades, schedule, and billing.
    </p>

    <table style="border-collapse: collapse; margin: 16px 0; font-size: 14px;">
        <tr>
            <td style="padding: 4px 16px 4px 0; color: #64748b;">Login Email</td>
            <td style="padding: 4px 0;"><strong>{{ $email }}</strong></td>
        </tr>
        <tr>
            <td style="padding: 4px 16px 4px 0; color: #64748b;">One-Time Password</td>
            <td style="padding: 4px 0;"><strong style="font-family: monospace; letter-spacing: 1px;">{{ $temporaryPassword }}</strong></td>
        </tr>
    </table>

    <p>
        Sign in at <a href="{{ $loginUrl }}" style="color: #4f46e5;">{{ $loginUrl }}</a>.
        You will be asked to choose your own password on first login.
    </p>

    <p>
        If you have more than one child enrolled with us, all of them will appear
        under this same account.
    </p>

    <p style="margin-top: 24px;">
        Sincerely,<br>
        <strong>{{ $schoolName }}</strong><br>
        Registrar's Office
    </p>

    <hr style="border:none;border-top:1px solid #e2e8f0;margin-top:24px;">
    <p style="font-size: 11px; color: #94a3b8;">
        This is an automated message. Please do not reply directly to this email.
        If you did not expect this account, please contact the school registrar.
    </p>
</body>
</html>
