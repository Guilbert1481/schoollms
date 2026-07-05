<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; color: #1f2937; max-width: 640px; margin: 0 auto;">
    <p>Dear {{ $recipientName ?: 'Parent / Guardian' }},</p>

    <p>
        Please find attached the invoice from <strong>{{ $schoolName }}</strong>.
    </p>

    <table style="border-collapse: collapse; margin: 16px 0; font-size: 14px;">
        <tr>
            <td style="padding: 4px 16px 4px 0; color: #64748b;">Invoice Number</td>
            <td style="padding: 4px 0;"><strong>{{ $invoice->invoice_number ?: $invoice->id }}</strong></td>
        </tr>
        <tr>
            <td style="padding: 4px 16px 4px 0; color: #64748b;">Amount Due</td>
            <td style="padding: 4px 0;"><strong>&#8369;{{ number_format((float) $invoice->balance, 2) }}</strong></td>
        </tr>
        @if($invoice->due_date)
        <tr>
            <td style="padding: 4px 16px 4px 0; color: #64748b;">Due Date</td>
            <td style="padding: 4px 0;"><strong>{{ \Illuminate\Support\Carbon::parse($invoice->due_date)->format('M d, Y') }}</strong></td>
        </tr>
        @endif
    </table>

    <p>
        The complete invoice is attached as a PDF. You may settle it through the
        student portal or at the school's finance office.
    </p>

    <p style="margin-top: 24px;">
        Sincerely,<br>
        <strong>{{ $schoolName }}</strong><br>
        Finance Office
    </p>

    <hr style="border:none;border-top:1px solid #e2e8f0;margin-top:24px;">
    <p style="font-size: 11px; color: #94a3b8;">
        This is an automated message. Please do not reply directly to this email.
    </p>
</body>
</html>
