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
<body class="min-h-screen overflow-x-hidden font-sans text-slate-900">
    @php
        $navGroups = [
            'Overview' => [
                ['route' => 'dashboard', 'path' => '/dashboard', 'label' => 'Dashboard', 'icon' => 'layout-dashboard'],
            ],
            'Modules' => [
                ['route' => 'employees.index', 'path' => '/employees', 'label' => 'Employees', 'icon' => 'user'],
                ['route' => 'salary.index', 'path' => '/salaries', 'label' => 'Salaries', 'icon' => 'coins'],
                ['route' => 'onboarding', 'path' => '/onboarding', 'label' => 'Onboarding', 'icon' => 'clipboard-user'],
                ['route' => 'timekeeping.index', 'path' => '/timekeeping', 'label' => 'Timekeeping', 'icon' => 'clock'],
                ['route' => 'leave', 'path' => '/leave', 'label' => 'Leave', 'icon' => 'calendar-event'],
                ['route' => 'payroll.index', 'path' => '/payroll', 'label' => 'Payroll', 'icon' => 'wallet'],
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

    <div class="flex min-h-screen bg-transparent">
        <aside class="sidebar fixed left-0 top-0 z-40 flex h-screen flex-col border-r border-slate-200 bg-white px-3 py-3 text-slate-700 shadow-[0_1px_3px_rgba(15,23,42,0.08)]">
            <div class="sidebar-scroll flex flex-1 flex-col overflow-y-auto pb-3">
                <nav class="space-y-1">
                    @foreach ($navGroups as $groupName => $items)
                        <p class="sidebar-group-label px-2 pt-3 pb-2 text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ $groupName }}</p>

                        @foreach ($items as $item)
                            @php
                                $routeExists = \Illuminate\Support\Facades\Route::has($item['route']);
                                $isActive = $routeExists ? request()->routeIs($item['route']) : request()->is(ltrim($item['path'], '/'));
                                $href = $routeExists ? route($item['route']) : url($item['path']);
                            @endphp
                            <a href="{{ $href }}" class="sidebar-link {{ $isActive ? 'sidebar-link-active' : '' }}">
                                <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center">
                                    <i class="ti ti-{{ $item['icon'] }} sidebar-icon text-xl"></i>
                                </span>
                                <span class="sidebar-nav-label whitespace-nowrap font-medium">{{ $item['label'] }}</span>
                            </a>
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
                    @endphp

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

        <main class="main-content flex-1 overflow-y-auto transition-[padding-left] duration-300 ease-in-out">
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
