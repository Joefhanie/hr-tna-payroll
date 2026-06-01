@props([
    'showGlobalAlerts' => false,
])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $themeSettings = \App\Models\CompanySetting::current();
        $brandPalette = $themeSettings->brand_palette;
        $faviconUrl = $themeSettings && $themeSettings->logo_path
            ? route('media.file', ['path' => ltrim($themeSettings->logo_path, '/')])
            : asset('favicon.ico');
    @endphp
    <title>{{ $title ?? 'HR System' }}</title>
    <link rel="icon" href="{{ $faviconUrl }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --brand-primary: {{ $brandPalette['primary'] }};
            --brand-primary-hover: {{ $brandPalette['primary_hover'] }};
            --brand-primary-soft: {{ $brandPalette['primary_soft'] }};
            --brand-primary-ring: {{ $brandPalette['primary_ring'] }};
            --brand-secondary: {{ $brandPalette['secondary'] }};
            --brand-secondary-hover: {{ $brandPalette['secondary_hover'] }};
            --brand-secondary-soft: {{ $brandPalette['secondary_soft'] }};
            --brand-secondary-ring: {{ $brandPalette['secondary_ring'] }};
            --brand-accent: {{ $brandPalette['accent'] }};
            --brand-accent-hover: {{ $brandPalette['accent_hover'] }};
            --brand-accent-soft: {{ $brandPalette['accent_soft'] }};
            --brand-surface-tint: {{ $brandPalette['surface_tint'] }};
            /* Compute accessible text color for primary based on contrast ratio */
            <?php
                // Helpers to compute contrast ratio (WCAG)
                $hex2rgb = function($hex) {
                    $hex = ltrim($hex, '#');
                    if (strlen($hex) === 3) {
                        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
                    }
                    $int = hexdec($hex);
                    return [($int >> 16) & 255, ($int >> 8) & 255, $int & 255];
                };

                $relLuminance = function(array $rgb) {
                    $sr = $rgb[0] / 255; $sg = $rgb[1] / 255; $sb = $rgb[2] / 255;
                    $r = ($sr <= 0.03928) ? ($sr / 12.92) : pow((($sr + 0.055) / 1.055), 2.4);
                    $g = ($sg <= 0.03928) ? ($sg / 12.92) : pow((($sg + 0.055) / 1.055), 2.4);
                    $b = ($sb <= 0.03928) ? ($sb / 12.92) : pow((($sb + 0.055) / 1.055), 2.4);
                    return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
                };

                $contrastRatio = function(array $rgb1, array $rgb2) use ($relLuminance) {
                    $l1 = $relLuminance($rgb1);
                    $l2 = $relLuminance($rgb2);
                    $lighter = max($l1, $l2);
                    $darker = min($l1, $l2);
                    return ($lighter + 0.05) / ($darker + 0.05);
                };

                $primaryHex = $brandPalette['primary'] ?? '#4f46e5';
                $whiteRgb = [255,255,255];
                $blackRgb = [6,17,46]; // dark navy used across app

                try {
                    $primaryRgb = $hex2rgb($primaryHex);
                    $contrastWithWhite = $contrastRatio($primaryRgb, $whiteRgb);
                    // Prefer white if contrast >= 4.5:1, otherwise use dark text for better readability
                    $computedTextOnPrimary = $contrastWithWhite >= 4.5 ? '#ffffff' : '#06112e';
                } catch (\Throwable $e) {
                    $computedTextOnPrimary = $brandPalette['text_on_primary'] ?? '#ffffff';
                }
            ?>
            --brand-text-on-primary: <?php echo $computedTextOnPrimary; ?>;
            --brand-text-on-secondary: {{ $brandPalette['text_on_secondary'] }};
            --brand-text-on-accent: {{ $brandPalette['text_on_accent'] }};
            --sidebar-width: 16.5rem;
        }
        .logout-button {
            transition: all 180ms ease-in-out !important;
        }
        .logout-button:hover {
            background-color: #fef2f2 !important;
            border-color: #fecaca !important;
            color: #dc2626 !important;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.06) !important;
            transform: translateY(-1px);
        }
        .logout-button:hover .sidebar-icon {
            color: #dc2626 !important;
        }
    </style>
