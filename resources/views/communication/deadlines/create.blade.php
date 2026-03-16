

@extends('communication.layout')

@section('communication-content')
<div class="max-w-5xl mx-auto">
    <div class="mb-8">
        <h2 class="text-2xl font-semibold text-gray-900 flex items-center gap-2">
            <span class="w-1.5 h-6 bg-indigo-600 rounded-full"></span>
            Create Deadline
        </h2>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl p-8 shadow-sm">
        @include('communication.deadlines._form')
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const assignBtn = document.getElementById('assignToggleBtn');
    const assignSection = document.getElementById('assignSection');
    const assignIcon = document.getElementById('assignIcon');

    if (assignBtn && assignSection) {
        assignBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            const isHidden = assignSection.classList.contains('hidden');
            
            if (isHidden) {
                assignSection.classList.remove('hidden');
                assignIcon.style.transform = 'rotate(180deg)';
            } else {
                assignSection.classList.add('hidden');
                assignIcon.style.transform = 'rotate(0deg)';
            }
        });

        document.addEventListener('click', function (e) {
            if (!assignSection.contains(e.target) && !assignBtn.contains(e.target)) {
                assignSection.classList.add('hidden');
                assignIcon.style.transform = 'rotate(0deg)';
            }
        });
    }

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
</style>
@endsection