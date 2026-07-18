{{-- Daily | Summary tab strip. $active is 'daily' or 'summary'. --}}
@php($tabs = [
    'daily' => ['label' => 'Daily', 'url' => route('teacher.attendance.index')],
    'summary' => ['label' => 'Summary', 'url' => route('teacher.attendance.summary')],
])

<div class="border-b border-slate-200">
    <nav class="flex gap-6">
        @foreach ($tabs as $key => $tab)
            <a href="{{ $tab['url'] }}"
                class="-mb-px border-b-2 px-1 pb-3 text-sm font-medium transition
                    {{ $active === $key
                        ? 'border-slate-800 text-slate-800'
                        : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>
</div>