</head>
<body class="min-h-screen font-sans text-slate-900 bg-slate-50">
    <!-- Global Toast Container -->
    <div id="toast-container" class="fixed top-5 right-5 z-50 flex flex-col gap-3 pointer-events-none max-w-sm w-full">
        @if (session('success'))
            <div class="toast-item toast-enter pointer-events-auto flex items-start gap-3 rounded-xl bg-white border border-emerald-100 p-4 shadow-[0_10px_30px_rgba(15,23,42,0.08)]" data-type="success">
                <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                    <i class="ti ti-check text-sm font-semibold"></i>
                </div>
                <div class="flex-1">
                    <p class="text-xs font-semibold text-slate-900">Success</p>
                    <p class="mt-0.5 text-xs text-slate-500">{{ session('success') }}</p>
                </div>
                <button type="button" class="toast-close text-slate-400 hover:text-slate-600 transition">
                    <i class="ti ti-x text-sm"></i>
                </button>
            </div>
        @endif

        @if (session('error'))
            <div class="toast-item toast-enter pointer-events-auto flex items-start gap-3 rounded-xl bg-white border border-rose-100 p-4 shadow-[0_10px_30px_rgba(15,23,42,0.08)]" data-type="error">
                <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-600">
                    <i class="ti ti-x text-sm font-semibold"></i>
                </div>
                <div class="flex-1">
                    <p class="text-xs font-semibold text-slate-900">Error</p>
                    <p class="mt-0.5 text-xs text-slate-500">{{ session('error') }}</p>
                </div>
                <button type="button" class="toast-close text-slate-400 hover:text-slate-600 transition">
                    <i class="ti ti-x text-sm"></i>
                </button>
            </div>
        @endif

        @if ($errors->any())
            @foreach ($errors->all() as $error)
                <div class="toast-item toast-enter pointer-events-auto flex items-start gap-3 rounded-xl bg-white border border-rose-100 p-4 shadow-[0_10px_30px_rgba(15,23,42,0.08)]" data-type="validation-error">
                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-600">
                        <i class="ti ti-alert-triangle text-sm font-semibold"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-xs font-semibold text-slate-900">Validation Error</p>
                        <p class="mt-0.5 text-xs text-slate-500">{{ $error }}</p>
                    </div>
                    <button type="button" class="toast-close text-slate-400 hover:text-slate-600 transition">
                        <i class="ti ti-x text-sm"></i>
                    </button>
                </div>
            @endforeach
        @endif
    </div>

    @php
        $user = auth()->user();
        $hasNotificationTable = \Illuminate\Support\Facades\Schema::hasTable('notifications');
        $hasNotificationMorphColumns = $hasNotificationTable
            && \Illuminate\Support\Facades\Schema::hasColumn('notifications', 'notifiable_type')
            && \Illuminate\Support\Facades\Schema::hasColumn('notifications', 'notifiable_id');
        $recentNotifications = $user
            ? ($hasNotificationMorphColumns ? $user->notifications()->latest()->limit(5)->get() : collect())
            : collect();
        $unreadNotificationCount = $user
            ? ($hasNotificationMorphColumns ? $user->unreadNotifications()->count() : 0)
            : 0;
        $navGroups = [
            'Overview' => [
                ['route' => 'dashboard', 'path' => '/dashboard', 'label' => 'Dashboard', 'icon' => 'layout-dashboard'],
            ],
            'Modules' => [
                ['label' => 'Employees', 'icon' => 'user', 'path' => '/employees', 'permission' => 'employees.view,employees.create,employees.edit,employees.delete', 'children' => [
                    ['route' => 'employees.index',           'path' => '/employees',                  'label' => 'Employee List'],
                    ['route' => 'employees.temporary-access', 'path' => '/employees-temporary-access', 'label' => 'Temporary Access', 'roles' => [4]],
                ]],
                ['route' => 'onboarding', 'path' => '/onboarding', 'label' => 'Onboarding', 'icon' => 'user-plus', 'permission' => 'onboarding.view'],
                ['route' => 'timekeeping.index', 'path' => '/timekeeping', 'label' => 'Timekeeping', 'icon' => 'clock', 'permission' => 'timekeeping.view,timekeeping.create,timekeeping.edit,timekeeping.delete', 'children' => [
                    ['route' => 'timekeeping.index', 'path' => '/timekeeping', 'label' => 'Attendance'],
                    ['route' => 'timekeeping.shift-schedule', 'path' => '/timekeeping/shift-schedule', 'label' => 'Shift Schedule'],
                ]],
                ['label' => 'Leave', 'icon' => 'calendar-event', 'path' => '/leave', 'permission' => 'leaves.view,leaves.create,leaves.edit,leaves.delete', 'children' => [
                    ['route' => 'leave.index',    'path' => '/leave',          'label' => 'Requests'],
                    ['route' => 'leave.calendar', 'path' => '/leave/calendar', 'label' => 'Calendar'],
                ]],
                ['label' => 'Salaries', 'icon' => 'coins', 'path' => '/salaries', 'permission' => 'payroll.view,payroll.create,payroll.edit,payroll.delete', 'children' => [
                    ['route' => 'salary.index',    'path' => '/salaries',          'label' => 'Salary Records'],
                    ['route' => 'salary.settings', 'path' => '/salaries/settings', 'label' => 'Salary Settings'],
                    ['route' => 'salary.government-premiums', 'path' => '/salaries/government-premiums', 'label' => 'Goverment Premiums'],
                ]],
                ['label' => 'Payroll', 'icon' => 'wallet', 'path' => '/payroll', 'permission' => 'payroll.view,payroll.create,payroll.edit,payroll.delete', 'children' => [
                    ['route' => 'payroll.index',                 'path' => '/payroll',                   'label' => 'Payroll Run'],
                    ['route' => 'payroll.plotting-payment',      'path' => '/payroll/plotting-payment',  'label' => 'Plotting of Payments'],
                    ['route' => 'payroll.previous-claims.index', 'path' => '/payroll/previous-claims',   'label' => 'Previous Claims'],
                    ['route' => 'payroll.previous-claims.index', 'path' => '/payroll/previous-claims',   'label' => 'Overtime Requests', 'query' => ['type' => 'Overtime']],
                    ['route' => 'payroll.previous-claims.index', 'path' => '/payroll/previous-claims',   'label' => 'Night Differential Requests', 'query' => ['type' => 'Night Differential']],
                    ['route' => 'payroll.disputes.index',        'path' => '/payroll/disputes',          'label' => 'Disputes'],
                ]],
                ['route' => 'benefits', 'path' => '/benefits', 'label' => 'Benefits', 'icon' => 'heartbeat', 'permission' => 'benefits.view,benefits.create,benefits.edit,benefits.delete'],
                ['route' => 'self-service', 'path' => '/self-service', 'label' => 'Self-Service', 'icon' => 'user-circle', 'permission' => 'self-service.view'],
                ['route' => 'reports', 'path' => '/reports', 'label' => 'Reports', 'icon' => 'chart-bar', 'permission' => 'reports.view,reports.create,reports.edit,reports.delete'],
            ],
        ];

        // Filter nav groups by permissions
        foreach ($navGroups as $groupName => &$items) {
            $items = array_filter($items, function ($item) use ($user) {
                if (isset($item['permission'])) {
                    if (!$user) return false;
                    $perms = explode(',', $item['permission']);
                    foreach ($perms as $p) {
                        if ($user->hasPermission(trim($p))) return true;
                    }
                    return false;
                }
                return true;
            });
        }
        unset($items);

        // Filter out empty nav groups
        $navGroups = array_filter($navGroups, fn($items) => count($items) > 0);

        $organizationActive = request()->routeIs('organization.departments.*', 'organization.positions.*');
        $userInitials = $user?->name
            ? collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))->implode('')
            : 'HR';
        $workspaceLabel = $header ?? ($title ?? 'Workspace');
    @endphp

    <div class="flex min-h-screen bg-transparent">
        <div id="sidebarOverlay" class="sidebar-overlay fixed inset-0 z-30 bg-slate-950/35 lg:hidden"></div>

        <aside class="sidebar fixed left-0 top-0 z-40 flex h-full flex-col border-r border-slate-200 bg-white px-3 py-3 text-slate-700 shadow-[0_1px_3px_rgba(15,23,42,0.08)]">
            @php
                $companySetting = $themeSettings;
                $hasLogo = !empty($companySetting->logo_path);
                $settingsRouteExists = \Illuminate\Support\Facades\Route::has('organization.settings');
                $settingsHref = $settingsRouteExists ? route('organization.settings') : '#';
                $canAccessSettings = $user && $user->role === 4;
            @endphp
            @if ($canAccessSettings)
                <a href="{{ $settingsHref }}" class="flex items-center gap-3 px-2.5 py-2.5 mb-1.5 shrink-0 hover:bg-slate-50 border border-transparent hover:border-slate-100/80 rounded-2xl transition duration-150 group">
            @else
                <div class="flex items-center gap-3 px-2.5 py-2.5 mb-1.5 shrink-0">
            @endif
                @if ($hasLogo)
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full border border-slate-200/80 bg-white shadow-sm transition duration-150 group-hover:border-slate-300">
                        <img src="{{ route('media.file', ['path' => ltrim($companySetting->logo_path, '/')]) }}" alt="Company Logo" class="h-full w-full object-cover">
                    </div>
                @else
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-slate-200/80 shadow-sm transition duration-150 group-hover:border-slate-300" style="background-image: linear-gradient(135deg, {{ $brandPalette['primary_soft'] }} 0%, {{ $brandPalette['secondary_soft'] }} 100%); color: {{ $brandPalette['secondary'] }};">
                        <i class="ti ti-building text-2xl"></i>
                    </div>
                @endif
                <div class="min-w-0 flex-1">
                    <h2 class="text-sm font-bold text-slate-800 truncate tracking-tight transition duration-150 group-hover:text-slate-950" title="{{ $companySetting->company_name ?: 'Company Name' }}">
                        {{ $companySetting->company_name ?: 'Company Name' }}
                    </h2>
                    @if ($companySetting->tagline)
                        <p class="text-[11px] font-medium text-slate-400 truncate mt-0.5" title="{{ $companySetting->tagline }}">
                            {{ $companySetting->tagline }}
                        </p>
                    @endif
                </div>
            @if ($canAccessSettings)
                </a>
            @else
                </div>
            @endif

            <div class="sidebar-scroll flex flex-1 flex-col overflow-y-auto pb-3">
                <nav class="space-y-1">
                    @foreach ($navGroups as $groupName => $items)
                        <p class="sidebar-group-label px-2 {{ $loop->first ? 'pt-1.5' : 'pt-4' }} pb-2 text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ $groupName }}</p>

                        @foreach ($items as $item)
                            @if (isset($item['children']))
                                @php
                                    $parentRouteExists = isset($item['route']) && \Illuminate\Support\Facades\Route::has($item['route']);
                                    $parentHref = $parentRouteExists ? route($item['route']) : url($item['path'] ?? '#');
                                    $hasActiveChild = collect($item['children'])->contains(function ($child) {
                                        $childRouteExists = \Illuminate\Support\Facades\Route::has($child['route']);
                                        if ($childRouteExists) {
                                            return request()->routeIs($child['route']) || request()->routeIs($child['route'].'.*') || ($child['route'] === 'payroll.plotting-payment' && (request()->routeIs('payroll.work-location-details') || request()->routeIs('payroll.per-date'))) || ($child['route'] === 'payroll.index' && request()->routeIs('payroll.show', 'payroll.edit'));
                                        }
                                        return request()->is(ltrim($child['path'], '/'));
                                    });
                                    $hasActiveParent = $parentRouteExists
                                        ? request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*')
                                        : request()->is(ltrim($item['path'] ?? '', '/'));
                                    $groupIsActive = $hasActiveParent || $hasActiveChild;
                                @endphp
                                <details class="sidebar-group" @if ($groupIsActive) open @endif>
                                    <summary class="sidebar-link {{ $groupIsActive ? 'sidebar-link-active' : '' }} cursor-pointer list-none">
                                        <a href="{{ $parentHref }}" class="flex flex-1 items-center gap-[0.1rem] text-inherit no-underline">
                                            <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center">
                                                <i class="ti ti-{{ $item['icon'] }} sidebar-icon text-xl"></i>
                                            </span>
                                            <span class="sidebar-nav-label whitespace-nowrap font-medium flex-1">{{ $item['label'] }}</span>
                                        </a>
                                        <span class="text-xs text-slate-400">▾</span>
                                    </summary>

                                    <div class="mt-1 space-y-1 pl-2">
                                        @foreach ($item['children'] as $child)
                                            @if(isset($child['roles']) && !in_array($user->role ?? 0, $child['roles']))
                                                @continue
                                            @endif
                                            @php
                                                $childRouteExists = \Illuminate\Support\Facades\Route::has($child['route']);
                                                $childIsActive = false;
                                                if ($childRouteExists) {
                                                    $baseActive = request()->routeIs($child['route']) || request()->routeIs($child['route'].'.*') || ($child['route'] === 'payroll.plotting-payment' && (request()->routeIs('payroll.work-location-details') || request()->routeIs('payroll.per-date'))) || ($child['route'] === 'payroll.index' && request()->routeIs('payroll.show', 'payroll.edit'));
                                                    if ($baseActive) {
                                                        if (isset($child['query'])) {
                                                            $childIsActive = true;
                                                            foreach ($child['query'] as $key => $val) {
                                                                if (request()->query($key) !== $val) {
                                                                    $childIsActive = false;
                                                                    break;
                                                                }
                                                            }
                                                        } else {
                                                            $childIsActive = !request()->has('type');
                                                        }
                                                    }
                                                } else {
                                                    $childIsActive = request()->is(ltrim($child['path'], '/'));
                                                }
                                                $childHref = $childRouteExists ? route($child['route'], $child['query'] ?? []) : url($child['path']);
                                            @endphp
                                            <a href="{{ $childHref }}" class="sidebar-link sidebar-link-sub {{ $childIsActive ? 'sidebar-link-active' : '' }}">
                                                <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center">
                                                    <i class="ti ti-point sidebar-icon text-base"></i>
                                                </span>
                                                <span class="sidebar-nav-label whitespace-nowrap font-medium text-xs">{{ $child['label'] }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </details>
                            @else
                                @php
                                    $routeExists = \Illuminate\Support\Facades\Route::has($item['route']);
                                    $isActive = $routeExists
                                        ? request()->routeIs($item['route']) || ($item['route'] === 'self-service' && request()->routeIs('self-service.*'))
                                        : request()->is(ltrim($item['path'], '/'));
                                    $href = $routeExists ? route($item['route']) : url($item['path']);
                                @endphp
                                <a href="{{ $href }}" class="sidebar-link {{ $isActive ? 'sidebar-link-active' : '' }}">
                                    <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center">
                                        <i class="ti ti-{{ $item['icon'] }} sidebar-icon text-xl"></i>
                                    </span>
                                    <span class="sidebar-nav-label whitespace-nowrap font-medium">{{ $item['label'] }}</span>
                                </a>
                            @endif
                        @endforeach
                    @endforeach

                    @if ($user && $user->role === 4)
                    <p class="sidebar-group-label px-2 pt-4 pb-2 text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Organization</p>

                    @php
                        $departmentsRouteExists = \Illuminate\Support\Facades\Route::has('organization.departments.index');
                        $departmentsHref = $departmentsRouteExists ? route('organization.departments.index') : url('/organization/departments');
                        $departmentsActive = $departmentsRouteExists ? request()->routeIs('organization.departments.*', 'organization.positions.*') : request()->is('organization/departments*');

                        $usersRouteExists = \Illuminate\Support\Facades\Route::has('organization.users.index');
                        $usersHref = $usersRouteExists ? route('organization.users.index') : url('/organization/users');
                        $usersActive = $usersRouteExists ? request()->routeIs('organization.users.*') : request()->is('organization/users*');

                        $settingsRouteExists = \Illuminate\Support\Facades\Route::has('organization.settings');
                        $settingsHref = $settingsRouteExists ? route('organization.settings') : url('/organization/settings');
                        $settingsActive = $settingsRouteExists ? request()->routeIs('organization.settings') : request()->is('organization/settings*');
                    @endphp

                    <a href="{{ $usersHref }}" class="sidebar-link {{ $usersActive ? 'sidebar-link-active' : '' }}">
                        <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center">
                            <i class="ti ti-users sidebar-icon text-xl"></i>
                        </span>
                        <span class="sidebar-nav-label whitespace-nowrap font-medium">Users</span>
                    </a>

                    <a href="{{ $departmentsHref }}" class="sidebar-link {{ $departmentsActive ? 'sidebar-link-active' : '' }}">
                        <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center">
                            <i class="ti ti-building sidebar-icon text-xl"></i>
                        </span>
                        <span class="sidebar-nav-label whitespace-nowrap font-medium">Departments</span>
                    </a>

                    @php
                        $machineSettingsRouteExists = \Illuminate\Support\Facades\Route::has('organization.settings.machine-settings');
                        $machineSettingsHref = $machineSettingsRouteExists ? route('organization.settings.machine-settings') : url('/organization/settings/machine-settings');
                        $machineTabEnabled = \App\Models\CompanySetting::current()->use_machine;
                    @endphp

                    <details class="sidebar-group" @if (request()->routeIs('organization.settings') || request()->routeIs('organization.settings.*') || request()->routeIs('organization.settings.machine-settings')) open @endif>
                        <summary class="sidebar-link {{ $settingsActive ? 'sidebar-link-active' : '' }} cursor-pointer list-none">
                            <a href="{{ $settingsHref }}" class="flex flex-1 items-center gap-[0.1rem] text-inherit no-underline">
                                <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center">
                                    <i class="ti ti-settings sidebar-icon text-xl"></i>
                                </span>
                                <span class="sidebar-nav-label whitespace-nowrap font-medium">Settings</span>
                            </a>
                            <span class="text-xs text-slate-400">▾</span>
                        </summary>

                        <div class="mt-1 space-y-1 pl-2">
                            <a href="{{ $settingsHref }}" class="sidebar-link sidebar-link-sub {{ request()->routeIs('organization.settings') ? 'sidebar-link-active' : '' }}">
                                <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center">
                                    <i class="ti ti-point sidebar-icon text-base"></i>
                                </span>
                                <span class="sidebar-nav-label whitespace-nowrap font-medium text-xs">Company Settings</span>
                            </a>

                            @if ($machineTabEnabled)
                                <a href="{{ $machineSettingsHref }}" class="sidebar-link sidebar-link-sub {{ request()->routeIs('organization.settings.machine-settings') ? 'sidebar-link-active' : '' }}">
                                    <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center">
                                        <i class="ti ti-point sidebar-icon text-base"></i>
                                    </span>
                                    <span class="sidebar-nav-label whitespace-nowrap font-medium text-xs">Machines</span>
                                </a>
                            @endif
                        </div>
                    </details>
                    @endif
                </nav>
            </div>

            <div class="mt-auto pt-4">
                <form id="logoutForm" method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <button type="button" id="logoutTrigger" class="logout-button flex w-full items-center rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-slate-700 transition hover:bg-slate-50">
                        <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center">
                            <i class="ti ti-logout sidebar-icon text-xl"></i>
                        </span>
                        <span class="sidebar-nav-label whitespace-nowrap font-medium">Logout</span>
                    </button>
                </form>
            </div>
        </aside>

        <main class="main-content relative flex-1 min-h-screen overflow-y-auto transition-[padding-left] duration-300 ease-in-out">
            <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 pl-4 pr-5 py-3 backdrop-blur-md sm:pl-5 sm:pr-6 sm:py-3.5 lg:pr-8">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-2 text-slate-500">
                        <button id="sidebar-toggle" type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2" style="--tw-ring-color: var(--brand-primary-ring);" aria-label="Toggle Sidebar">
                            <i class="ti ti-layout-sidebar text-[1.15rem]"></i>
                        </button>
                        <h1 class="truncate text-[1.05rem] font-medium text-slate-700">{{ $workspaceLabel }}</h1>
                    </div>

                    <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                        <div class="relative">
                            <button id="notificationToggle" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:bg-slate-50 sm:h-10 sm:w-10" type="button" aria-label="Notifications" aria-expanded="false" aria-controls="notificationPanel">
                                <i class="ti ti-bell text-xl"></i>
                                @if ($unreadNotificationCount > 0)
                                    <span class="absolute -right-0.5 -top-0.5 inline-flex min-w-5 items-center justify-center rounded-full bg-rose-600 px-1.5 py-0.5 text-[0.65rem] font-bold leading-none text-white">
                                        {{ $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount }}
                                    </span>
                                @endif
                            </button>

                            <div id="notificationPanel" class="notification-panel fixed left-3 right-3 top-16 z-40 hidden max-h-[calc(100vh-6rem)] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl sm:absolute sm:left-auto sm:right-0 sm:top-12 sm:w-96 sm:max-h-96 sm:max-w-none">
                                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">Notifications</p>
                                        <p class="text-xs text-slate-500">Recent system activity</p>
                                    </div>
                                    @if ($unreadNotificationCount > 0)
                                        <form method="POST" action="{{ route('notifications.read-all') }}">
                                            @csrf
                                            <button type="submit" class="rounded-lg px-2.5 py-1 text-xs font-semibold text-[#1a56db] transition hover:bg-blue-50">
                                                Mark all read
                                            </button>
                                        </form>
                                    @endif
                                </div>

                                <div class="max-h-[calc(100vh-10rem)] overflow-y-auto sm:max-h-96">
                                    @forelse ($recentNotifications as $notification)
                                        @php
                                            $notificationData = $notification->data ?? [];
                                            $notificationTitle = $notificationData['title'] ?? 'Notification';
                                            $notificationMessage = $notificationData['message'] ?? '';
                                            $notificationIcon = $notificationData['icon'] ?? 'ti ti-bell';
                                        @endphp
                                        <a href="{{ route('notifications.show', $notification->id) }}" class="flex items-start gap-3 border-b border-slate-100 px-4 py-3 transition hover:bg-slate-50 {{ $notification->read_at ? 'opacity-80' : '' }}">
                                            <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $notification->read_at ? 'bg-slate-100 text-slate-500' : 'bg-blue-50 text-[#1a56db]' }}">
                                                <i class="{{ $notificationIcon }} text-base"></i>
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-2">
                                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $notificationTitle }}</p>
                                                    @if (!$notification->read_at)
                                                        <span class="rounded-full bg-blue-50 px-2 py-0.5 text-[0.65rem] font-semibold text-[#1a56db]">New</span>
                                                    @endif
                                                </div>
                                                <p class="mt-0.5 text-xs text-slate-500">{{ $notificationMessage }}</p>
                                                <p class="mt-1 text-[0.7rem] text-slate-400">{{ optional($notification->created_at)->diffForHumans() }}</p>
                                            </div>
                                        </a>
                                    @empty
                                        <div class="px-4 py-6 text-center text-sm text-slate-500">
                                            No notifications yet.
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        <a href="{{ route('profile.show') }}" class="inline-flex h-9 w-9 items-center justify-center rounded-full overflow-hidden border border-slate-200 text-sm font-bold shadow-sm transition hover:opacity-90 hover:scale-105 sm:h-10 sm:w-10" style="background-color: var(--brand-primary); color: var(--brand-text-on-primary);" title="View Profile">
                            @if ($user && $user->employee && $user->employee->profile_picture)
                                <img src="{{ route('media.file', ['path' => ltrim($user->employee->profile_picture, '/')]) }}" alt="Profile" class="h-full w-full object-cover">
                            @else
                                {{ $userInitials }}
                            @endif
                        </a>
                    </div>
                </div>
            </header>

            <div class="p-4 sm:p-5 lg:p-6">
                <div class="space-y-5">
                    @if ($showGlobalAlerts && session('success'))
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($showGlobalAlerts && session('error'))
                        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if ($showGlobalAlerts && $errors->any())
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                            <p class="font-medium">Please review the following:</p>
                            <ul class="mt-2 list-disc pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{ $slot }}
                </div>
            </div>
        </main>
    </div>

    <div id="logoutModal" class="fixed inset-0 z-50 hidden bg-slate-950/40 p-4 justify-center items-start sm:items-center overflow-y-auto">
        <div class="my-auto w-full max-w-md rounded-2xl border border-slate-200 bg-white shadow-xl max-h-[calc(100vh-2rem)] sm:max-h-[90vh] overflow-y-auto">
            <div class="border-b border-slate-100 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Confirm Logout</h3>
                <p class="mt-1 text-sm text-slate-500">Are you sure you want to sign out of the system?</p>
            </div>
            <div class="flex items-center justify-end gap-3 px-6 py-4">
                <button type="button" id="logoutCancel" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    Cancel
                </button>
                <button type="button" id="logoutConfirm" class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-rose-700">
                    Logout
                </button>
            </div>
        </div>
    </div>

    <div id="confirmModal" class="fixed inset-0 z-50 hidden bg-slate-950/40 p-4 backdrop-blur-sm justify-center items-start sm:items-center overflow-y-auto">
        <div class="my-auto w-full max-w-md rounded-2xl border border-slate-200 bg-white shadow-xl overflow-y-auto max-h-[calc(100vh-2rem)] sm:max-h-[90vh]">
            <div class="px-6 py-6">
                <div class="flex flex-col items-center text-center">
                    <div id="confirmModalIconContainer" class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-50 text-amber-600 mb-3">
                        <i id="confirmModalIcon" class="ti ti-alert-triangle text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900" id="confirmModalTitle">Confirm Action</h3>
                    <p class="mt-2 text-sm text-slate-500" id="confirmModalMessage">Are you sure you want to proceed?</p>
                </div>
            </div>
            <div class="flex items-center justify-center gap-3 px-6 py-4 bg-slate-50 border-t border-slate-100">
                <button type="button" id="confirmCancel" class="rounded-xl border border-slate-200 bg-white px-5 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    Cancel
                </button>
                <button type="button" id="confirmProceed" class="rounded-xl bg-slate-900 px-5 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
                    Confirm
                </button>
            </div>
        </div>
    </div>

    {{ $scripts ?? '' }}

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.querySelector('.sidebar');
            if (!sidebar) return;

            sidebar.addEventListener('mouseleave', function () {
                document.querySelectorAll('details.organization-menu[open]').forEach(function (details) {
                    details.removeAttribute('open');
                });
            });

            const sidebarToggle = document.getElementById('sidebar-toggle');
            const sidebarOverlay = document.getElementById('sidebarOverlay');

            const isMobileViewport = function () {
                return window.innerWidth < 1024;
            };

            const closeMobileSidebar = function () {
                document.body.classList.remove('sidebar-open');
            };

            const syncResponsiveSidebarState = function () {
                if (isMobileViewport()) {
                    document.body.classList.remove('sidebar-collapsed');
                } else {
                    document.body.classList.remove('sidebar-open');
                }
            };

            syncResponsiveSidebarState();

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    if (isMobileViewport()) {
                        document.body.classList.toggle('sidebar-open');
                    } else {
                        document.body.classList.toggle('sidebar-collapsed');
                    }
                });
            }

            sidebarOverlay?.addEventListener('click', closeMobileSidebar);

            window.addEventListener('resize', syncResponsiveSidebarState);

            sidebar.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    if (isMobileViewport()) {
                        closeMobileSidebar();
                    }
                });
            });

            const logoutForm = document.getElementById('logoutForm');
            const logoutTrigger = document.getElementById('logoutTrigger');
            const logoutModal = document.getElementById('logoutModal');
            const logoutCancel = document.getElementById('logoutCancel');
            const logoutConfirm = document.getElementById('logoutConfirm');

            const openLogoutModal = function () {
                if (!logoutModal) return;

                logoutModal.classList.remove('hidden');
                logoutModal.classList.add('flex');
            };

            const closeLogoutModal = function () {
                if (!logoutModal) return;

                logoutModal.classList.remove('flex');
                logoutModal.classList.add('hidden');
            };

            if (logoutTrigger) {
                logoutTrigger.addEventListener('click', openLogoutModal);
            }

            if (logoutCancel) {
                logoutCancel.addEventListener('click', closeLogoutModal);
            }

            if (logoutConfirm && logoutForm) {
                logoutConfirm.addEventListener('click', function () {
                    logoutForm.submit();
                });
            }

            if (logoutModal) {
                logoutModal.addEventListener('click', function (event) {
                    if (event.target === logoutModal) {
                        closeLogoutModal();
                    }
                });
            }

            const notificationToggle = document.getElementById('notificationToggle');
            const notificationPanel = document.getElementById('notificationPanel');

            const closeNotificationPanel = function () {
                if (!notificationPanel || !notificationToggle) {
                    return;
                }

                notificationPanel.classList.add('hidden');
                notificationToggle.setAttribute('aria-expanded', 'false');
            };

            const toggleNotificationPanel = function () {
                if (!notificationPanel || !notificationToggle) {
                    return;
                }

                const isHidden = notificationPanel.classList.contains('hidden');
                if (isHidden) {
                    notificationPanel.classList.remove('hidden');
                    notificationToggle.setAttribute('aria-expanded', 'true');
                } else {
                    closeNotificationPanel();
                }
            };

            if (notificationToggle) {
                notificationToggle.addEventListener('click', function (event) {
                    event.stopPropagation();
                    toggleNotificationPanel();
                });
            }

            if (notificationPanel) {
                notificationPanel.addEventListener('click', function (event) {
                    event.stopPropagation();
                });
            }

            document.addEventListener('click', function () {
                closeNotificationPanel();
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && logoutModal && logoutModal.classList.contains('flex')) {
                    closeLogoutModal();
                }

                if (event.key === 'Escape') {
                    closeNotificationPanel();
                }

                if (event.key === 'Escape') {
                    closeMobileSidebar();
                }
            });

            // Confirm Modal
            let __pendingConfirmForm = null;
            let __confirmCallback = null;

            const confirmModal = document.getElementById('confirmModal');
            const confirmCancel = document.getElementById('confirmCancel');
            const confirmProceed = document.getElementById('confirmProceed');
            const confirmModalTitle = document.getElementById('confirmModalTitle');
            const confirmModalMessage = document.getElementById('confirmModalMessage');
            const confirmModalIconContainer = document.getElementById('confirmModalIconContainer');
            const confirmModalIcon = document.getElementById('confirmModalIcon');

            function openConfirmModal(title, message, callback = null, options = {}) {
                if (!confirmModal) return;
                confirmModalTitle.innerHTML = title || 'Confirm Action';
                confirmModalMessage.innerHTML = message || 'Are you sure you want to proceed?';
                __confirmCallback = callback;

                // Customize button labels if provided
                if (confirmProceed) {
                    confirmProceed.innerHTML = options.confirmText || 'Confirm';
                    // Reset class list to default, then apply type-specific classes
                    confirmProceed.className = "rounded-xl px-5 py-2 text-sm font-medium text-white transition " + (options.confirmClass || 'bg-slate-900 hover:bg-slate-800');
                }
                if (confirmCancel) {
                    confirmCancel.innerHTML = options.cancelText || 'Cancel';
                }

                // Customize icon and color scheme based on type
                if (confirmModalIconContainer && confirmModalIcon) {
                    confirmModalIconContainer.className = "flex h-12 w-12 items-center justify-center rounded-full mb-3";
                    confirmModalIcon.className = "text-2xl";

                    const type = options.type || 'warning';
                    if (type === 'danger') {
                        confirmModalIconContainer.classList.add('bg-rose-50', 'text-rose-600');
                        confirmModalIcon.classList.add('ti', 'ti-trash');
                        if (confirmProceed && !options.confirmClass) {
                            confirmProceed.className = "rounded-xl px-5 py-2 text-sm font-medium text-white transition bg-rose-600 hover:bg-rose-700";
                        }
                    } else if (type === 'success') {
                        confirmModalIconContainer.classList.add('bg-emerald-50', 'text-emerald-600');
                        confirmModalIcon.classList.add('ti', 'ti-circle-check');
                        if (confirmProceed && !options.confirmClass) {
                            confirmProceed.className = "rounded-xl px-5 py-2 text-sm font-medium text-white transition bg-emerald-600 hover:bg-emerald-700";
                        }
                    } else if (type === 'info') {
                        confirmModalIconContainer.classList.add('bg-blue-50', 'text-blue-600');
                        confirmModalIcon.classList.add('ti', 'ti-info-circle');
                        if (confirmProceed && !options.confirmClass) {
                            confirmProceed.className = "rounded-xl px-5 py-2 text-sm font-medium text-white transition bg-blue-600 hover:bg-blue-700";
                        }
                    } else { // default warning
                        confirmModalIconContainer.classList.add('bg-amber-50', 'text-amber-600');
                        confirmModalIcon.classList.add('ti', 'ti-alert-triangle');
                        if (confirmProceed && !options.confirmClass) {
                            confirmProceed.className = "rounded-xl bg-slate-900 px-5 py-2 text-sm font-medium text-white transition hover:bg-slate-800";
                        }
                    }
                }

                confirmModal.classList.remove('hidden');
                confirmModal.classList.add('flex');
            }

            function closeConfirmModal() {
                if (!confirmModal) return;
                confirmModal.classList.remove('flex');
                confirmModal.classList.add('hidden');
                __pendingConfirmForm = null;
                __confirmCallback = null;
            }

            // Expose globally
            window.showConfirmModal = function(title, message, callback, options = {}) {
                openConfirmModal(title, message, callback, options);
            };

            function initConfirmModal() {
                document.querySelectorAll('[data-confirm]').forEach(function (el) {
                    el.addEventListener('click', function (e) {
                        const message = el.getAttribute('data-confirm') || 'Are you sure you want to proceed?';
                        const title = el.getAttribute('data-confirm-title') || 'Confirm Action';
                        const type = el.getAttribute('data-confirm-type') || 'warning';
                        const confirmText = el.getAttribute('data-confirm-text') || 'Confirm';
                        const cancelText = el.getAttribute('data-confirm-cancel') || 'Cancel';

                        let form = null;
                        if (el.hasAttribute('form')) {
                            form = document.getElementById(el.getAttribute('form'));
                        } else {
                            form = el.closest('form');
                        }

                        if (!form) return;

                        e.preventDefault();
                        __pendingConfirmForm = form;
                        openConfirmModal(title, message, null, { type, confirmText, cancelText });
                    });
                });

                if (confirmCancel) {
                    confirmCancel.addEventListener('click', function () {
                        closeConfirmModal();
                    });
                }

                if (confirmProceed) {
                    confirmProceed.addEventListener('click', function () {
                        if (__pendingConfirmForm) {
                            const form = __pendingConfirmForm;
                            closeConfirmModal();
                            if (typeof form.requestSubmit === 'function') {
                                form.requestSubmit();
                            } else {
                                form.submit();
                            }
                        } else if (__confirmCallback) {
                            __confirmCallback();
                            closeConfirmModal();
                        }
                    });
                }

                if (confirmModal) {
                    confirmModal.addEventListener('click', function (event) {
                        if (event.target === confirmModal) {
                            closeConfirmModal();
                        }
                    });
                }

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' && confirmModal && confirmModal.classList.contains('flex')) {
                        closeConfirmModal();
                    }
                });
            }

            initConfirmModal();

            // Toast Notification System
            function initToast(toast, index) {
                // Trigger enter animation
                setTimeout(() => {
                    toast.classList.remove('toast-enter');
                    toast.classList.add('toast-enter-active');
                }, index * 100);

                // Auto dismiss
                const timeoutId = setTimeout(() => {
                    dismissToast(toast);
                }, 6000 + (index * 150));

                // Close button click
                toast.querySelector('.toast-close')?.addEventListener('click', () => {
                    clearTimeout(timeoutId);
                    dismissToast(toast);
                });
            }

            function dismissToast(toast) {
                toast.classList.remove('toast-enter-active');
                toast.classList.add('toast-exit');
                toast.addEventListener('transitionend', () => {
                    toast.remove();
                });
                // Fallback if transitionend event doesn't fire
                setTimeout(() => {
                    toast.remove();
                }, 400);
            }

            // Initialize static toasts (rendered from server session)
            document.querySelectorAll('.toast-item').forEach((toast, idx) => {
                initToast(toast, idx);
            });

            // Global function to trigger a toast programmatically
            window.showToast = function(type, title, message) {
                const container = document.getElementById('toast-container');
                if (!container) return;

                const isSuccess = type === 'success';
                const iconClass = isSuccess ? 'ti ti-check text-emerald-600' : 'ti ti-alert-triangle text-rose-600';
                const bgClass = isSuccess ? 'bg-emerald-50' : 'bg-rose-50';
                const borderClass = isSuccess ? 'border-emerald-100' : 'border-rose-100';

                const toast = document.createElement('div');
                toast.className = `toast-item toast-enter pointer-events-auto flex items-start gap-3 rounded-xl bg-white border ${borderClass} p-4 shadow-[0_10px_30px_rgba(15,23,42,0.08)]`;
                toast.innerHTML = `
                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full ${bgClass}">
                        <i class="${iconClass} text-sm font-semibold"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-xs font-semibold text-slate-900">${title}</p>
                        <p class="mt-0.5 text-xs text-slate-500">${message}</p>
                    </div>
                    <button type="button" class="toast-close text-slate-400 hover:text-slate-600 transition">
                        <i class="ti ti-x text-sm"></i>
                    </button>
                `;
                container.appendChild(toast);
                initToast(toast, container.querySelectorAll('.toast-item').length - 1);
            };
        });
    </script>
</body>
</html>
