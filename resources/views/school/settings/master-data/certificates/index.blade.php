@extends('layouts.app')

@section('content')

<div class="max-w-7xl mx-auto p-6 space-y-6">

    @include('school.settings.partials.master-data._header')

    @include('partials.tabs', [
        'tabs' => config('tabs.tabs.master_data')
    ])

    <div class="bg-white p-4 rounded shadow">

@php
    $certificateTemplatePreviewData = $data->mapWithKeys(function ($template) {
        return [
            $template->id => [
                'id' => $template->id,
                'name' => $template->name,
                'layout_json' => $template->layout_json ?? [],
                'orientation' => $template->orientation ?? 'landscape',
                'paper_size' => $template->paper_size ?? 'a4',
                'background_image' => $template->background_image ?? null,
            ],
        ];
    });

    $certificateSection = request('section', 'templates');
    if (!in_array($certificateSection, ['templates', 'events'], true)) {
        $certificateSection = 'templates';
    }

    $certificateEventColumns = config('tables.certificate.certificate_events.columns', []);
    $certificateEventColspan = count($certificateEventColumns) ?: 1;
    $certificateRecipientColumns = config('tables.certificate.certificate_event_recipients.columns', []);
    $certificateRecipientColspan = count($certificateRecipientColumns) ?: 1;
@endphp

@if($certificateSection === 'templates')
<h2 class="text-lg font-bold mb-4">Certificate Templates</h2>

<x-table.table
    tableKey="certificate_templates"
    :columns="$columns"
    :data="$data"
    :actions="config('tables.table-actions.certificate_templates')" 
    createModal="certificateCreateModal"
    createLabel="Create"
    editModal="certificateEditModal"
    deleteRoute="school.settings.master-data.certificates.destroy"
/>
@endif

@if($certificateSection === 'events')
<div class="mt-8 rounded-lg border border-slate-200 bg-slate-50 p-4">
    <div class="mb-4">
        <div>
            <h2 class="text-lg font-bold mb-1 text-slate-900">Certificate Events</h2>
            <p class="text-sm text-slate-500">Select an event and manage recipients on this page.</p>
        </div>
    </div>

    <div id="certificateEventsView" class="rounded-xl border border-slate-200 bg-white p-3">
        <div class="mb-3 flex items-center justify-between gap-3">
            <input id="certificateEventsFilter"
                   type="text"
                   placeholder="Filter..."
                   class="w-full max-w-sm rounded-lg border border-slate-300 px-3 py-2 text-sm">

            <div class="flex items-center gap-2">
                <button id="certificateEventsColumnsBtn"
                        type="button"
                        class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">
                    Columns
                </button>
                <button id="openCertificateEventCreateModalBtn"
                        type="button"
                        class="rounded-lg border border-indigo-300 bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    Create
                </button>
            </div>
        </div>

        <p id="certificateEventsFeedback" class="mb-3 hidden rounded-lg px-3 py-2 text-sm"></p>

        <div class="overflow-x-auto rounded-lg border border-slate-200">
            <table id="certificateEventsTable" class="min-w-full text-sm">
                <thead class="bg-slate-100 text-slate-700">
                <tr>
                    @foreach($certificateEventColumns as $column)
                        <th class="px-3 py-2 text-left font-semibold"
                            data-table="certificateEvents"
                            data-column="{{ $column['key'] ?? '' }}">
                            {{ $column['label'] ?? ucfirst(str_replace('_', ' ', $column['key'] ?? '')) }}
                        </th>
                    @endforeach
                </tr>
                </thead>
                <tbody id="certificateEventsTableBody" class="divide-y divide-slate-100">
                    <tr>
                        <td colspan="{{ $certificateEventColspan }}" class="px-3 py-4 text-center text-slate-500">Loading events...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <x-table.column-modal
            :columns="$certificateEventColumns"
            tableKey="certificateEvents"
            :defaultVisible="['id','event_name','event_type','template_name','recipients_count','date_issued_default','actions']"
        />
    </div>

    <div id="certificateRecipientsView" class="hidden rounded-xl border border-slate-200 bg-white p-3">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <button id="backToCertificateEventsBtn"
                        type="button"
                        class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">
                    Back
                </button>
                <p id="certificateRecipientsContext" class="text-sm text-slate-600"></p>
            </div>

            <input id="certificateRecipientsFilter"
                   type="text"
                   placeholder="Filter..."
                   class="w-full max-w-sm rounded-lg border border-slate-300 px-3 py-2 text-sm">

            <div class="flex items-center gap-2">
                <button id="certificateRecipientsColumnsBtn"
                        type="button"
                        class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">
                    Columns
                </button>
                <button id="openCertificateRecipientCreateModalBtn"
                        type="button"
                        class="rounded-lg border border-emerald-300 bg-emerald-100 px-3 py-2 text-sm font-medium text-emerald-800 hover:bg-emerald-200">
                    Add Recipient
                </button>
                <button id="printAllCertificateBtn"
                        type="button"
                        class="rounded-lg border border-indigo-300 bg-indigo-100 px-3 py-2 text-sm font-medium text-indigo-800 hover:bg-indigo-200">
                    Print Certificates
                </button>
            </div>
        </div>

        <p id="certificateRecipientsManageFeedback" class="mb-3 hidden rounded-lg px-3 py-2 text-sm"></p>

        <div class="overflow-x-auto rounded-lg border border-slate-200">
            <table id="certificateRecipientsTable" class="min-w-full text-sm">
                <thead class="bg-slate-100 text-slate-700">
                <tr>
                    @foreach($certificateRecipientColumns as $column)
                        <th class="px-3 py-2 text-left font-semibold"
                            data-table="certificateRecipients"
                            data-column="{{ $column['key'] ?? '' }}">
                            {{ $column['label'] ?? ucfirst(str_replace('_', ' ', $column['key'] ?? '')) }}
                        </th>
                    @endforeach
                </tr>
                </thead>
                <tbody id="certificateRecipientsTableBody" class="divide-y divide-slate-100">
                    <tr>
                        <td colspan="{{ $certificateRecipientColspan }}" class="px-3 py-4 text-center text-slate-500">Select an event.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <x-table.column-modal
            :columns="$certificateRecipientColumns"
            tableKey="certificateRecipients"
            :defaultVisible="['recipient_name','certificate_title','award_title','activity_name','recognition_reason','issued_date','status','actions']"
        />
    </div>
</div>
@endif

<x-modal.form id="certificateEventCreateModal" title="Create Certificate Event" widthClass="w-[92vw] max-w-5xl">
    <form id="certificateEventCreateForm" class="space-y-3">
        @csrf

        <div>
            <label class="mb-1 block text-sm text-slate-700">Event Name</label>
            <input type="text" name="event_name" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
        </div>

        <div>
            <label class="mb-1 block text-sm text-slate-700">Event Type</label>
            <select name="event_type" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                <option value="training">Training</option>
                <option value="quiz">Quiz</option>
                <option value="pageant">Pageant</option>
                <option value="program">Program</option>
                <option value="participation">Participation</option>
                <option value="other" selected>Other</option>
            </select>
        </div>

        <div>
            <label class="mb-1 block text-sm text-slate-700">Template</label>
            <select name="template_id" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                <option value="">Select template</option>
                @foreach($data as $template)
                    <option value="{{ $template->id }}">{{ $template->name }} ({{ ucfirst($template->category ?? 'other') }})</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-slate-500">Default template fallback when no specific award/role template is mapped.</p>
        </div>

        <div>
            <label class="mb-1 block text-sm text-slate-700">Default Certificate Title</label>
            <input type="text" name="certificate_title_default" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
        </div>

        <div>
            <label class="mb-1 block text-sm text-slate-700">Default Issued Date</label>
            <input type="date" name="date_issued_default" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
        </div>
    </form>
</x-modal.form>

<x-modal.form id="certificateRecipientFormModal" title="Add Recipient">
    <form id="certificateRecipientAddForm" class="space-y-3">
        @csrf
        <input type="hidden" name="event_id" value="">

        <div>
            <label class="mb-1 block text-sm text-slate-700">Activity (Optional)</label>
            <select name="activity_name" id="certificateRecipientActivityFilter" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                <option value="">All Activities</option>
            </select>
        </div>

        <div>
            <label class="mb-1 block text-sm text-slate-700">Recipient Name</label>
            <select name="recipient_name" id="certificateRecipientNameSelect" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" required>
                <option value="">Select participant</option>
            </select>
        </div>

        <div>
            <label class="mb-1 block text-sm text-slate-700">Award Title</label>
            <div class="flex items-center gap-2">
                <input type="text" name="award_title" list="certificateAwardTitleOptions" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Select or type award (e.g., Champion, 1st Place)">
                <button type="button" id="addCertificateAwardOptionBtn" class="whitespace-nowrap rounded border border-indigo-300 bg-indigo-100 px-3 py-2 text-sm font-medium text-indigo-800 hover:bg-indigo-200">Add Award</button>
            </div>
            <datalist id="certificateAwardTitleOptions">
                <option value="Champion"></option>
            </datalist>
        </div>

        <div>
            <label class="mb-1 block text-sm text-slate-700">Certificate Title</label>
            <input type="text" name="certificate_title" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </div>

        <div>
            <label class="mb-1 block text-sm text-slate-700">Recognition Reason</label>
            <input type="text" name="recognition_reason" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="e.g. Best in Innovation, Quiz Bee Champion">
        </div>
    </form>
