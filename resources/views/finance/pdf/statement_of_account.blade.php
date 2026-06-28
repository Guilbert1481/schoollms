@php
    $cur = 'PHP';
    $student = $statement->student;
    $studentName = $student ? trim(($student->first_name ?? '').' '.($student->last_name ?? '')) : '—';
    $schoolName = $school->name ?? config('app.name', 'School');
    $items = $statement->line_items ?? [];
    $note = \App\Models\FinanceSetting::where('school_id', $statement->school_id)->value('soa_footer_note');
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
    .header .doc .title { font-size: 16px; font-weight: bold; color: #4f46e5; letter-spacing: 1px; }
    .meta td { padding: 2px 0; vertical-align: top; }
    .meta .label { color: #6b7280; padding-right: 8px; }
    .summary { width: 100%; border-collapse: collapse; margin-top: 14px; }
    .summary td { width: 25%; padding: 8px; border: 1px solid #e5e7eb; text-align: center; }
    .summary .k { font-size: 9px; text-transform: uppercase; color: #6b7280; }
    .summary .v { font-size: 13px; font-weight: bold; }
    table.tx { width: 100%; border-collapse: collapse; margin-top: 16px; }
    table.tx th { background: #f3f4f6; text-align: left; padding: 6px 8px; font-size: 10px; text-transform: uppercase; color: #374151; border-bottom: 1px solid #e5e7eb; }
    table.tx td { padding: 6px 8px; border-bottom: 1px solid #f1f5f9; }
    .right { text-align: right; }
    .footer { margin-top: 26px; font-size: 10px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 8px; }
</style>
</head>
<body>
    <div class="header">
        <div class="doc">
            <div class="title">STATEMENT OF ACCOUNT</div>
            <div>{{ $statement->soa_number }}</div>
        </div>
        <div class="school">{{ $schoolName }}</div>
        <div style="color:#6b7280;">Period: {{ $statement->period_label }}</div>
    </div>

    <table class="meta" style="width:100%;">
        <tr>
            <td style="width:55%;">
                <table class="meta">
                    <tr><td class="label">Student</td><td><strong>{{ $studentName }}</strong></td></tr>
                    <tr><td class="label">Email</td><td>{{ $student->email ?? '—' }}</td></tr>
                </table>
            </td>
            <td style="width:45%;">
                <table class="meta">
                    <tr><td class="label">Generated</td><td>{{ optional($statement->generated_at)->format('M d, Y') ?? '—' }}</td></tr>
                    <tr><td class="label">Due Date</td><td>{{ optional($statement->due_date)->format('M d, Y') ?? '—' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td><div class="k">Opening Balance</div><div class="v">{{ $cur }} {{ number_format((float) $statement->opening_balance, 2) }}</div></td>
            <td><div class="k">Charges</div><div class="v">{{ $cur }} {{ number_format((float) $statement->total_charges, 2) }}</div></td>
            <td><div class="k">Payments</div><div class="v">{{ $cur }} {{ number_format((float) $statement->total_credits, 2) }}</div></td>
            <td><div class="k">Closing Balance</div><div class="v">{{ $cur }} {{ number_format((float) $statement->closing_balance, 2) }}</div></td>
        </tr>
    </table>

    <table class="tx">
        <thead>
            <tr>
                <th>Date</th>
                <th>Description</th>
                <th>Reference</th>
                <th class="right">Charge</th>
                <th class="right">Payment</th>
                <th class="right">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                <tr>
                    <td>{{ !empty($item['date']) ? \Carbon\Carbon::parse($item['date'])->format('M d, Y') : '' }}</td>
                    <td>{{ $item['description'] ?? '' }}</td>
                    <td>{{ $item['reference'] ?? '' }}</td>
                    <td class="right">{{ ($item['debit'] ?? 0) > 0 ? number_format((float) $item['debit'], 2) : '' }}</td>
                    <td class="right">{{ ($item['credit'] ?? 0) > 0 ? number_format((float) $item['credit'], 2) : '' }}</td>
                    <td class="right">{{ number_format((float) ($item['balance_after'] ?? 0), 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;color:#9ca3af;padding:14px;">No transactions in this period (carry-over balance only).</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($note)
        <div class="footer">{!! nl2br(e($note)) !!}</div>
    @endif
    <div class="footer">Generated {{ now()->format('M d, Y H:i') }} &middot; {{ $schoolName }}</div>
</body>
</html>
