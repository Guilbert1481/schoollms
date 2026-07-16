@extends('layouts.app')

@section('content')
@php($qrUrl = route('teacher.attendance.live.qr').'?'.request()->getQueryString())
<div class="w-full space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Live check-in</h1>
            <p class="text-sm text-slate-500">{{ $label }} — students scan this code to mark themselves present.</p>
        </div>
        <a href="{{ url()->previous() }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Done
        </a>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="mx-auto flex max-w-md flex-col items-center gap-4 text-center">
            <div id="qr" class="flex h-[280px] w-[280px] items-center justify-center">
                <span class="text-sm text-slate-400">Loading code…</span>
            </div>
            <p class="text-sm font-medium text-slate-700">Scan with your phone to check in</p>
            <p class="text-xs text-slate-400">The code changes every {{ $window }} seconds. A screenshot won't work for long.</p>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    (function () {
        const qrUrl = @json($qrUrl);
        const box = document.getElementById('qr');
        const windowSeconds = Math.max(3, {{ $window }});
        let qr = null;

        async function refresh() {
            try {
                const res = await fetch(qrUrl, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                if (!data.url || typeof QRCode === 'undefined') return;
                if (!qr) {
                    box.innerHTML = '';
                    qr = new QRCode(box, { width: 260, height: 260, correctLevel: QRCode.CorrectLevel.M });
                }
                qr.makeCode(data.url);
            } catch (e) { /* keep the last code on a transient error */ }
        }

        refresh();
        setInterval(refresh, windowSeconds * 1000);
    })();
</script>
@endsection
