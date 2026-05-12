{{-- resources/views/communication/announcements/index.blade.php --}}
@extends('communication.layout')

@section('communication-content')

<div class="flex justify-between items-center mb-6">
    <h2 class="text-xl font-semibold text-gray-800">
        Announcements
    </h2>

    <button onclick="openCreateModal()"
        class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">
        + New Announcement
    </button>
</div>

@include('communication.announcements.partials.table')

@include('communication.announcements.partials.create-modal')
@include('communication.announcements.partials.edit-modal')
@include('communication.announcements.partials.view-modal')

<script>
function showModal(id){
    const el = document.getElementById(id);
    if(!el) return;
    el.style.display = 'flex';
}
function hideModal(id){
    const el = document.getElementById(id);
    if(!el) return;
    el.style.display = 'none';
}

function openCreateModal(){ showModal('createAnnouncementModal'); }
function closeCreateModal(){ hideModal('createAnnouncementModal'); }
function closeEditModal(){ hideModal('editAnnouncementModal'); }
function closeViewModal(){ hideModal('viewAnnouncementModal'); }

function formatDisplay(value){
    if(!value) return '-';
    const d = new Date(value);
    if(isNaN(d.getTime())) return value;
    return d.toLocaleString();
}

// Toggle the Regular / Super Priority UI inside any form wrapper.
function applyPriorityState(scope, level){
    const buttons = scope.querySelectorAll('[data-role="priority-buttons"] [data-priority]');
    const hidden  = scope.querySelector('[data-role="priority-input"]');
    const dates   = scope.querySelector('[data-role="regular-dates"]');
    const duration= scope.querySelector('[data-role="super-duration"]');
    const isSuper = (level === 'super');

    buttons.forEach(function(btn){
        const on  = btn.dataset.onClass  || '';
        const off = btn.dataset.offClass || '';
        const active = (btn.dataset.priority === 'super') === isSuper;
        btn.className = 'px-4 py-2 text-sm ' + (active ? on : off) +
            (btn.dataset.priority === 'super' ? ' border-l' : '');
    });

    if(hidden)   hidden.value = isSuper ? 'super' : 'normal';
    if(dates)    dates.style.display    = isSuper ? 'none' : '';
    if(duration) duration.style.display = isSuper ? ''     : 'none';
}

function initPriorityToggle(scope){
    if(!scope) return;
    scope.querySelectorAll('[data-role="priority-buttons"] [data-priority]').forEach(function(btn){
        btn.addEventListener('click', function(){
            applyPriorityState(scope, btn.dataset.priority);
        });
    });
    // Set initial state from hidden input (defaults to normal).
    const hidden = scope.querySelector('[data-role="priority-input"]');
    applyPriorityState(scope, hidden && hidden.value === 'super' ? 'super' : 'regular');
}

document.addEventListener('DOMContentLoaded', function(){

    // Init priority toggles in each form that has one.
    document.querySelectorAll('#createAnnouncementModal form, #editAnnouncementForm').forEach(initPriorityToggle);

    // VIEW buttons
    document.querySelectorAll('.announcement-view-btn').forEach(function(btn){
        btn.addEventListener('click', function(){
            const d = btn.dataset;
            document.getElementById('viewTitle').textContent = d.title || '';
            document.getElementById('viewContent').textContent = d.content || '';
            document.getElementById('viewType').textContent = d.type || '-';
            document.getElementById('viewPriority').textContent =
                (d.priority === 'super') ? 'Priority' : (d.priority || 'normal');
            document.getElementById('viewPublished').textContent = formatDisplay(d.published);
            document.getElementById('viewExpires').textContent = formatDisplay(d.expires);
            document.getElementById('viewCreator').textContent = d.creator || '-';
            document.getElementById('viewStatus').textContent = d.status || '-';
            showModal('viewAnnouncementModal');
        });
    });

    // EDIT buttons
    document.querySelectorAll('.announcement-edit-btn').forEach(function(btn){
        btn.addEventListener('click', function(){
            const d = btn.dataset;
            const form = document.getElementById('editAnnouncementForm');
            form.setAttribute('action', d.url);
            document.getElementById('editTitle').value = d.title || '';
            document.getElementById('editContent').value = d.content || '';
            document.getElementById('editType').value = d.type || 'announcement';
            document.getElementById('editPublishedAt').value = d.published || '';
            document.getElementById('editExpiresAt').value = d.expires || '';
            const minsEl = document.getElementById('editSuperMinutes');
            if(minsEl) minsEl.value = d.superMinutes || 60;
            applyPriorityState(form, d.priority === 'super' ? 'super' : 'regular');
            showModal('editAnnouncementModal');
        });
    });

    // Close when clicking backdrop
    ['createAnnouncementModal','editAnnouncementModal','viewAnnouncementModal'].forEach(function(id){
        const el = document.getElementById(id);
        if(!el) return;
        el.addEventListener('click', function(e){
            if(e.target === el) el.style.display = 'none';
        });
    });
});
</script>

@endsection