</x-modal.form>

<div id="certificateAwardOptionModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-md rounded-xl bg-white shadow-xl">
        <div class="flex items-center justify-between border-b px-4 py-3">
            <h4 class="text-base font-semibold text-slate-800">Add Award Option</h4>
            <button type="button" id="closeCertificateAwardOptionModalBtn" class="rounded border border-slate-300 px-3 py-1 text-sm text-slate-600 hover:bg-slate-100">Close</button>
        </div>

        <form id="certificateAwardOptionForm" class="space-y-3 px-4 py-4">
            <div>
                <label class="mb-1 block text-sm text-slate-700">Award Title</label>
                <input type="text" name="award_title" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="e.g. Champion, 1st Place" required>
            </div>

            <div class="flex items-center justify-end gap-2">
                <button type="button" id="cancelCertificateAwardOptionModalBtn" class="rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">Cancel</button>
                <button type="submit" class="rounded border border-indigo-300 bg-indigo-100 px-3 py-2 text-sm font-medium text-indigo-800 hover:bg-indigo-200">Save</button>
            </div>
        </form>
    </div>
</div>

<div id="certificateRecipientPreviewModal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/60 p-4">
    <div class="w-full max-w-6xl rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b px-4 py-3">
            <div>
                <h4 id="certificateRecipientPreviewTitle" class="text-base font-semibold text-slate-800">Certificate Preview</h4>
                <p id="certificateRecipientPreviewSubTitle" class="text-sm text-slate-500"></p>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" id="cancelCertificateRecipientPreviewBtn" class="rounded border border-slate-300 bg-white px-3 py-1 text-sm text-slate-700 hover:bg-slate-100">Cancel</button>
                <button type="button" id="printCertificateRecipientPreviewBtn" class="rounded border border-indigo-300 bg-indigo-100 px-3 py-1 text-sm font-medium text-indigo-800 hover:bg-indigo-200">Print</button>
            </div>
        </div>

        <div class="max-h-[80vh] overflow-auto bg-slate-100 p-4">
            <div class="rounded-xl bg-slate-200/60 p-4">
                <div class="flex justify-center overflow-x-auto">
                    <div id="certificateRecipientPreviewCanvas" class="relative border border-slate-300 bg-white shadow-sm"></div>
                </div>
            </div>

            <p id="certificateRecipientPreviewEmpty" class="hidden py-6 text-center text-sm text-slate-500">Template preview is not available for this recipient.</p>
        </div>
    </div>
</div>

{{-- CREATE MODAL --}}
<x-modal.form id="certificateCreateModal" title="Create Certificate Template">

    <form method="POST" action="{{ route('school.settings.master-data.certificates.store') }}">
        @csrf

        <div class="mb-3">
            <label class="block text-sm mb-1">Name</label>
            <input type="text" name="name" class="border p-2 w-full">
        </div>

        <div class="mb-3">
            <label class="block text-sm mb-1">Certificate Type</label>
            <select name="certificate_type" class="border p-2 w-full">
                <option value="internal">Internal</option>
                <option value="external">External</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="block text-sm mb-1">Category</label>
            <select name="category" class="border p-2 w-full">
                <option value="training">Training</option>
                <option value="pageant">Pageant</option>
                <option value="quiz">Quiz</option>
                <option value="participation">Participation</option>
                <option value="program">Program</option>
                <option value="other">Other</option>
            </select>
        </div>

    </form>

</x-modal.form>


{{-- EDIT MODAL --}}
<x-modal.form id="certificateEditModal" title="Edit Certificate Template">

    <form method="POST" id="editCertificateForm">
        @csrf
        @method('PUT')

        <input type="hidden" name="id">
        <input type="hidden" name="table" value="certificate_templates">

        <div class="mb-3">
            <label class="block text-sm mb-1">Name</label>
            <input type="text" name="name" class="border p-2 w-full">
        </div>

        <div class="mb-3">
            <label class="block text-sm mb-1">Certificate Type</label>
            <select name="certificate_type" class="border p-2 w-full">
                <option value="internal">Internal</option>
                <option value="external">External</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="block text-sm mb-1">Category</label>
            <select name="category" class="border p-2 w-full">
                <option value="training">Training</option>
                <option value="pageant">Pageant</option>
                <option value="quiz">Quiz</option>
                <option value="participation">Participation</option>
                <option value="program">Program</option>
                <option value="other">Other</option>
            </select>
        </div>

    </form>

</x-modal.form>

<div id="certificateTemplatePreviewModal"
     class="modal-overlay hidden fixed inset-0 z-50 items-start justify-center overflow-y-auto bg-black/50 p-4">
    <div class="my-4 flex w-full max-w-6xl max-h-[92vh] flex-col rounded-2xl bg-white shadow-2xl">
        <div class="sticky top-0 z-10 flex items-center justify-between gap-4 border-b bg-white px-6 py-4">
            <div>
                <h3 id="certificateTemplatePreviewTitle" class="text-lg font-semibold">Certificate Preview</h3>
                <p id="certificateTemplatePreviewName" class="text-sm text-gray-500"></p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button"
                        onclick="closeModal('certificateTemplatePreviewModal')"
                        class="rounded-lg border border-gray-300 bg-gray-100 px-4 py-2 text-sm text-gray-700 hover:bg-gray-200">
                    Close
                </button>
                <button type="button"
                        onclick="printCertificateTemplatePreview()"
                        class="rounded-lg border border-gray-300 bg-gray-100 px-4 py-2 text-sm text-gray-700 hover:bg-gray-200">
                    Print
                </button>
            </div>
        </div>

        <div class="overflow-auto bg-gray-100 p-6">
            <div class="rounded-2xl bg-gray-200/60 p-4">
                <div class="flex justify-center overflow-x-auto">
                    <div id="certificateTemplatePreviewCanvas"
                         class="relative bg-white border border-gray-300 shadow-sm"></div>
                </div>
            </div>

            <p id="certificateTemplatePreviewEmpty"
               class="hidden py-10 text-center text-sm text-gray-500">
                No saved certificate layout yet.
            </p>
        </div>
    </div>
</div>

<script>
window.certificateTemplatePreviewData = {{ Illuminate\Support\Js::from($certificateTemplatePreviewData) }};

(function () {
    const PAPER_SIZES = {
        a4: { width: 297, height: 210 },
        a5: { width: 210, height: 148 },
        letter: { width: 279.4, height: 215.9 },
        legal: { width: 355.6, height: 215.9 }
    };
    const LONG_EDGE = 900;

    function normalizeOrientation(value) {
        return value === 'portrait' ? 'portrait' : 'landscape';
    }

    function normalizePaperSize(value) {
        return PAPER_SIZES[value] ? value : 'a4';
    }

    function getCanvasSize(paperSize, orientation) {
        const page = PAPER_SIZES[normalizePaperSize(paperSize)];
        const normalizedOrientation = normalizeOrientation(orientation);
        const longEdge = Math.max(page.width, page.height);
        const shortEdge = Math.min(page.width, page.height);
        const scale = LONG_EDGE / longEdge;

        if (normalizedOrientation === 'portrait') {
            return {
                width: Math.round(shortEdge * scale),
                height: Math.round(longEdge * scale)
            };
        }

        return {
            width: Math.round(longEdge * scale),
            height: Math.round(shortEdge * scale)
        };
    }

    function createPreviewContent(content) {
        if (!content || !content.tagName) return null;

        const element = document.createElement(content.tagName);

        if (content.className) {
            element.className = content.className;
        }

        if (content.style) {
            element.setAttribute('style', content.style);
        }

        if (content.tagName === 'img') {
            element.src = content.src || '';
        } else {
            element.innerHTML = content.html || '';
        }

        element.removeAttribute('contenteditable');
        return element;
    }

    function createPreviewItem(item) {
        if (!item || !item.content) return null;

        const contentEl = createPreviewContent(item.content);
        if (!contentEl) return null;

        const wrapper = document.createElement('div');
        wrapper.className = 'canvas-item';
        wrapper.style.position = 'absolute';

        if (item.wrapperStyle) {
            wrapper.setAttribute('style', item.wrapperStyle);
        }

        wrapper.appendChild(contentEl);
        return wrapper;
    }

    window.openCertificateTemplatePreviewModal = function (templateId) {
        const template = window.certificateTemplatePreviewData
            ? window.certificateTemplatePreviewData[String(templateId)] || window.certificateTemplatePreviewData[templateId]
            : null;

        const canvas = document.getElementById('certificateTemplatePreviewCanvas');
        const emptyState = document.getElementById('certificateTemplatePreviewEmpty');
        const nameLabel = document.getElementById('certificateTemplatePreviewName');
        const titleLabel = document.getElementById('certificateTemplatePreviewTitle');

        if (!template || !canvas) {
            return;
        }

        const size = getCanvasSize(template.paper_size, template.orientation);
        canvas.style.width = size.width + 'px';
        canvas.style.height = size.height + 'px';
        canvas.dataset.orientation = normalizeOrientation(template.orientation);
        canvas.dataset.paperSize = normalizePaperSize(template.paper_size);

        if (template.background_image) {
            canvas.style.backgroundImage = 'url("' + template.background_image + '")';
            canvas.style.backgroundSize = 'cover';
            canvas.style.backgroundPosition = 'center';
            canvas.style.backgroundRepeat = 'no-repeat';
        } else {
            canvas.style.backgroundImage = '';
            canvas.style.backgroundSize = '';
            canvas.style.backgroundPosition = '';
            canvas.style.backgroundRepeat = '';
        }

        canvas.innerHTML = '';

        if (nameLabel) {
            nameLabel.textContent = template.name || 'Certificate Template';
        }

        if (titleLabel) {
            titleLabel.textContent = template.name
                ? template.name + ' Preview'
                : 'Certificate Preview';
        }

        const layout = Array.isArray(template.layout_json) ? template.layout_json : [];
        const hasBackground = !!template.background_image;

        if (!layout.length && !hasBackground) {
            if (emptyState) {
                emptyState.classList.remove('hidden');
            }
        } else {
            if (emptyState) {
                emptyState.classList.add('hidden');
            }

            layout.forEach(function (item) {
                const previewItem = createPreviewItem(item);
                if (previewItem) {
                    canvas.appendChild(previewItem);
                }
            });
        }

        openModal('certificateTemplatePreviewModal');
    };

    window.printCertificateTemplatePreview = function () {
        const previewCanvas = document.getElementById('certificateTemplatePreviewCanvas');
        if (!previewCanvas) return;

        if (typeof window.printCertificatePreview === 'function') {
            window.printCertificatePreview(previewCanvas);
        }
    };
})();
</script>

