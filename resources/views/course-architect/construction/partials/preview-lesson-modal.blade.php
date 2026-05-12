{{-- In-page preview modal for lesson resources (no Save footer). --}}
<div id="lessonPreviewModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white rounded-lg shadow-lg absolute w-[760px] max-w-[95vw]"
         style="top:60px; left:50%; transform:translateX(-50%);">
        <div class="flex justify-between items-center border-b px-4 py-2">
            <h3 id="lessonPreviewTitle" class="font-semibold truncate">Preview</h3>
            <button type="button" onclick="closeModal('lessonPreviewModal')" class="text-gray-500 hover:text-gray-800">✕</button>
        </div>
        <div id="lessonPreviewBody" class="p-4 min-h-[360px] flex items-center justify-center bg-slate-50">
            <span class="text-sm text-gray-500">Loading…</span>
        </div>
    </div>
</div>

<script>
async function openLessonPreview(id) {
    const body  = document.getElementById('lessonPreviewBody');
    const title = document.getElementById('lessonPreviewTitle');
    body.innerHTML  = '<span class="text-sm text-gray-500">Loading…</span>';
    title.textContent = 'Preview';
    openModal('lessonPreviewModal');

    try {
        const url = "{{ url('course-architect/lesson-studio') }}/" + id + "/preview";
        const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        if (!res.ok) throw new Error(res.status);
        const d = await res.json();

        title.textContent = d.filename || 'Preview';

        if (!d.file_url) {
            body.innerHTML = '<span class="text-sm italic text-gray-500">No file attached for this lesson resource.</span>';
            return;
        }

        const safe = (s) => String(s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));

        if (d.file_type === 'video') {
            body.innerHTML = `
                <video controls class="w-full max-h-[70vh] rounded">
                    <source src="${safe(d.file_url)}">
                    Your browser does not support video.
                </video>`;
        } else if (d.file_type === 'pdf') {
            body.innerHTML = `<iframe src="${safe(d.file_url)}" class="w-full" style="height:70vh; border:0;"></iframe>`;
        } else if (d.file_type === 'ppt') {
            // Microsoft Office Online viewer requires a publicly reachable URL.
            // localhost / 127.0.0.1 / private IPs cannot be fetched by Microsoft's servers,
            // so we skip the embed in those cases and show a download card instead.
            const host = (() => { try { return new URL(d.file_url).hostname; } catch { return ''; } })();
            const isPrivate = /^(localhost|127\.|10\.|192\.168\.|172\.(1[6-9]|2\d|3[0-1])\.)/.test(host);

            if (isPrivate) {
                body.innerHTML = `
                    <div class="text-center px-6 py-10">
                        <div class="text-5xl mb-3">📊</div>
                        <div class="text-sm text-gray-700 mb-1 font-medium">${safe(d.filename || 'Presentation')}</div>
                        <div class="text-xs text-gray-500 mb-4">PowerPoint files cannot be previewed inline from a local server.</div>
                        <a href="${safe(d.file_url)}" target="_blank" rel="noopener"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded bg-indigo-600 text-white text-sm hover:bg-indigo-700">
                            ⬇ Download to view
                        </a>
                    </div>`;
            } else {
                const office = 'https://view.officeapps.live.com/op/embed.aspx?src=' + encodeURIComponent(d.file_url);
                body.innerHTML = `
                    <div class="w-full">
                        <iframe src="${safe(office)}" class="w-full" style="height:60vh; border:0;"></iframe>
                        <div class="mt-2 text-xs text-gray-500">
                            Cannot preview? <a href="${safe(d.file_url)}" target="_blank" rel="noopener" class="text-indigo-600 hover:underline">Download ${safe(d.filename || 'file')}</a>
                        </div>
                    </div>`;
            }
        } else {
            body.innerHTML = `<a href="${safe(d.file_url)}" target="_blank" rel="noopener" class="text-indigo-600 hover:underline">Open ${safe(d.filename || 'file')}</a>`;
        }
    } catch (e) {
        body.innerHTML = '<span class="text-sm text-red-600">Failed to load preview.</span>';
    }
}
</script>
