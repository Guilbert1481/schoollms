@php
    $cur = 'PHP';
    $student = $invoice->student;
    $studentName = $student ? trim(($student->first_name ?? '').' '.($student->last_name ?? '')) : '—';
    $schoolName = $school->name ?? config('app.name', 'School');
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 32px 36px; size: A4; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
    .header { border-bottom: 2px solid #4f46e5; padding-bottom: 10px; margin-bottom: 16px; }
    .header .school { font-size: 16px; font-weight: bold; color: #111827; }
    .header .doc { float: right; text-align: right; }
    .header .doc .title { font-size: 18px; font-weight: bold; color: #4f46e5; letter-spacing: 1px; }
    .meta td { padding: 2px 0; vertical-align: top; }
    .meta .label { color: #6b7280; padding-right: 8px; }
    table.items { width: 100%; border-collapse: collapse; margin-top: 14px; }
    table.items th { background: #f3f4f6; text-align: left; padding: 6px 8px; font-size: 10px; text-transform: uppercase; color: #374151; border-bottom: 1px solid #e5e7eb; }
    table.items td { padding: 6px 8px; border-bottom: 1px solid #f1f5f9; }
    .right { text-align: right; }
    .totals { width: 40%; margin-left: 60%; margin-top: 12px; }
    .totals td { padding: 3px 0; }
    .totals .grand { border-top: 1px solid #e5e7eb; font-weight: bold; }
    .balance { color: #b91c1c; font-weight: bold; }
    .badge { display: inline-block; padding: 2px 10px; border-radius: 9999px; font-size: 10px; font-weight: bold; }
    .footer { margin-top: 26px; font-size: 10px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 8px; }
</style>
</head>
<body>
    <div class="header">
        <div class="doc">
            <div class="title">INVOICE</div>
            <div>{{ $invoice->invoice_number }}</div>
        </div>
        <div class="school">{{ $schoolName }}</div>
        <div style="color:#6b7280;">Statement of charges</div>
    </div>

    <table class="meta" style="width:100%;">
        <tr>
            <td style="width:55%;">
                <table class="meta">
                    <tr><td class="label">Billed To</td><td><strong>{{ $studentName }}</strong></td></tr>
                    <tr><td class="label">Email</td><td>{{ $student->email ?? '—' }}</td></tr>
                    <tr><td class="label">Program</td><td>{{ $invoice->enrollment?->program?->code ?? $invoice->enrollment?->program?->name ?? '—' }}</td></tr>
                    <tr><td class="label">Term</td><td>{{ $invoice->enrollment?->term?->name ?? '—' }}</td></tr>
                </table>
            </td>
            <td style="width:45%;">
                <table class="meta">
                    <tr><td class="label">Issue Date</td><td>{{ optional($invoice->issue_date)->format('M d, Y') ?? '—' }}</td></tr>
                    <tr><td class="label">Due Date</td><td>{{ optional($invoice->due_date)->format('M d, Y') ?? '—' }}</td></tr>
                    <tr><td class="label">Status</td><td>{{ strtoupper($invoice->status) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Description</th>
                <th>Basis</th>
                <th class="right">Qty</th>
                <th class="right">Unit ({{ $cur }})</th>
                <th class="right">Amount ({{ $cur }})</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->billing_basis === 'per_unit' ? 'Per unit' : 'Fixed' }}</td>
                    <td class="right">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                    <td class="right">{{ number_format((float) $item->unit_amount, 2) }}</td>
                    <td class="right">{{ number_format((float) $item->net_amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="right">{{ $cur }} {{ number_format((float) $invoice->subtotal_amount, 2) }}</td></tr>
        <tr><td>Discount</td><td class="right">{{ $cur }} {{ number_format((float) $invoice->discount_amount, 2) }}</td></tr>
        <tr class="grand"><td>Total</td><td class="right">{{ $cur }} {{ number_format((float) $invoice->total_amount, 2) }}</td></tr>
        <tr><td>Paid</td><td class="right">{{ $cur }} {{ number_format((float) $invoice->paid_amount, 2) }}</td></tr>
        <tr><td>Balance Due</td><td class="right balance">{{ $cur }} {{ number_format((float) $invoice->balance, 2) }}</td></tr>
    </table>

    <div class="footer">
        Generated {{ now()->format('M d, Y H:i') }} &middot; {{ $schoolName }}
    </div>
</body>
</html>