<script>
function openCertificateEditModal(id) {
    openModal('certificateEditModal');

    const modal = document.getElementById('certificateEditModal');
    if (!modal) return;

    const form = modal.querySelector('form');
    const idInput = modal.querySelector('input[name="id"]');

    if (idInput) {
        idInput.value = id;
    }

    if (form) {
        form.action = '/school/settings/master-data/certificates/' + id;
    }

    fetch('/school/system/dynamic/get/certificate_templates/' + id)
        .then(response => response.json())
        .then(data => {
            for (const key in data) {
                const input = modal.querySelector('[name="' + key + '"]');
                if (input) {
                    input.value = data[key];
                }
            }
        });
}
</script>

<script>
(function () {
    const eventColumns = @json(array_values($certificateEventColumns));
    const eventActions = @json(config('tables.table-actions.certificate_events', []));
    const eventColspan = {{ $certificateEventColspan }};
    const recipientColspan = {{ $certificateRecipientColspan }};
    const endpoints = {
        index: @json(route('school.settings.master-data.certificates.events.index')),
        store: @json(route('school.settings.master-data.certificates.events.store')),
        awardsStoreTemplate: @json(route('school.settings.master-data.certificates.events.awards.store', ['eventId' => '__EVENT_ID__'])),
    };

    const eventsView = document.getElementById('certificateEventsView');
    const recipientsView = document.getElementById('certificateRecipientsView');
    const eventsTableBody = document.getElementById('certificateEventsTableBody');
    const recipientsTableBody = document.getElementById('certificateRecipientsTableBody');
    const backToEventsBtn = document.getElementById('backToCertificateEventsBtn');
    const recipientsContext = document.getElementById('certificateRecipientsContext');
    const form = document.getElementById('certificateEventCreateForm');
    const eventFilterInput = document.getElementById('certificateEventsFilter');
    const recipientsFilterInput = document.getElementById('certificateRecipientsFilter');
    const openRecipientCreateModalBtn = document.getElementById('openCertificateRecipientCreateModalBtn');
    const openEventModalBtn = document.getElementById('openCertificateEventCreateModalBtn');
    const eventsColumnsBtn = document.getElementById('certificateEventsColumnsBtn');
    const printAllCertificateBtn = document.getElementById('printAllCertificateBtn');
    const recipientsColumnsBtn = document.getElementById('certificateRecipientsColumnsBtn');
    const feedback = document.getElementById('certificateEventsFeedback');
    const eventModalTitle = document.querySelector('#certificateEventCreateModal .font-semibold');

    const manageFeedback = document.getElementById('certificateRecipientsManageFeedback');
    const addRecipientForm = document.getElementById('certificateRecipientAddForm');
    const recipientActivityFilter = document.getElementById('certificateRecipientActivityFilter');
    const recipientNameSelect = document.getElementById('certificateRecipientNameSelect');
    const addAwardOptionBtn = document.getElementById('addCertificateAwardOptionBtn');
    const awardOptionsList = document.getElementById('certificateAwardTitleOptions');
    const awardOptionModal = document.getElementById('certificateAwardOptionModal');
    const awardOptionForm = document.getElementById('certificateAwardOptionForm');
    const closeAwardOptionModalBtn = document.getElementById('closeCertificateAwardOptionModalBtn');
    const cancelAwardOptionModalBtn = document.getElementById('cancelCertificateAwardOptionModalBtn');
    const recipientPreviewModal = document.getElementById('certificateRecipientPreviewModal');
    const recipientPreviewCanvas = document.getElementById('certificateRecipientPreviewCanvas');
    const recipientPreviewTitle = document.getElementById('certificateRecipientPreviewTitle');
    const recipientPreviewSubTitle = document.getElementById('certificateRecipientPreviewSubTitle');
    const recipientPreviewEmpty = document.getElementById('certificateRecipientPreviewEmpty');
    const cancelRecipientPreviewBtn = document.getElementById('cancelCertificateRecipientPreviewBtn');
    const printRecipientPreviewBtn = document.getElementById('printCertificateRecipientPreviewBtn');

    if (!eventsView || !recipientsView || !eventsTableBody || !recipientsTableBody || !backToEventsBtn || !recipientsContext || !form || !eventFilterInput || !recipientsFilterInput || !openRecipientCreateModalBtn || !openEventModalBtn || !eventsColumnsBtn || !printAllCertificateBtn || !recipientsColumnsBtn || !feedback || !manageFeedback || !addRecipientForm || !recipientActivityFilter || !recipientNameSelect || !addAwardOptionBtn || !awardOptionsList || !awardOptionModal || !awardOptionForm || !closeAwardOptionModalBtn || !cancelAwardOptionModalBtn || !recipientPreviewModal || !recipientPreviewCanvas || !recipientPreviewTitle || !recipientPreviewSubTitle || !recipientPreviewEmpty || !cancelRecipientPreviewBtn || !printRecipientPreviewBtn) {
        return;
    }

    let allEvents = [];
    let editingEventId = null;
    let selectedEventId = null;
    let activeAwardOptions = [];
    let participantPool = [];
    let eventActivityOptions = [];
    let eventActivityRoleRules = [];

    function eventScopedUrl(template, eventId) {
        return String(template || '').replace('__EVENT_ID__', String(eventId || ''));
    }

    function defaultAwardOptions() {
        return ['Champion'];
    }

    function openAwardOptionModal() {
        awardOptionForm.reset();
        awardOptionModal.classList.remove('hidden');
        awardOptionModal.classList.add('flex');

        const input = awardOptionForm.querySelector('input[name="award_title"]');
        if (input) {
            input.focus();
        }
    }

    function closeAwardOptionModal() {
        awardOptionModal.classList.add('hidden');
        awardOptionModal.classList.remove('flex');
    }

    function normalizeAwardOption(value) {
        return String(value || '').replace(/\s+/g, ' ').trim();
    }

    function uniqueAwardOptions(values) {
        const seen = new Set();
        const out = [];

        (values || []).forEach(function (item) {
            const normalized = normalizeAwardOption(item);
            const key = normalized.toLowerCase();
            if (!normalized || isAutoGeneratedAward(key) || seen.has(key)) {
                return;
            }
            seen.add(key);
            out.push(normalized);
        });

        return out;
    }

    function isAutoGeneratedAward(value) {
        const normalized = normalizeAwardOption(value).toLowerCase();
        return ['participant', 'participation', 'attendee', 'attendance'].includes(normalized);
    }

    function renderAwardOptions(values) {
        const options = uniqueAwardOptions(values);
        awardOptionsList.innerHTML = options
            .map(function (item) { return '<option value="' + escapeHtml(item) + '"></option>'; })
            .join('');

        activeAwardOptions = options;
    }

    function syncAwardOptionsFromEvent(eventData) {
        const metadataOptions = Array.isArray(eventData?.metadata?.award_options) ? eventData.metadata.award_options : [];
        const recipientOptions = Array.isArray(eventData?.recipients)
            ? eventData.recipients
                .map(function (r) { return r && r.award_title ? r.award_title : ''; })
                .filter(function (v) { return !!normalizeAwardOption(v); })
            : [];

        renderAwardOptions(defaultAwardOptions().concat(metadataOptions).concat(recipientOptions));
    }

    function csrfToken() {
        const input = form.querySelector('input[name="_token"]');
        return input ? input.value : '';
    }

    function showFeedback(message, tone) {
        feedback.textContent = message;
        feedback.classList.remove('hidden', 'bg-emerald-100', 'text-emerald-800', 'bg-rose-100', 'text-rose-800');
        if (tone === 'error') {
            feedback.classList.add('bg-rose-100', 'text-rose-800');
            return;
        }
        feedback.classList.add('bg-emerald-100', 'text-emerald-800');
    }

    function normalizeDateForInput(value) {
        if (!value) return '';
        const text = String(value);
        return text.length >= 10 ? text.slice(0, 10) : text;
    }

    function setEventModalToCreate() {
        editingEventId = null;
        form.reset();

        if (eventModalTitle) {
            eventModalTitle.textContent = 'Create Certificate Event';
        }
    }

    function setEventModalToEdit(eventItem) {
        if (!eventItem) return;

        editingEventId = eventItem.id;

        if (eventModalTitle) {
            eventModalTitle.textContent = 'Edit Certificate Event';
        }

        const setValue = function (selector, value) {
            const field = form.querySelector(selector);
            if (field) {
                field.value = value;
            }
        };

        setValue('input[name="event_name"]', eventItem.event_name || '');
        setValue('select[name="event_type"]', eventItem.event_type || 'other');
        setValue('select[name="template_id"]', String(eventItem.template_id || eventItem.template?.id || ''));
        setValue('input[name="certificate_title_default"]', eventItem.certificate_title_default || '');
        setValue('input[name="date_issued_default"]', normalizeDateForInput(eventItem.date_issued_default));
    }

    function showManageFeedback(message, tone) {
        manageFeedback.textContent = message;
        manageFeedback.classList.remove('hidden', 'bg-emerald-100', 'text-emerald-800', 'bg-rose-100', 'text-rose-800');
        if (tone === 'error') {
            manageFeedback.classList.add('bg-rose-100', 'text-rose-800');
            return;
        }
        manageFeedback.classList.add('bg-emerald-100', 'text-emerald-800');
    }

    function normalizeText(value) {
        return String(value || '').replace(/\s+/g, ' ').trim();
    }

    function normalizeRoleType(value) {
        return normalizeText(value).toLowerCase();
    }

    function participantLikeRole(value) {
        const role = normalizeRoleType(value);
        return role.includes('participant') || role.includes('contestant') || role.includes('attendee');
    }

    function sanitizeActivityRoleRules(rules) {
        if (!Array.isArray(rules)) {
            return [];
        }

        const result = rules
            .map(function (rule) {
                const activity = normalizeText(rule?.activity);
                const eligibleRoles = Array.isArray(rule?.eligible_roles)
                    ? rule.eligible_roles
                        .map(function (role) { return normalizeText(role); })
                        .filter(function (role) { return role !== ''; })
                    : [];

                if (!activity) {
                    return null;
                }

                return {
                    activity: activity,
                    issue_certificate: !!rule?.issue_certificate,
                    eligible_roles: eligibleRoles,
                    auto_generate_participation: !!rule?.auto_generate_participation,
                };
            })
            .filter(function (item) { return !!item; });

        return result;
    }

    function getActivityRule(activityName) {
        const activity = normalizeText(activityName).toLowerCase();
        if (!activity) {
            return null;
        }

        const rules = Array.isArray(eventActivityRoleRules) ? eventActivityRoleRules : [];
        return rules.find(function (rule) {
            return normalizeText(rule.activity).toLowerCase() === activity;
        }) || null;
    }

    function recipientActivityName(recipient) {
        return normalizeText(recipient?.activity_name || recipient?.custom_fields?.selected_event_type || '');
    }

    function recipientRoleType(recipient) {
        return normalizeText(recipient?.custom_fields?.selected_role_type || '');
    }

    function isRecipientAllowedByActivityRoleRule(recipient) {
        const rules = Array.isArray(eventActivityRoleRules) ? eventActivityRoleRules : [];
        if (!rules.length) {
            return true;
        }

        const activity = recipientActivityName(recipient);
        if (!activity) {
            return true;
        }

        const activityRule = getActivityRule(activity);
        if (!activityRule) {
            return true;
        }

        if (!activityRule.issue_certificate) {
            return false;
        }

        const allowedRoles = Array.isArray(activityRule.eligible_roles) ? activityRule.eligible_roles : [];
        if (!allowedRoles.length) {
            return false;
        }

        const recipientRole = normalizeRoleType(recipientRoleType(recipient));
        if (!recipientRole) {
            return true;
        }

        return allowedRoles.some(function (role) {
            return normalizeRoleType(role) === recipientRole;
        });
    }

    function isParticipantCandidate(recipient) {
        const roleType = String(recipient?.custom_fields?.selected_role_type || '').toLowerCase();
        const award = String(recipient?.award_title || '').toLowerCase();
        const title = String(recipient?.certificate_title || '').toLowerCase();

        return roleType.includes('participant')
            || roleType.includes('attendee')
            || roleType.includes('contestant')
            || isAutoGeneratedAward(award)
            || title.includes('participation')
            || title.includes('attendance')
            || title.includes('contest');
    }

    function getParticipantCandidates() {
        const source = Array.isArray(participantPool) ? participantPool : [];
        const filtered = source.filter(function (recipient) {
            return isParticipantCandidate(recipient) && isRecipientAllowedByActivityRoleRule(recipient);
        });
        return filtered.length > 0 ? filtered : source;
    }

    function renderActivityFilterOptions() {
        const candidates = getParticipantCandidates();
        const current = normalizeText(recipientActivityFilter.value);

        const ruleActivities = (Array.isArray(eventActivityRoleRules) ? eventActivityRoleRules : [])
            .filter(function (rule) { return !!rule.issue_certificate; })
            .map(function (rule) { return normalizeText(rule.activity); })
            .filter(function (item) { return item !== ''; });

        const configured = (Array.isArray(eventActivityOptions) ? eventActivityOptions : [])
            .map(function (item) { return normalizeText(item); })
            .filter(function (item) { return item !== ''; });

        const inferred = candidates
            .map(function (item) { return normalizeText(item?.activity_name); })
            .filter(function (item) { return item !== ''; });

        const source = (ruleActivities.length > 0 ? ruleActivities : configured).concat(inferred);

        const activityOptions = source
            .filter(function (item, idx, arr) {
                return arr.findIndex(function (x) { return x.toLowerCase() === item.toLowerCase(); }) === idx;
            })
            .sort(function (a, b) { return a.localeCompare(b); });

        recipientActivityFilter.innerHTML = '<option value="">All Activities</option>'
            + activityOptions.map(function (item) {
                return '<option value="' + escapeHtml(item) + '">' + escapeHtml(item) + '</option>';
            }).join('');

        if (current !== '') {
            const matched = activityOptions.find(function (item) { return item.toLowerCase() === current.toLowerCase(); });
            recipientActivityFilter.value = matched || '';
        }
    }

    function renderParticipantOptions() {
        const candidates = getParticipantCandidates();
        const selectedActivity = normalizeText(recipientActivityFilter.value).toLowerCase();
        const currentRecipient = normalizeText(recipientNameSelect.value);

        const filtered = candidates.filter(function (item) {
            if (!isRecipientAllowedByActivityRoleRule(item)) {
                return false;
            }

            if (!selectedActivity) {
                return true;
            }

            const activity = recipientActivityName(item).toLowerCase();
            return activity === selectedActivity;
        });

        recipientNameSelect.innerHTML = '<option value="">Select participant</option>'
            + filtered.map(function (item) {
                const name = normalizeText(item?.recipient_name);
                const activity = recipientActivityName(item);
                const role = recipientRoleType(item);
                const label = activity ? (name + ' - ' + activity) : name;
                const withRole = role ? (label + ' (' + role + ')') : label;

                return '<option value="' + escapeHtml(name) + '" data-activity="' + escapeHtml(activity) + '" data-role="' + escapeHtml(role) + '">' + escapeHtml(withRole) + '</option>';
            }).join('');

        if (currentRecipient !== '') {
            const hasMatch = filtered.some(function (item) {
                return normalizeText(item?.recipient_name).toLowerCase() === currentRecipient.toLowerCase();
            });

            recipientNameSelect.value = hasMatch ? currentRecipient : '';
        }
    }

    function isWinnerAward(value) {
        return normalizeAwardOption(value).toLowerCase() === 'winner';
    }

    function hasParticipationCertificate(recipient, activityName, existingRecipients) {
        const nameKey = normalizeText(recipient?.recipient_name).toLowerCase();
        const activityKey = normalizeText(activityName).toLowerCase();

        return (Array.isArray(existingRecipients) ? existingRecipients : []).some(function (row) {
            const rowName = normalizeText(row?.recipient_name).toLowerCase();
            const rowActivity = recipientActivityName(row).toLowerCase();
            const rowAward = normalizeText(row?.award_title).toLowerCase();
            const rowTitle = normalizeText(row?.certificate_title).toLowerCase();

            if (!nameKey || rowName !== nameKey || rowActivity !== activityKey) {
                return false;
            }

            return rowTitle.includes('participation') || rowAward.includes('participation') || rowAward.includes('participant');
        });
    }

    async function maybeAutoGenerateParticipationRecipients(eventId, activityName, winnerName, awardTitle) {
        if (!isWinnerAward(awardTitle)) {
            return 0;
        }

        const normalizedActivity = normalizeText(activityName);
        if (!normalizedActivity) {
            return 0;
        }

        const rule = getActivityRule(normalizedActivity);
        if (!rule || !rule.issue_certificate || !rule.auto_generate_participation) {
            return 0;
        }

        const allowedRoles = Array.isArray(rule.eligible_roles) ? rule.eligible_roles : [];
        if (!allowedRoles.some(participantLikeRole)) {
            return 0;
        }

        const payload = await fetchEventDetails(eventId);
        const eventData = payload.data || {};
        const recipients = Array.isArray(eventData.recipients) ? eventData.recipients : [];
        const winnerNameKey = normalizeText(winnerName).toLowerCase();

        const candidates = recipients.filter(function (recipient) {
            const activity = recipientActivityName(recipient).toLowerCase();
            const role = normalizeRoleType(recipientRoleType(recipient));
            const name = normalizeText(recipient?.recipient_name).toLowerCase();

            return activity === normalizedActivity.toLowerCase()
                && participantLikeRole(role)
                && allowedRoles.some(function (allowedRole) { return normalizeRoleType(allowedRole) === role; })
                && name !== ''
                && name !== winnerNameKey
                && !hasParticipationCertificate(recipient, normalizedActivity, recipients);
        });

        let generated = 0;
        for (const candidate of candidates) {
            try {
                await createRecipient(eventId, {
                    recipient_name: normalizeText(candidate?.recipient_name),
                    certificate_title: 'Certificate of Participation',
                    award_title: null,
                    activity_name: normalizedActivity,
                    recognition_reason: null,
                });
                generated += 1;
            } catch (error) {
                // Ignore single-row failures so one bad row doesn't block all eligible candidates.
            }
        }

        return generated;
    }

    function selectedEvent() {
        return allEvents.find(function (row) { return Number(row.id) === Number(selectedEventId); }) || null;
    }

    function syncRecipientFormEventId() {
        const eventIdInput = addRecipientForm.querySelector('input[name="event_id"]');
        if (eventIdInput) {
            eventIdInput.value = selectedEventId ? String(selectedEventId) : '';
        }
    }

    function showEventsView() {
        eventsView.classList.remove('hidden');
        recipientsView.classList.add('hidden');
    }

    function showRecipientsView(eventItem) {
        selectedEventId = Number(eventItem.id || 0);
        syncRecipientFormEventId();
        recipientsContext.textContent = 'Event: ' + (eventItem.event_name || 'Untitled Event');
        eventsView.classList.add('hidden');
        recipientsView.classList.remove('hidden');
    }

    function getEventColumnValue(eventItem, key) {
        if (!eventItem || !key) {
            return '-';
        }

        if (key === 'template_name') {
            return eventItem.template && eventItem.template.name ? eventItem.template.name : '-';
        }

        if (key === 'recipients_count') {
            return eventItem.recipients_count ?? 0;
        }

        const value = eventItem[key];
        return value === null || value === undefined || value === '' ? '-' : value;
    }

    function renderEventActionButtons(eventItem, eventId) {
        const actions = Array.isArray(eventActions) ? eventActions : [];

        if (!actions.length) {
            return '';
        }

        return actions.map(function (action) {
            const actionType = action.type || 'js';
            const actionName = action.name || '';
            const handler = action.handler || actionName;
            const label = action.label || actionName;
            const customClass = action.class || 'bg-slate-100 text-slate-700 border border-slate-300';
            const baseClass = 'rounded px-2 py-1 text-xs font-medium hover:opacity-90';

            if (actionType === 'delete' || actionName === 'delete') {
                return '<button type="button" data-action="delete-event" data-event-id="' + escapeHtml(eventId) + '" class="' + escapeHtml(baseClass + ' ' + customClass) + '">' + escapeHtml(label) + '</button>';
            }

            return '<button type="button" data-action="' + escapeHtml(handler) + '" data-event-id="' + escapeHtml(eventId) + '" class="' + escapeHtml(baseClass + ' ' + customClass) + '">' + escapeHtml(label) + '</button>';
        }).join('');
    }

    function initializeEventsPagination() {
        if (typeof initTable === 'function') {
            initTable('certificateEvents');
        }
    }

    function renderEventRows(events) {
        if (!Array.isArray(events) || !events.length) {
            eventsTableBody.innerHTML = '<tr><td colspan="' + eventColspan + '" class="px-3 py-4 text-center text-slate-500">No events yet.</td></tr>';
            initializeEventsPagination();
            return;
        }

        eventsTableBody.innerHTML = events.map(function (eventItem) {
            const eventId = eventItem.id;
            const cells = eventColumns.map(function (column) {
                if (column.key === 'actions') {
                    return '<td class="px-3 py-2 text-left" data-table="certificateEvents" data-column="actions">' +
                        '<div class="flex justify-start gap-2">' + renderEventActionButtons(eventItem, eventId) + '</div>' +
                    '</td>';
                }

                return '<td class="px-3 py-2 text-slate-700" data-table="certificateEvents" data-column="' + escapeHtml(column.key) + '">' + escapeHtml(getEventColumnValue(eventItem, column.key)) + '</td>';
            }).join('');

            return '<tr>' + cells + '</tr>';
        }).join('');

        initializeEventsPagination();
    }

    function applyEventFilter() {
        const keyword = String(eventFilterInput.value || '').trim().toLowerCase();
        if (!keyword) {
            renderEventRows(allEvents);
            return;
        }

        const filtered = allEvents.filter(function (eventItem) {
            const haystack = eventColumns
                .filter(function (column) { return column.key !== 'actions'; })
                .map(function (column) { return getEventColumnValue(eventItem, column.key); })
                .join(' ')
                .toLowerCase();
            return haystack.includes(keyword);
        });

        renderEventRows(filtered);
    }

    function normalizePreviewOrientation(value) {
        return value === 'portrait' ? 'portrait' : 'landscape';
    }

    function normalizePreviewPaperSize(value) {
        const allowed = ['a4', 'a5', 'letter', 'legal'];
        return allowed.includes(value) ? value : 'a4';
    }

    function getPreviewCanvasSize(paperSize, orientation) {
        const sizes = {
            a4: { width: 297, height: 210 },
            a5: { width: 210, height: 148 },
            letter: { width: 279.4, height: 215.9 },
            legal: { width: 355.6, height: 215.9 }
        };

        const page = sizes[normalizePreviewPaperSize(paperSize)] || sizes.a4;
        const normalizedOrientation = normalizePreviewOrientation(orientation);
        const longEdge = Math.max(page.width, page.height);
        const shortEdge = Math.min(page.width, page.height);
        const scale = 900 / longEdge;

        if (normalizedOrientation === 'portrait') {
            return {
                width: Math.round(shortEdge * scale),
                height: Math.round(longEdge * scale)
            };
        }

        return {
            width: Math.round(longEdge * scale),
            height: Math.round(shortEdge * scale)
        };
    }

    function applyRecipientNameTokens(html, recipientName) {
        const name = String(recipientName || '').trim();
        const safeName = name
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
        if (!name) {
            return {
                html: String(html || ''),
                replaced: false,
            };
        }

        let output = String(html || '');
        let replaced = false;

        const patterns = [
            /\{\{\s*recipient_name\s*\}\}/gi,
            /\{\{\s*name\s*\}\}/gi,
            /\[recipient_name\]/gi,
            /\{recipient_name\}/gi,
            /RECIPIENT_NAME/g,
            /Juan Dela Cruz/gi,
            /Sample Trainee/gi,
        ];

        patterns.forEach(function (pattern) {
            const next = output.replace(pattern, safeName);
            if (next !== output) {
                replaced = true;
                output = next;
            }
        });

        return {
            html: output,
            replaced: replaced,
        };
    }

    function buildRecipientPreviewContent(content, recipientName) {
        if (!content || !content.tagName) {
            return null;
        }

        const element = document.createElement(content.tagName);

        if (content.className) {
            element.className = content.className;
        }

        if (content.style) {
            element.setAttribute('style', content.style);
        }

        if (content.tagName === 'img') {
            element.src = content.src || '';
        } else {
            const classNames = String(content.className || '');
            const isRecipientNameSlot = classNames.split(/\s+/).includes('recipient-name-slot');

            if (isRecipientNameSlot) {
                const resolvedRecipientName = String(recipientName || '').trim();

                if (resolvedRecipientName !== '') {
                    // Recipient context: always use the real recipient name.
                    element.textContent = resolvedRecipientName;
                    element.dataset.nameBound = '1';
                } else {
                    // Builder/template context: keep whatever sample text admin designed.
                    element.innerHTML = content.html || '@{{ recipient_name }}';
                }
            } else {
                const tokenResult = applyRecipientNameTokens(content.html || '', recipientName);
                element.innerHTML = tokenResult.html;
                if (tokenResult.replaced) {
                    element.dataset.nameBound = '1';
                }
            }
        }

        element.removeAttribute('contenteditable');
        return element;
    }

    function renderRecipientPreview(template, recipientName, targetCanvas) {
        const canvas = targetCanvas || recipientPreviewCanvas;

        if (!template || !canvas) {
            return false;
        }

        const size = getPreviewCanvasSize(template.paper_size, template.orientation);
        canvas.style.width = size.width + 'px';
        canvas.style.height = size.height + 'px';
        canvas.dataset.orientation = normalizePreviewOrientation(template.orientation);
        canvas.dataset.paperSize = normalizePreviewPaperSize(template.paper_size);

        if (template.background_image) {
            canvas.style.backgroundImage = 'url("' + template.background_image + '")';
            canvas.style.backgroundSize = 'cover';
            canvas.style.backgroundPosition = 'center';
            canvas.style.backgroundRepeat = 'no-repeat';
        } else {
            canvas.style.backgroundImage = '';
            canvas.style.backgroundSize = '';
            canvas.style.backgroundPosition = '';
            canvas.style.backgroundRepeat = '';
        }

        canvas.innerHTML = '';

        const layout = Array.isArray(template.layout_json) ? template.layout_json : [];
        let nameApplied = false;
        layout.forEach(function (item) {
            if (!item || !item.content) {
                return;
            }

            const contentEl = buildRecipientPreviewContent(item.content, recipientName);
            if (!contentEl) {
                return;
            }

            if (contentEl.dataset && contentEl.dataset.nameBound === '1') {
                nameApplied = true;
            }

            const wrapper = document.createElement('div');
            wrapper.className = 'canvas-item';
            wrapper.style.position = 'absolute';

            if (item.wrapperStyle) {
                wrapper.setAttribute('style', item.wrapperStyle);
            }

            if (contentEl.classList && contentEl.classList.contains('recipient-name-slot')) {
                wrapper.style.left = '0px';
                wrapper.style.width = '100%';
                contentEl.style.display = 'block';
                contentEl.style.width = '100%';
                contentEl.style.textAlign = 'center';
            }

            wrapper.appendChild(contentEl);
            canvas.appendChild(wrapper);
        });

        // Fallback for templates without explicit recipient placeholder tokens.
        if (!nameApplied && String(recipientName || '').trim() !== '') {
            const fallbackName = document.createElement('div');
            fallbackName.style.position = 'absolute';
            fallbackName.style.left = '12%';
            fallbackName.style.width = '76%';
            fallbackName.style.top = '48%';
            fallbackName.style.textAlign = 'center';
            fallbackName.style.fontFamily = '"Great Vibes", "Dancing Script", cursive';
            fallbackName.style.fontSize = '44px';
            fallbackName.style.fontWeight = '700';
            fallbackName.style.lineHeight = '1.1';
            fallbackName.style.color = '#1f2937';
            fallbackName.style.textShadow = '0 1px 0 rgba(255,255,255,0.55)';
            fallbackName.style.pointerEvents = 'none';
            fallbackName.textContent = String(recipientName || '').trim();
            canvas.appendChild(fallbackName);
        }

        return layout.length > 0 || !!template.background_image;
    }

    function openRecipientPreview(templateId, recipientName) {
        const templates = window.certificateTemplatePreviewData || {};
        const template = templates[String(templateId)] || templates[templateId] || null;
        const safeRecipientName = recipientName || 'Recipient';

        recipientPreviewTitle.textContent = 'Certificate Preview';
        recipientPreviewSubTitle.textContent = 'Recipient: ' + safeRecipientName;

        const hasPreview = renderRecipientPreview(template, safeRecipientName);
        recipientPreviewEmpty.classList.toggle('hidden', hasPreview);

        recipientPreviewModal.classList.remove('hidden');
        recipientPreviewModal.classList.add('flex');
    }

    function closeRecipientPreview() {
        recipientPreviewModal.classList.add('hidden');
        recipientPreviewModal.classList.remove('flex');
    }

    function mapPaperSizeForPrint(value) {
        if (value === 'a5') return 'A5';
        if (value === 'letter') return 'Letter';
        if (value === 'legal') return 'Legal';
        return 'A4';
    }

    function printAllCertificateCanvases(canvasItems) {
        if (!Array.isArray(canvasItems) || canvasItems.length === 0) {
            return;
        }

        const firstCanvas = canvasItems[0];
        const firstOrientation = (firstCanvas.dataset.orientation === 'portrait') ? 'portrait' : 'landscape';
        const firstPaper = mapPaperSizeForPrint(firstCanvas.dataset.paperSize || 'a4');

        const pageDims = (typeof getPageDimensionsPx === 'function')
            ? getPageDimensionsPx(firstPaper, firstOrientation)
            : { width: 1123, height: 794 };

        const iframe = document.createElement('iframe');
        iframe.style.position = 'fixed';
        iframe.style.right = '0';
        iframe.style.bottom = '0';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = '0';
        iframe.setAttribute('aria-hidden', 'true');
        document.body.appendChild(iframe);

        const doc = iframe.contentWindow.document;
        doc.open();
        doc.write('<!doctype html><html><head><meta charset="utf-8"><title>Print Certificates</title><style>' +
            '@page{size:' + firstPaper + ' ' + firstOrientation + ';margin:0;}' +
            'html,body{margin:0;padding:0;background:#fff;}' +
            '.print-page{position:relative;overflow:hidden;background:#fff;page-break-after:always;break-after:page;}' +
            '.print-page:last-child{page-break-after:auto;break-after:auto;}' +
            '</style></head><body></body></html>');
        doc.close();

        const body = doc.body;
        canvasItems.forEach(function (canvas) {
            const clone = canvas.cloneNode(true);
            if (typeof cleanupPreviewNode === 'function') {
                cleanupPreviewNode(clone);
            }

            const bgValue = clone.style.backgroundImage || '';
            const bgUrl = (typeof extractBackgroundImageUrl === 'function')
                ? extractBackgroundImageUrl(bgValue)
                : null;

            if (bgUrl) {
                const bgImg = doc.createElement('img');
                bgImg.src = bgUrl;
                bgImg.alt = '';
                bgImg.setAttribute('aria-hidden', 'true');
                bgImg.style.position = 'absolute';
                bgImg.style.inset = '0';
                bgImg.style.width = '100%';
                bgImg.style.height = '100%';
                bgImg.style.objectFit = 'cover';
                bgImg.style.objectPosition = 'center';
                bgImg.style.zIndex = '0';
                bgImg.style.pointerEvents = 'none';

                clone.style.backgroundImage = '';
                clone.prepend(bgImg);

                clone.querySelectorAll(':scope > *:not(img)').forEach(function (child) {
                    if (!child.style.position) {
                        child.style.position = 'relative';
                    }
                    if (!child.style.zIndex) {
                        child.style.zIndex = '1';
                    }
                });
            }

            const sourceWidth = parseFloat(clone.style.width) || canvas.offsetWidth || 900;
            const sourceHeight = parseFloat(clone.style.height) || canvas.offsetHeight || 650;
            const scale = Math.min(pageDims.width / sourceWidth, pageDims.height / sourceHeight);

            const page = doc.createElement('div');
            page.className = 'print-page';
            page.style.width = pageDims.width + 'px';
            page.style.height = pageDims.height + 'px';

            clone.style.transformOrigin = 'top left';
            clone.style.transform = 'scale(' + scale + ')';
            clone.style.width = sourceWidth + 'px';
            clone.style.height = sourceHeight + 'px';

            page.appendChild(clone);
            body.appendChild(page);
        });

        const printWindow = iframe.contentWindow;
        printWindow.onafterprint = function () {
            setTimeout(function () {
                iframe.remove();
            }, 200);
        };

        waitForImagesToLoad(doc, 3500).finally(function () {
            setTimeout(function () {
                printWindow.focus();
                printWindow.print();
            }, 100);
        });
    }

    async function printAllCertificatesForSelectedEvent() {
        if (!selectedEventId) {
            showManageFeedback('Select event first.', 'error');
            return;
        }

        const payload = await fetchEventDetails(selectedEventId);
        const eventData = payload.data || {};
        const recipients = Array.isArray(eventData.recipients) ? eventData.recipients : [];

        if (recipients.length === 0) {
            showManageFeedback('No recipients found for this event.', 'error');
            return;
        }

        const templates = window.certificateTemplatePreviewData || {};
        const printableCanvases = [];

        recipients.forEach(function (recipient) {
            const templateId = recipient.recipient_template_id || eventData.template_id || null;
            if (!templateId) {
                return;
            }

            const template = templates[String(templateId)] || templates[templateId] || null;
            if (!template) {
                return;
            }

            const canvas = document.createElement('div');
            canvas.className = 'relative border border-slate-300 bg-white shadow-sm';

            const rendered = renderRecipientPreview(template, recipient.recipient_name || 'Recipient', canvas);
            if (rendered) {
                printableCanvases.push(canvas);
            }
        });

        if (printableCanvases.length === 0) {
            showManageFeedback('No printable certificates are available for this event.', 'error');
            return;
        }

        printAllCertificateCanvases(printableCanvases);
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function initializeRecipientPagination() {
        if (typeof initTable === 'function') {
            initTable('certificateRecipients');
        }
    }

    function renderRecipientRows(recipients, eventId, eventTemplateId) {
        if (!Array.isArray(recipients) || !recipients.length) {
            recipientsTableBody.innerHTML = '<tr><td colspan="' + recipientColspan + '" class="px-3 py-4 text-center text-slate-500">No recipients yet.</td></tr>';
            initializeRecipientPagination();
            return;
        }

        recipientsTableBody.innerHTML = recipients.map(function (recipient) {
            const status = recipient.status || 'pending';

            return '<tr>' +
                '<td class="px-3 py-2 text-slate-800" data-table="certificateRecipients" data-column="recipient_name">' + escapeHtml(recipient.recipient_name || '-') + '</td>' +
                '<td class="px-3 py-2 text-slate-600" data-table="certificateRecipients" data-column="certificate_title">' + escapeHtml(recipient.certificate_title || '-') + '</td>' +
                '<td class="px-3 py-2 text-slate-600" data-table="certificateRecipients" data-column="award_title">' + escapeHtml(recipient.award_title || '-') + '</td>' +
                '<td class="px-3 py-2 text-slate-600" data-table="certificateRecipients" data-column="activity_name">' + escapeHtml(recipient.activity_name || '-') + '</td>' +
                '<td class="px-3 py-2 text-slate-600" data-table="certificateRecipients" data-column="recognition_reason">' + escapeHtml(recipient.recognition_reason || '-') + '</td>' +
                '<td class="px-3 py-2 text-slate-600" data-table="certificateRecipients" data-column="issued_date">' + escapeHtml(recipient.issued_date || '-') + '</td>' +
                '<td class="px-3 py-2" data-table="certificateRecipients" data-column="status">' +
                    '<select id="recipient-status-' + recipient.id + '" class="rounded border border-slate-300 bg-white px-2 py-1 text-xs">' +
                        '<option value="pending"' + (status === 'pending' ? ' selected' : '') + '>Pending</option>' +
                        '<option value="generated"' + (status === 'generated' ? ' selected' : '') + '>Generated</option>' +
                        '<option value="failed"' + (status === 'failed' ? ' selected' : '') + '>Failed</option>' +
                    '</select>' +
                '</td>' +
                '<td class="px-3 py-2 text-right" data-table="certificateRecipients" data-column="actions">' +
                    '<button type="button" data-action="preview-recipient" data-event-id="' + eventId + '" data-recipient-id="' + recipient.id + '" data-recipient-name="' + escapeHtml(recipient.recipient_name || '') + '" data-template-id="' + escapeHtml(recipient.recipient_template_id || eventTemplateId || '') + '" class="mr-2 rounded border border-slate-300 bg-slate-50 px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-100">Preview</button>' +
                    '<button type="button" data-action="mark-winner" data-event-id="' + eventId + '" data-recipient-id="' + recipient.id + '" class="mr-2 rounded border border-indigo-300 bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 hover:bg-indigo-100">Winner</button>' +
                    '<button type="button" data-action="save-recipient-status" data-event-id="' + eventId + '" data-recipient-id="' + recipient.id + '" class="mr-2 rounded border border-amber-300 bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700 hover:bg-amber-100">Save</button>' +
                    '<button type="button" data-action="delete-recipient" data-event-id="' + eventId + '" data-recipient-id="' + recipient.id + '" class="rounded border border-rose-300 bg-rose-50 px-2 py-1 text-xs font-medium text-rose-700 hover:bg-rose-100">Delete</button>' +
                '</td>' +
            '</tr>';
        }).join('');

        initializeRecipientPagination();
    }

    function renderRecipientPlaceholder(message, tone) {
        const textClass = tone === 'error' ? 'text-rose-600' : 'text-slate-500';
        recipientsTableBody.innerHTML = '<tr><td colspan="' + recipientColspan + '" class="px-3 py-4 text-center ' + textClass + '">' + escapeHtml(message) + '</td></tr>';
        initializeRecipientPagination();
    }

    async function fetchEvents(preferredEventId) {
        eventsTableBody.innerHTML = '<tr><td colspan="' + eventColspan + '" class="px-3 py-4 text-center text-slate-500">Loading events...</td></tr>';

        try {
            const response = await fetch(endpoints.index, {
                method: 'GET',
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                throw new Error('Unable to load events.');
            }

            const payload = await response.json();
            allEvents = payload.data || [];
            applyEventFilter();

            if (preferredEventId) {
                selectedEventId = Number(preferredEventId);
            }

            if (selectedEventId && !allEvents.some(function (item) { return Number(item.id) === Number(selectedEventId); })) {
                selectedEventId = null;
                showEventsView();
            }

            if (selectedEventId && !recipientsView.classList.contains('hidden')) {
                await refreshRecipients(selectedEventId);
            }
        } catch (error) {
            eventsTableBody.innerHTML = '<tr><td colspan="' + eventColspan + '" class="px-3 py-4 text-center text-rose-600">Failed to load events.</td></tr>';
            showFeedback(error.message || 'Failed to load events.', 'error');
        }
    }

    async function fetchEventDetails(eventId) {
        const response = await fetch(endpoints.index + '/' + eventId, {
            method: 'GET',
            headers: { 'Accept': 'application/json' }
        });

        if (!response.ok) {
            const errorPayload = await response.json().catch(function () { return {}; });
            throw new Error(errorPayload.message || 'Unable to load recipients.');
        }

        return response.json();
    }

    async function createEvent(formData) {
        const payload = Object.fromEntries(formData.entries());

        const response = await fetch(endpoints.store, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify(payload),
        });

        if (!response.ok) {
            const errorPayload = await response.json().catch(function () { return {}; });
            const firstValidationError = errorPayload.errors
                ? Object.values(errorPayload.errors)[0]?.[0]
                : null;
            throw new Error(firstValidationError || errorPayload.message || 'Event creation failed.');
        }

        return response.json();
    }

    async function updateEvent(eventId, formData) {
        const payload = Object.fromEntries(formData.entries());

        const response = await fetch(endpoints.index + '/' + eventId, {
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify(payload),
        });

        if (!response.ok) {
            const errorPayload = await response.json().catch(function () { return {}; });
            const firstValidationError = errorPayload.errors
                ? Object.values(errorPayload.errors)[0]?.[0]
                : null;
            throw new Error(firstValidationError || errorPayload.message || 'Event update failed.');
        }

        return response.json();
    }

    async function deleteEvent(url) {
        const response = await fetch(url, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
        });

        if (!response.ok) {
            const errorPayload = await response.json().catch(function () { return {}; });
            throw new Error(errorPayload.message || 'Delete failed.');
        }
    }

    async function createRecipient(eventId, payload) {
        const response = await fetch(endpoints.index + '/' + eventId + '/recipients', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify(payload),
        });

        if (!response.ok) {
            const errorPayload = await response.json().catch(function () { return {}; });
            const firstValidationError = errorPayload.errors
                ? Object.values(errorPayload.errors)[0]?.[0]
                : null;
            throw new Error(firstValidationError || errorPayload.message || 'Failed to add recipient.');
        }

        return response.json();
    }

    async function addAwardOption(eventId, awardTitle) {
        const response = await fetch(eventScopedUrl(endpoints.awardsStoreTemplate, eventId), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({ award_title: awardTitle }),
        });

        if (!response.ok) {
            const errorPayload = await response.json().catch(function () { return {}; });
            const firstValidationError = errorPayload.errors
                ? Object.values(errorPayload.errors)[0]?.[0]
                : null;
            throw new Error(firstValidationError || errorPayload.message || 'Failed to add award option.');
        }

        return response.json();
    }

    async function updateRecipientStatus(eventId, recipientId, status) {
        const response = await fetch(endpoints.index + '/' + eventId + '/recipients/' + recipientId, {
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({ status: status }),
        });

        if (!response.ok) {
            const errorPayload = await response.json().catch(function () { return {}; });
            throw new Error(errorPayload.message || 'Failed to update recipient status.');
        }
    }

    async function updateRecipientFields(eventId, recipientId, payload) {
        const response = await fetch(endpoints.index + '/' + eventId + '/recipients/' + recipientId, {
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify(payload),
        });

        if (!response.ok) {
            const errorPayload = await response.json().catch(function () { return {}; });
            throw new Error(errorPayload.message || 'Failed to update recipient fields.');
        }
    }

    async function deleteRecipient(eventId, recipientId) {
        const response = await fetch(endpoints.index + '/' + eventId + '/recipients/' + recipientId, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
        });

        if (!response.ok) {
            const errorPayload = await response.json().catch(function () { return {}; });
            throw new Error(errorPayload.message || 'Failed to delete recipient.');
        }
    }

    async function refreshRecipients(eventId) {
        if (!eventId) {
            renderRecipientPlaceholder('Select an event.', 'success');
            return;
        }

        const payload = await fetchEventDetails(eventId);
        const eventData = payload.data || {};
        selectedEventId = Number(eventData.id || eventId);
        syncRecipientFormEventId();
        participantPool = Array.isArray(eventData.recipients) ? eventData.recipients : [];
        eventActivityRoleRules = sanitizeActivityRoleRules(eventData?.metadata?.activity_role_rules || []);

        const relationActivities = Array.isArray(eventData.event_types)
            ? eventData.event_types.map(function (item) { return item && item.name ? item.name : ''; })
            : [];
        const metadataActivities = Array.isArray(eventData?.metadata?.event_types)
            ? eventData.metadata.event_types
            : [];

        eventActivityOptions = relationActivities.concat(metadataActivities)
            .map(function (item) { return normalizeText(item); })
            .filter(function (item, idx, arr) {
                return item !== '' && arr.findIndex(function (x) { return x.toLowerCase() === item.toLowerCase(); }) === idx;
            });

        renderActivityFilterOptions();
        renderParticipantOptions();
        syncAwardOptionsFromEvent(eventData);
        renderRecipientRows(eventData.recipients || [], eventId, eventData.template_id || null);
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Creating...';
        }

        try {
            const result = editingEventId
                ? await updateEvent(editingEventId, new FormData(form))
                : await createEvent(new FormData(form));

            if (editingEventId) {
                showFeedback('Event updated successfully.', 'success');
            } else {
                showFeedback('Event created successfully.', 'success');
            }

            setEventModalToCreate();
            if (typeof closeModal === 'function') {
                closeModal('certificateEventCreateModal');
            }
            await fetchEvents(result?.data?.id || selectedEventId);
        } catch (error) {
            showFeedback(error.message || 'Event creation failed.', 'error');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Create Event';
            }
        }
    });

    addRecipientForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        const submitBtn = addRecipientForm.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Adding...';
        }

        const eventId = addRecipientForm.querySelector('input[name="event_id"]').value;
        const recipientName = addRecipientForm.querySelector('select[name="recipient_name"]').value;
        const certificateTitle = addRecipientForm.querySelector('input[name="certificate_title"]').value;
        const awardTitle = addRecipientForm.querySelector('[name="award_title"]').value;
        const selectedRecipientOption = recipientNameSelect.options[recipientNameSelect.selectedIndex];
        const selectedActivity = normalizeText(addRecipientForm.querySelector('select[name="activity_name"]').value);
        const inferredActivity = selectedRecipientOption ? normalizeText(selectedRecipientOption.dataset.activity || '') : '';
        const activityName = selectedActivity || inferredActivity;
        const recognitionReason = addRecipientForm.querySelector('input[name="recognition_reason"]').value;

        if (isAutoGeneratedAward(awardTitle)) {
            showManageFeedback('Participant/Participation/Attendee/Attendance are auto-generated from attendance setup.', 'error');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Add Recipient';
            }
            return;
        }

        try {
            await createRecipient(eventId, {
                recipient_name: recipientName,
                certificate_title: certificateTitle || null,
                award_title: awardTitle || null,
                activity_name: activityName || null,
                recognition_reason: recognitionReason || null,
            });

            let autoGeneratedCount = 0;
            if (eventId && activityName) {
                autoGeneratedCount = await maybeAutoGenerateParticipationRecipients(eventId, activityName, recipientName, awardTitle);
            }

            const successMessage = autoGeneratedCount > 0
                ? ('Recipient added successfully. ' + autoGeneratedCount + ' participation certificate(s) auto-generated.')
                : 'Recipient added successfully.';

            showManageFeedback(successMessage, 'success');
            addRecipientForm.querySelector('select[name="recipient_name"]').value = '';
            addRecipientForm.querySelector('input[name="certificate_title"]').value = '';
            addRecipientForm.querySelector('[name="award_title"]').value = '';
            addRecipientForm.querySelector('select[name="activity_name"]').value = '';
            addRecipientForm.querySelector('input[name="recognition_reason"]').value = '';
            await refreshRecipients(eventId);
            await fetchEvents(eventId);
            if (typeof closeModal === 'function') {
                closeModal('certificateRecipientFormModal');
            }
        } catch (error) {
            showManageFeedback(error.message || 'Failed to add recipient.', 'error');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Add Recipient';
            }
        }
    });

    eventFilterInput.addEventListener('input', applyEventFilter);

    if (openEventModalBtn) {
        openEventModalBtn.addEventListener('click', function () {
            setEventModalToCreate();
            if (typeof openModal === 'function') {
                openModal('certificateEventCreateModal');
            }
        });
    }

    if (eventsColumnsBtn) {
        eventsColumnsBtn.addEventListener('click', function () {
            if (typeof openColumnSettings === 'function') {
                openColumnSettings('certificateEvents');
                return;
            }

            showFeedback('Columns control is not available.', 'error');
        });
    }

    if (recipientsColumnsBtn) {
        recipientsColumnsBtn.addEventListener('click', function () {
            if (typeof openColumnSettings === 'function') {
                openColumnSettings('certificateRecipients');
                return;
            }

            showManageFeedback('Columns control is not available.', 'error');
        });
    }

    printAllCertificateBtn.addEventListener('click', async function () {
        try {
            await printAllCertificatesForSelectedEvent();
        } catch (error) {
            showManageFeedback(error.message || 'Failed to print certificates.', 'error');
        }
    });

    backToEventsBtn.addEventListener('click', function () {
        showEventsView();
    });

    openRecipientCreateModalBtn.addEventListener('click', function () {
        if (!selectedEventId) {
            showManageFeedback('Select event first.', 'error');
            return;
        }

        addRecipientForm.reset();
        renderActivityFilterOptions();
        renderParticipantOptions();
        syncRecipientFormEventId();
        if (typeof openModal === 'function') {
            openModal('certificateRecipientFormModal');
        }
    });

    recipientActivityFilter.addEventListener('change', function () {
        renderParticipantOptions();
    });

    addAwardOptionBtn.addEventListener('click', function () {
        if (!selectedEventId) {
            showManageFeedback('Select event first.', 'error');
            return;
        }

        openAwardOptionModal();
    });

    closeAwardOptionModalBtn.addEventListener('click', closeAwardOptionModal);
    cancelAwardOptionModalBtn.addEventListener('click', closeAwardOptionModal);

    awardOptionForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        const eventId = addRecipientForm.querySelector('input[name="event_id"]').value;
        const modalInput = awardOptionForm.querySelector('input[name="award_title"]');
        const candidate = normalizeAwardOption(modalInput ? modalInput.value : '');

        if (!eventId) {
            showManageFeedback('Select an event first.', 'error');
            return;
        }

        if (!candidate) {
            showManageFeedback('Type an award title first.', 'error');
            return;
        }

        if (isAutoGeneratedAward(candidate)) {
            showManageFeedback('Participant/Participation/Attendee/Attendance are auto-generated from attendance setup and cannot be added manually.', 'error');
            return;
        }

        if (activeAwardOptions.map(function (item) { return item.toLowerCase(); }).includes(candidate.toLowerCase())) {
            showManageFeedback('Award option already exists.', 'success');
            const awardInput = addRecipientForm.querySelector('[name="award_title"]');
            if (awardInput) {
                awardInput.value = candidate;
            }
            closeAwardOptionModal();
            return;
        }

        try {
            await addAwardOption(eventId, candidate);
            showManageFeedback('Award option saved.', 'success');
            await refreshRecipients(eventId);

            const awardInput = addRecipientForm.querySelector('[name="award_title"]');
            if (awardInput) {
                awardInput.value = candidate;
            }
            closeAwardOptionModal();
        } catch (error) {
            showManageFeedback(error.message || 'Failed to save award option.', 'error');
        }
    });

    cancelRecipientPreviewBtn.addEventListener('click', closeRecipientPreview);
    printRecipientPreviewBtn.addEventListener('click', function () {
        if (typeof window.printCertificatePreview === 'function') {
            window.printCertificatePreview(recipientPreviewCanvas);
        }
    });

    eventsTableBody.addEventListener('click', async function (event) {
        const target = event.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }

        const eventId = Number(target.dataset.eventId || 0);
        if (!eventId) {
            return;
        }

        const eventItem = allEvents.find(function (row) { return Number(row.id) === eventId; });
        if (!eventItem) {
            showFeedback('Event not found.', 'error');
            return;
        }

        if (target.dataset.action === 'manage-recipients') {
            try {
                showRecipientsView(eventItem);
                await refreshRecipients(eventId);
            } catch (error) {
                showFeedback(error.message || 'Failed to load recipients.', 'error');
            }
            return;
        }

        if (target.dataset.action === 'edit-event') {
            setEventModalToEdit(eventItem);
            if (typeof openModal === 'function') {
                openModal('certificateEventCreateModal');
            }
            return;
        }

        if (target.dataset.action === 'delete-event') {
            if (!confirm('Delete this certificate event?')) {
                return;
            }

            try {
                await deleteEvent(endpoints.index + '/' + eventItem.id);
                showFeedback('Event deleted successfully.', 'success');
                if (selectedEventId === eventId) {
                    selectedEventId = null;
                    showEventsView();
                }
                await fetchEvents();
            } catch (error) {
                showFeedback(error.message || 'Delete failed.', 'error');
            }
        }
    });

    recipientsTableBody.addEventListener('click', async function (event) {
        const target = event.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }

        const eventId = target.dataset.eventId || addRecipientForm.querySelector('input[name="event_id"]').value;
        const recipientId = target.dataset.recipientId || '';

        if (target.dataset.action === 'preview-recipient') {
            const templateId = target.dataset.templateId || '';
            const recipientName = target.dataset.recipientName || '';

            if (!templateId) {
                showManageFeedback('No template assigned for this recipient yet.', 'error');
                return;
            }

            openRecipientPreview(templateId, recipientName);
            return;
        }

        if (target.dataset.action === 'save-recipient-status') {
            const select = document.getElementById('recipient-status-' + recipientId);
            const status = select ? select.value : 'pending';

            try {
                await updateRecipientStatus(eventId, recipientId, status);
                showManageFeedback('Recipient status updated.', 'success');
                await refreshRecipients(eventId);
                await fetchEvents(eventId);
            } catch (error) {
                showManageFeedback(error.message || 'Failed to update status.', 'error');
            }

            return;
        }

        if (target.dataset.action === 'mark-winner') {
            try {
                await updateRecipientFields(eventId, recipientId, {
                    award_title: 'Winner',
                    status: 'pending',
                });
                showManageFeedback('Recipient marked as winner.', 'success');
                await refreshRecipients(eventId);
            } catch (error) {
                showManageFeedback(error.message || 'Failed to mark recipient as winner.', 'error');
            }

            return;
        }

        if (target.dataset.action === 'delete-recipient') {
            if (!confirm('Delete this recipient?')) {
                return;
            }

            try {
                await deleteRecipient(eventId, recipientId);
                showManageFeedback('Recipient deleted.', 'success');
                await refreshRecipients(eventId);
                await fetchEvents(eventId);
            } catch (error) {
                showManageFeedback(error.message || 'Failed to delete recipient.', 'error');
            }
        }
    });

    fetchEvents();
})();
</script>

<script src="{{ asset('js/certificate/print.js') }}"></script>

    </div>

</div>

@endsection
