@php
    use Illuminate\Support\Facades\DB;

    $user = auth()->user();
    $school = $user->school ?? null;

    $access = DB::table('account_access')
        ->where('user_id', $user->id)
        ->where('is_active', 1)
        ->first();

    $profile = null;
    $roleName = null;

    if ($access) {
        $profile = DB::table('profiles')->where('id', $access->person_id)->first();
        $role = DB::table('roles')->where('id', $access->role_id)->first();
        $roleName = $role->name ?? null;
    }

    // ✅ THEME SETTINGS (THIS WAS LOST OR MOVED)
    $identity = $user->dashboard_identity ?? [];
    $header = $identity['header'] ?? [
        'mode' => 'dark',
        'style' => 'solid',
        'color' => 'slate'
    ];

    $mode = $header['mode'];
    $style = $header['style'];
    $colorKey = $header['color'];

    $theme = config('theme.colors');
    $activeColor = $theme[$colorKey] ?? $theme['slate'];

    $manualBg = $activeColor['hex'] ?? '#1e293b';

    $bgClass = ($style === 'gradient')
        ? ($activeColor['gradient_horizontal'] ?? $activeColor['solid'])
        : $activeColor['solid'];

    $textClass = $mode === 'light'
        ? ($activeColor['text_light_mode'] ?? 'text-slate-900')
        : ($activeColor['text_dark_mode'] ?? 'text-white');

    $borderClass = $mode === 'light'
        ? ($activeColor['border_light_mode'] ?? 'border-black/10')
        : ($activeColor['border_dark_mode'] ?? 'border-white/10');

    $hoverClass = $mode === 'light'
        ? ($activeColor['hover_light_mode'] ?? 'hover:bg-black/10')
        : ($activeColor['hover_dark_mode'] ?? 'hover:bg-white/10');

    // ✅ NOTIFICATIONS
    // Real DB notifications (excluding legacy push-based enrollment rows — these
    // are now generated virtually by App\Support\EnrollmentNotifications).
    $dbNotifications = DB::table('notifications')
        ->where('notifiable_id', $user->id)
        ->where('type', '!=', \App\Notifications\EnrollmentOpenNotification::class)
        ->orderByDesc('created_at')
        ->limit(10)
        ->get()
        ->map(function ($n) {
            $d = json_decode($n->data);
            return [
                'id'           => $n->id,
                'title'        => $d->title ?? 'Notification',
                'message'      => $d->message ?? '',
                'type'         => $d->type ?? 'announcement',
                'reference_id' => $d->reference_id ?? 0,
                'term_id'      => $d->term_id ?? null,
                'read'         => !is_null($n->read_at),
                'urgent'       => false,
            ];
        })
        ->all();

    $virtualEnrollments = \App\Support\EnrollmentNotifications::forUser($user);
    $virtualBilling     = \App\Support\BillingNotifications::forUser($user);

    $notifications = collect(array_merge($virtualBilling, $virtualEnrollments, $dbNotifications));

    $notifCount = DB::table('notifications')
        ->where('notifiable_id', $user->id)
        ->where('type', '!=', \App\Notifications\EnrollmentOpenNotification::class)
        ->whereNull('read_at')
        ->count() + count($virtualEnrollments) + count($virtualBilling);
@endphp

<header class="h-24 flex items-center justify-between px-6 shadow-sm border-b z-30 {{ $bgClass }} {{ $textClass }} {{ $borderClass }}"
        style="background-color: {{ $manualBg }};">
    
    <div class="hidden md:flex flex-1 items-center justify-between">

        {{-- LEFT --}}
        @include('components.header.branding', [
            'school' => $school,
            'user' => $user
        ])

        {{-- CENTER --}}
        <div class="flex-1 max-w-xl px-8">
            @include('components.header.banner')
        </div>

        {{-- RIGHT --}}
        <div class="flex items-center gap-4">

            {{-- 🔔 Notifications --}}
            @include('components.header.notifications', [
                'notifications' => $notifications,
                'notifCount' => $notifCount,
                'hoverClass' => $hoverClass
            ])

            <div class="h-8 w-[1px] bg-current opacity-10 mx-2"></div>

            {{-- 👤 User Dropdown --}}
            @include('components.header.user-dropdown', [
                'user' => $user,
                'profile' => $profile,
                'roleName' => $roleName,
                'borderClass' => $borderClass
            ])

        </div>
    </div>

</header>