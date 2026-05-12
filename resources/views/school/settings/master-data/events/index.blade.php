@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-6">

    @include('school.settings.partials.master-data._header')

    @include('partials.tabs', [
        'tabs' => config('tabs.tabs.master_data')
    ])

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h2 class="text-xl font-bold text-slate-900">Event Form Setup</h2>
                <p class="text-sm text-slate-500">Create a fillable event form link for any school event (sports, training, seminar, pageant, program, etc.).</p>
            </div>

            <form id="eventFormSetupForm" class="grid grid-cols-1 gap-4 md:grid-cols-4">
                @csrf

                @foreach(($formSchema ?? []) as $section)
                    @foreach(($section['rows'] ?? []) as $row)
                        @foreach($row as $field)
                            @php
                                $name = $field['name'] ?? '';
                                $type = $field['type'] ?? 'text';
                                $label = $field['label'] ?? ucfirst(str_replace('_', ' ', $name));
                                $required = !empty($field['required']);
                                $placeholder = $field['placeholder'] ?? '';
                                $options = $field['options'] ?? [];
                                $rows = (int) ($field['rows'] ?? 3);
                                $colSpan = (int) ($field['col_span'] ?? 1);
                                $safeColSpan = $colSpan < 1 ? 1 : ($colSpan > 4 ? 4 : $colSpan);
                            @endphp

                            <div class="md:col-span-{{ $safeColSpan }}">
                                <label class="mb-1 block text-sm text-slate-700">{{ $label }}</label>

                                @if($type === 'tag_input')
                                    <div class="space-y-2" data-tag-input data-name="{{ $name }}">
                                        <div class="flex gap-2">
                                            <input
                                                type="text"
                                                data-tag-source
                                                @if($placeholder) placeholder="{{ $placeholder }}" @endif
                                                class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                                            >
                                            <button
                                                type="button"
                                                data-tag-add
                                                class="rounded-xl border border-indigo-300 bg-indigo-50 px-3 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-100"
                                            >
                                                Add
                                            </button>
                                        </div>

                                        <div class="min-h-10 rounded-xl border border-slate-200 bg-slate-50 px-2 py-2" data-tag-list></div>
                                        <input type="hidden" name="{{ $name }}" value="[]" data-tag-hidden>

                                        @if(!empty($field['help']))
                                            <p class="text-xs text-slate-500">{{ $field['help'] }}</p>
                                        @endif
                                    </div>
                                @elseif($type === 'textarea')
                                    <textarea
                                        name="{{ $name }}"
                                        rows="{{ $rows }}"
                                        @if($required) required @endif
                                        @if($placeholder) placeholder="{{ $placeholder }}" @endif
                                        class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                                    ></textarea>
                                @elseif($type === 'select')
                                    <select
                                        name="{{ $name }}"
                                        @if($required) required @endif
                                        class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                                    >
                                        @foreach($options as $option)
                                            <option value="{{ $option }}">{{ ucfirst($option) }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input
                                        type="{{ $type }}"
                                        name="{{ $name }}"
                                        @if($required) required @endif
                                        @if($placeholder) placeholder="{{ $placeholder }}" @endif
                                        class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                                    >
                                @endif
                            </div>
                        @endforeach
                    @endforeach
                @endforeach

                <div class="md:col-span-4">
                    <button id="eventFormSetupSubmit" type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        Create Event Form Link
                    </button>
                </div>
            </form>

            <p id="eventFormSetupFeedback" class="mt-4 hidden rounded-lg px-3 py-2 text-sm"></p>

            <div id="eventFormSetupResult" class="mt-4 hidden rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                <p class="text-sm font-semibold text-emerald-800">Share this form link:</p>
                <div class="mt-2 flex flex-col gap-2 md:flex-row">
                    <input id="eventFormLinkInput" type="text" readonly class="w-full rounded-lg border border-emerald-300 bg-white px-3 py-2 text-sm text-slate-700">
                    <button id="eventFormCopyBtn" type="button" class="rounded-lg border border-emerald-400 bg-white px-3 py-2 text-sm text-emerald-700 hover:bg-emerald-100">Copy</button>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
            <h3 class="text-base font-semibold text-slate-900">How It Works</h3>
            <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-slate-600">
                <li>Create an event form from this page.</li>
                <li>Send the generated link to students, teachers, or staff.</li>
                <li>Recipients fill the form like Google Form.</li>
                <li>Responses are stored as event recipients for certificate issuance.</li>
            </ol>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="text-base font-semibold text-slate-900">Recent Event Forms</h3>
        <div id="eventFormCards" class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
            @forelse($events as $event)
                <article class="rounded-xl border border-slate-200 bg-white p-4">
                    <h4 class="text-sm font-semibold text-slate-900">{{ $event->event_name }}</h4>
                    <p class="mt-1 text-xs text-slate-500">{{ ucfirst($event->event_type ?? 'other') }}</p>
                    <p class="mt-1 text-xs text-slate-500">
                        Categories: {{ $event->eventTypes->pluck('name')->implode(', ') ?: (implode(', ', data_get($event->metadata, 'event_types', [])) ?: '-') }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500">
                        Roles: {{ $event->eventRoles->pluck('name')->implode(', ') ?: (implode(', ', data_get($event->metadata, 'role_types', [])) ?: '-') }}
                    </p>
                    <a href="{{ $event->form_link }}" target="_blank" rel="noopener" class="mt-3 inline-block rounded-lg border border-indigo-300 bg-indigo-50 px-3 py-2 text-xs font-medium text-indigo-700 hover:bg-indigo-100">Open Form Link</a>
                </article>
            @empty
                <p class="text-sm text-slate-500">No event forms yet.</p>
            @endforelse
        </div>
    </div>
</div>

<script>
(function () {
    const form = document.getElementById('eventFormSetupForm');
    const feedback = document.getElementById('eventFormSetupFeedback');
    const result = document.getElementById('eventFormSetupResult');
    const linkInput = document.getElementById('eventFormLinkInput');
    const copyBtn = document.getElementById('eventFormCopyBtn');
    const submitBtn = document.getElementById('eventFormSetupSubmit');
    const cardsContainer = document.getElementById('eventFormCards');

    if (!form || !feedback || !result || !linkInput || !copyBtn || !submitBtn) {
        return;
    }

    function showFeedback(message, tone) {
        feedback.textContent = message;
        feedback.classList.remove('hidden', 'bg-emerald-100', 'text-emerald-800', 'bg-rose-100', 'text-rose-800');
        if (tone === 'error') {
            feedback.classList.add('bg-rose-100', 'text-rose-800');
        } else {
            feedback.classList.add('bg-emerald-100', 'text-emerald-800');
        }
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function prependCard(item, formLink) {
        if (!cardsContainer) {
            return;
        }

        const sports = Array.isArray(item.event_types) && item.event_types.length
            ? item.event_types.map(function (row) { return row.name; }).join(', ')
            : (Array.isArray(item.metadata?.event_types) ? item.metadata.event_types.join(', ') : '-');

        const roles = Array.isArray(item.event_roles) && item.event_roles.length
            ? item.event_roles.map(function (row) { return row.name; }).join(', ')
            : (Array.isArray(item.metadata?.role_types) ? item.metadata.role_types.join(', ') : '-');

        const cardHtml = '' +
            '<article class="rounded-xl border border-slate-200 bg-white p-4">' +
                '<h4 class="text-sm font-semibold text-slate-900">' + escapeHtml(item.event_name) + '</h4>' +
                '<p class="mt-1 text-xs text-slate-500">' + escapeHtml((item.event_type || 'other').charAt(0).toUpperCase() + (item.event_type || 'other').slice(1)) + '</p>' +
                '<p class="mt-1 text-xs text-slate-500">Categories: ' + escapeHtml(sports) + '</p>' +
                '<p class="mt-1 text-xs text-slate-500">Roles: ' + escapeHtml(roles) + '</p>' +
                '<a href="' + escapeHtml(formLink) + '" target="_blank" rel="noopener" class="mt-3 inline-block rounded-lg border border-indigo-300 bg-indigo-50 px-3 py-2 text-xs font-medium text-indigo-700 hover:bg-indigo-100">Open Form Link</a>' +
            '</article>';

        if (cardsContainer.querySelector('p.text-sm.text-slate-500')) {
            cardsContainer.innerHTML = cardHtml;
            return;
        }

        cardsContainer.insertAdjacentHTML('afterbegin', cardHtml);
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        const payload = Object.fromEntries(new FormData(form).entries());

        form.querySelectorAll('[data-tag-input]').forEach(function (root) {
            const hidden = root.querySelector('[data-tag-hidden]');
            const name = root.getAttribute('data-name');
            if (hidden && name) {
                payload[name] = hidden.value;
            }
        });

        submitBtn.disabled = true;
        submitBtn.textContent = 'Creating...';

        try {
            const response = await fetch(@json(route('school.settings.master-data.events.store')), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json().catch(function () { return {}; });

            if (!response.ok) {
                const firstValidationError = data.errors ? Object.values(data.errors)[0]?.[0] : null;
                throw new Error(firstValidationError || data.message || 'Failed to create event form.');
            }

            result.classList.remove('hidden');
            linkInput.value = data.form_link || '';
            showFeedback(data.message || 'Event form created.', 'success');
            prependCard(data.data || {}, data.form_link || '');
            form.reset();
        } catch (error) {
            showFeedback(error.message || 'Failed to create event form.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Create Event Form Link';
        }
    });

    copyBtn.addEventListener('click', async function () {
        if (!linkInput.value) {
            return;
        }

        try {
            await navigator.clipboard.writeText(linkInput.value);
            showFeedback('Form link copied to clipboard.', 'success');
        } catch (error) {
            showFeedback('Copy failed. Please copy the link manually.', 'error');
        }
    });

    function setupTagInput(root) {
        const source = root.querySelector('[data-tag-source]');
        const addBtn = root.querySelector('[data-tag-add]');
        const list = root.querySelector('[data-tag-list]');
        const hidden = root.querySelector('[data-tag-hidden]');

        if (!source || !addBtn || !list || !hidden) {
            return;
        }

        let items = [];

        function syncHidden() {
            hidden.value = JSON.stringify(items);
        }

        function renderItems() {
            if (!items.length) {
                list.innerHTML = '<p class="px-1 py-1 text-xs text-slate-400">No items added yet.</p>';
                syncHidden();
                return;
            }

            list.innerHTML = items.map(function (item, index) {
                return '' +
                    '<span class="mb-1 mr-1 inline-flex items-center gap-1 rounded-full border border-indigo-200 bg-indigo-50 px-2 py-1 text-xs text-indigo-700">' +
                        escapeHtml(item) +
                        '<button type="button" data-tag-remove="' + index + '" class="font-bold text-indigo-500 hover:text-indigo-800">x</button>' +
                    '</span>';
            }).join('');

            syncHidden();
        }

        function addItem() {
            const value = source.value.trim();
            if (!value) {
                return;
            }

            if (!items.includes(value)) {
                items.push(value);
                renderItems();
            }

            source.value = '';
            source.focus();
        }

        addBtn.addEventListener('click', addItem);

        source.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                addItem();
            }
        });

        list.addEventListener('click', function (event) {
            const target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }

            const index = Number(target.getAttribute('data-tag-remove'));
            if (Number.isNaN(index)) {
                return;
            }

            items.splice(index, 1);
            renderItems();
        });

        renderItems();
    }

    form.querySelectorAll('[data-tag-input]').forEach(setupTagInput);
})();
</script>
@endsection
