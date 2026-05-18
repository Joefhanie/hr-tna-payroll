<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'HR System' }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-screen overflow-hidden font-sans text-slate-900">
    @php
        $navGroups = [
            'Overview' => [
                ['route' => 'dashboard', 'path' => '/dashboard', 'label' => 'Dashboard', 'icon' => 'layout-dashboard'],
            ],
            'Modules' => [
                ['route' => 'employees.index', 'path' => '/employees', 'label' => 'Employees', 'icon' => 'user'],
                ['route' => 'onboarding', 'path' => '/onboarding', 'label' => 'Onboarding', 'icon' => 'user-plus'],
                ['route' => 'timekeeping.index', 'path' => '/timekeeping', 'label' => 'Timekeeping', 'icon' => 'clock'],
                ['route' => 'leave', 'path' => '/leave', 'label' => 'Leave', 'icon' => 'calendar-event'],
                ['label' => 'Salaries', 'icon' => 'coins', 'children' => [
                    ['route' => 'salary.index', 'path' => '/salaries', 'label' => 'Salary Records'],
                    ['route' => 'salary.settings', 'path' => '/salaries/settings', 'label' => 'Tax & Deductions'],
                ]],
                   ['route' => 'payroll.index', 'path' => '/payroll', 'label' => 'Payroll', 'icon' => 'wallet', 'children' => [
                    ['route' => 'payroll.plotting-payment', 'path' => '/payroll/plotting-payment', 'label' => 'Plotting of Payments'],
                ]],
                ['route' => 'benefits', 'path' => '/benefits', 'label' => 'Benefits', 'icon' => 'heartbeat'],
                ['route' => 'self-service', 'path' => '/self-service', 'label' => 'Self-Service', 'icon' => 'user-circle'],
                ['route' => 'reports', 'path' => '/reports', 'label' => 'Reports', 'icon' => 'chart-bar'],
            ],
        ];

        $organizationActive = request()->routeIs('organization.departments.*', 'organization.positions.*');
        $user = auth()->user();
        $userInitials = $user?->name
            ? collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))->implode('')
            : 'HR';
        $workspaceLabel = $header ?? ($title ?? 'Workspace');
    @endphp

    <div class="flex h-screen bg-transparent">
        <aside class="sidebar fixed left-0 top-0 z-40 flex h-full flex-col border-r border-slate-200 bg-white px-3 py-3 text-slate-700 shadow-[0_1px_3px_rgba(15,23,42,0.08)]">
            <div class="sidebar-scroll flex flex-1 flex-col overflow-y-auto pb-3">
                <nav class="space-y-1">
                    @foreach ($navGroups as $groupName => $items)
                        <p class="sidebar-group-label px-2 pt-3 pb-2 text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ $groupName }}</p>

                        @foreach ($items as $item)
                            @if (isset($item['children']))
                                @php
                                    $parentRouteExists = isset($item['route']) && \Illuminate\Support\Facades\Route::has($item['route']);
                                    $parentHref = $parentRouteExists ? route($item['route']) : url($item['path'] ?? '#');
                                    $hasActiveChild = collect($item['children'])->contains(function ($child) {
                                        $childRouteExists = \Illuminate\Support\Facades\Route::has($child['route']);
                                        if ($childRouteExists) {
                                            return request()->routeIs($child['route']) || request()->routeIs($child['route'].'.*') || ($child['route'] === 'payroll.plotting-payment' && (request()->routeIs('payroll.work-location-details') || request()->routeIs('payroll.per-date')));
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
                                            @php
                                                $childRouteExists = \Illuminate\Support\Facades\Route::has($child['route']);
                                                $childIsActive = $childRouteExists
                                                    ? (request()->routeIs($child['route']) || request()->routeIs($child['route'].'.*') || ($child['route'] === 'payroll.plotting-payment' && (request()->routeIs('payroll.work-location-details') || request()->routeIs('payroll.per-date'))))
                                                    : request()->is(ltrim($child['path'], '/'));
                                                $childHref = $childRouteExists ? route($child['route']) : url($child['path']);
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

                    <p class="sidebar-group-label px-2 pt-4 pb-2 text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Organization</p>

                    @php
                        $departmentsRouteExists = \Illuminate\Support\Facades\Route::has('organization.departments.index');
                        $departmentsHref = $departmentsRouteExists ? route('organization.departments.index') : url('/organization/departments');
                        $departmentsActive = $departmentsRouteExists ? request()->routeIs('organization.departments.*') : request()->is('organization/departments*');

                        $positionsRouteExists = \Illuminate\Support\Facades\Route::has('organization.positions.index');
                        $positionsHref = $positionsRouteExists ? route('organization.positions.index') : url('/organization/positions');
                        $positionsActive = $positionsRouteExists ? request()->routeIs('organization.positions.*') : request()->is('organization/positions*');

                        $usersRouteExists = \Illuminate\Support\Facades\Route::has('organization.users.index');
                        $usersHref = $usersRouteExists ? route('organization.users.index') : url('/organization/users');
                        $usersActive = $usersRouteExists ? request()->routeIs('organization.users.*') : request()->is('organization/users*');
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

                    <a href="{{ $positionsHref }}" class="sidebar-link {{ $positionsActive ? 'sidebar-link-active' : '' }}">
                        <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center">
                            <i class="ti ti-sitemap sidebar-icon text-xl"></i>
                        </span>
                        <span class="sidebar-nav-label whitespace-nowrap font-medium">Positions</span>
                    </a>
                </nav>
            </div>

            <div class="mt-auto pt-4">
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <button type="submit" class="logout-button flex w-full items-center rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-slate-700 transition hover:bg-slate-50">
                        <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center">
                            <i class="ti ti-logout sidebar-icon text-xl"></i>
                        </span>
                        <span class="sidebar-nav-label whitespace-nowrap font-medium">Logout</span>
                    </button>
                </form>
            </div>
        </aside>

        <main class="main-content flex-1 h-screen overflow-y-auto transition-[padding-left] duration-300 ease-in-out">
            <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 pl-4 pr-5 py-3.5 backdrop-blur-md sm:pl-5 sm:pr-8">
                <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-2 text-slate-500">
                        <button id="sidebar-toggle" type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-200" aria-label="Toggle Sidebar">
                            <i class="ti ti-layout-sidebar text-[1.15rem]"></i>
                        </button>
                        <h1 class="text-[1.05rem] font-medium text-slate-700">{{ $workspaceLabel }}</h1>
                    </div>

                    <div class="flex items-center gap-3">
                        <button class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:bg-slate-50" type="button" aria-label="Notifications">
                            <i class="ti ti-bell text-xl"></i>
                        </button>
                        <div class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white shadow-sm">
                            {{ $userInitials }}
                        </div>
                    </div>
                </div>
            </header>

            <div class="p-4 sm:p-5 lg:p-6">
                <div class="space-y-5">{{ $slot }}</div>
            </div>
        </main>
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
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    document.body.classList.toggle('sidebar-collapsed');
                });
            }
        });
    </script>
</body>
</html>
