{{-- views/training/trainee/certificates.blade.php --}}
@extends('layouts.app') 

@section('content')
<div class="p-6">

    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold">My Certificates</h1>
        <p class="text-gray-500 text-sm">Completed and passed training certificates only.</p>
    </div>

    @php
        $columns = config('tables.training_trainee_certificates.columns', []);
        $actions = config('tables.table-actions.training_trainee_certificates', []);
        $certificateRows = $certificates->mapWithKeys(function ($item) {
            return [
                (string) $item->id => [
                    'id' => (int) $item->id,
                    'file_url' => $item->file_url,
                    'preview_url' => $item->preview_url,
                ],
            ];
        })->all();
    @endphp

    <x-table.table
        tableKey="training_trainee_certificates"
        :columns="$columns"
        :data="$certificates"
        :actions="$actions"
    />

    @if($certificates->isEmpty())
        <div class="py-8 text-center text-sm text-slate-500">
            Complete and pass a training to earn certificates.
        </div>
    @endif

    <x-modal.view id="traineeCertificatePreviewModal" title="Certificate Preview">
        <div class="space-y-3">
            <div class="rounded-lg border border-slate-200">
                <iframe id="traineeCertificatePreviewFrame" title="Certificate Preview" class="h-[60vh] w-full rounded-lg"></iframe>
            </div>

            <div class="flex justify-end gap-2">
                <button
                    type="button"
                    onclick="closeModal('traineeCertificatePreviewModal')"
                    class="rounded border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-100"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    onclick="printTraineeCertificatePreview()"
                    class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                >
                    Print
                </button>
            </div>
        </div>
    </x-modal.view>

</div>

<script>
(function () {
    const certificateRows = @json($certificateRows);
    const previewFrame = document.getElementById('traineeCertificatePreviewFrame');
    let activePreviewUrl = null;

    window.openTraineeCertificatePreview = function (id) {
        const item = certificateRows[String(id)] || certificateRows[id];
        if (!item) {
            return;
        }

        const url = item.file_url || item.preview_url;
        if (!url) {
            return;
        }

        activePreviewUrl = url;

        if (previewFrame) {
            previewFrame.src = url;
        }

        if (typeof openModal === 'function') {
            openModal('traineeCertificatePreviewModal');
            return;
        }

        const modal = document.getElementById('traineeCertificatePreviewModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('active');
        }
    };

    window.printTraineeCertificatePreview = function () {
        if (!activePreviewUrl) {
            return;
        }

        try {
            if (previewFrame && previewFrame.contentWindow) {
                previewFrame.contentWindow.focus();
                previewFrame.contentWindow.print();
                return;
            }
        } catch (error) {
            // Fall back to opening a new window when iframe print is blocked.
        }

        const win = window.open(activePreviewUrl, '_blank');
        if (win) {
            win.focus();
        }
    };
})();
</script>
@endsection