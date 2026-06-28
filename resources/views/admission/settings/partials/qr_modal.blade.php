{{-- QR-code modal for enrolment sessions --}}
<div id="qrModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4"
     onclick="if(event.target===this) closeQrModal()">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-md overflow-hidden">

        <div class="p-5 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h2 class="text-base font-black text-slate-900">Enrolment QR Code</h2>
                <p id="qrModalSubtitle" class="text-xs text-slate-500 mt-0.5"></p>
            </div>
            <button type="button" onclick="closeQrModal()"
                    class="p-2 rounded-lg hover:bg-slate-100 text-slate-400">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <div class="p-6 flex flex-col items-center gap-4">
            <div id="qrCanvas"
                 class="p-4 bg-white border-4 border-slate-100 rounded-xl shadow-sm"></div>

            <div class="w-full">
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">
                    Public enrolment link
                </label>
                <div class="flex gap-2">
                    <input type="text" id="qrLinkInput" readonly
                           class="flex-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700">
                    <button type="button" onclick="copyQrLink()"
                            class="px-3 py-2 bg-indigo-600 text-white rounded-lg font-bold text-xs hover:bg-indigo-700 flex items-center gap-1 whitespace-nowrap">
                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                        <span id="qrCopyLabel">Copy Link</span>
                    </button>
                </div>
                <p class="text-[11px] text-slate-400 mt-2">
                    Anyone who scans this code (or opens the link) will land on
                    a sign-in / sign-up page, then be sent to the enrolment
                    form.
                </p>
            </div>
        </div>

        <div class="p-4 border-t border-slate-200 bg-slate-50 flex items-center justify-end gap-2">
            <button type="button" onclick="closeQrModal()"
                    class="px-4 py-2 rounded-lg text-xs font-extrabold border border-slate-200 bg-white text-slate-700 hover:bg-slate-100">
                Cancel
            </button>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
(function () {
    let qrInstance = null;

    window.openQrModal = function (payload) {
        const modal    = document.getElementById('qrModal');
        const canvas   = document.getElementById('qrCanvas');
        const input    = document.getElementById('qrLinkInput');
        const subtitle = document.getElementById('qrModalSubtitle');

        // Reset
        canvas.innerHTML = '';
        input.value      = payload.url || '';
        subtitle.textContent = payload.title || '';

        if (typeof QRCode !== 'undefined' && payload.url) {
            qrInstance = new QRCode(canvas, {
                text: payload.url,
                width: 220,
                height: 220,
                correctLevel: QRCode.CorrectLevel.M,
            });
        } else {
            canvas.innerHTML = '<div class="text-xs text-rose-600 p-4">QR library failed to load.</div>';
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');

        if (window.lucide) window.lucide.createIcons();
    };

    window.closeQrModal = function () {
        const modal = document.getElementById('qrModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    window.copyQrLink = function () {
        const input = document.getElementById('qrLinkInput');
        const label = document.getElementById('qrCopyLabel');
        input.select();
        input.setSelectionRange(0, 99999);
        try {
            navigator.clipboard.writeText(input.value).then(() => {
                label.textContent = 'Copied!';
                setTimeout(() => (label.textContent = 'Copy Link'), 1500);
            });
        } catch (e) {
            document.execCommand('copy');
            label.textContent = 'Copied!';
            setTimeout(() => (label.textContent = 'Copy Link'), 1500);
        }
    };
})();
</script>
