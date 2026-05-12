<div class="space-y-4">

    <!-- Title & Subtitle -->
    <div>
        <h1 class="text-2xl font-black text-slate-800 dark:text-white">
            School Settings
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">
            Manage Your School With Ease
        </p>
    </div>

    <!-- Settings Tabs -->
    <div class="mt-2">
        @php
            $tabs = config('tabs.tabs.school_settings_tabs');
        @endphp

        @include('components.tabs.horizontal-tab', ['tabs' => $tabs])
    </div>

</div>