@props([
    'showGlobalAlerts' => false,
])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'HR System' }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans text-slate-900 bg-slate-50">
    <!-- Global Toast Container -->
    <div id="toast-container" class="fixed top-5 right-5 z-[9999] flex flex-col gap-3 pointer-events-none max-w-sm w-full">
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
                ]],
                ['label' => 'Payroll', 'icon' => 'wallet', 'path' => '/payroll', 'permission' => 'payroll.view,payroll.create,payroll.edit,payroll.delete', 'children' => [
                    ['route' => 'payroll.index',                 'path' => '/payroll',                   'label' => 'Payroll Run'],
                    ['route' => 'payroll.plotting-payment',      'path' => '/payroll/plotting-payment',  'label' => 'Plotting of Payments'],
                    ['route' => 'payroll.previous-claims.index', 'path' => '/payroll/previous-claims',   'label' => 'Previous Claims'],
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
                                                $childIsActive = $childRouteExists
                                                    ? (request()->routeIs($child['route']) || request()->routeIs($child['route'].'.*') || ($child['route'] === 'payroll.plotting-payment' && (request()->routeIs('payroll.work-location-details') || request()->routeIs('payroll.per-date'))) || ($child['route'] === 'payroll.index' && request()->routeIs('payroll.show', 'payroll.edit')))
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

                    <a href="{{ $settingsHref }}" class="sidebar-link {{ $settingsActive ? 'sidebar-link-active' : '' }}">
                        <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center">
                            <i class="ti ti-settings sidebar-icon text-xl"></i>
                        </span>
                        <span class="sidebar-nav-label whitespace-nowrap font-medium">Settings</span>
                    </a>
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

        <main class="main-content relative flex-1 h-screen overflow-y-auto transition-[padding-left] duration-300 ease-in-out">
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
                        <a href="{{ route('profile.show') }}" class="inline-flex h-10 w-10 items-center justify-center rounded-full overflow-hidden border border-slate-200 bg-blue-600 text-sm font-bold text-white shadow-sm transition hover:opacity-90 hover:scale-105" title="View Profile">
                            @if ($user && $user->employee && $user->employee->profile_picture)
                                <img src="{{ asset('storage/' . $user->employee->profile_picture) }}" alt="Profile" class="h-full w-full object-cover">
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

    <div id="logoutModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/40 p-4">
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white shadow-xl">
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

    <div id="confirmModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/40 p-4 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white shadow-xl overflow-hidden">
            <div class="px-6 py-6">
                <div class="flex flex-col items-center text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-50 text-amber-600 mb-3">
                        <i class="ti ti-alert-triangle text-2xl"></i>
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
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    document.body.classList.toggle('sidebar-collapsed');
                });
            }

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

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && logoutModal && logoutModal.classList.contains('flex')) {
                    closeLogoutModal();
                }
            });

            // Confirm Modal
            let __pendingConfirmForm = null;

            const confirmModal = document.getElementById('confirmModal');
            const confirmCancel = document.getElementById('confirmCancel');
            const confirmProceed = document.getElementById('confirmProceed');
            const confirmModalTitle = document.getElementById('confirmModalTitle');
            const confirmModalMessage = document.getElementById('confirmModalMessage');

            function openConfirmModal(title, message) {
                if (!confirmModal) return;
                confirmModalTitle.innerHTML = title || 'Confirm Action';
                confirmModalMessage.innerHTML = message || 'Are you sure you want to proceed?';
                confirmModal.classList.remove('hidden');
                confirmModal.classList.add('flex');
            }

            function closeConfirmModal() {
                if (!confirmModal) return;
                confirmModal.classList.remove('flex');
                confirmModal.classList.add('hidden');
            }

            function initConfirmModal() {
                document.querySelectorAll('[data-confirm]').forEach(function (el) {
                    el.addEventListener('click', function (e) {
                        const message = el.getAttribute('data-confirm') || 'Are you sure you want to proceed?';
                        const title = el.getAttribute('data-confirm-title') || 'Confirm Action';
                        const form = el.closest('form');

                        if (!form) return;

                        e.preventDefault();
                        __pendingConfirmForm = form;
                        confirmModalTitle.innerHTML = title;
                        confirmModalMessage.innerHTML = message;
                        confirmModal.classList.remove('hidden');
                        confirmModal.classList.add('flex');
                    });
                });

                if (confirmCancel) {
                    confirmCancel.addEventListener('click', function () {
                        closeConfirmModal();
                        __pendingConfirmForm = null;
                    });
                }

                if (confirmProceed) {
                    confirmProceed.addEventListener('click', function () {
                        if (__pendingConfirmForm) {
                            __pendingConfirmForm.submit();
                        }
                    });
                }

                if (confirmModal) {
                    confirmModal.addEventListener('click', function (event) {
                        if (event.target === confirmModal) {
                            closeConfirmModal();
                            __pendingConfirmForm = null;
                        }
                    });
                }

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' && confirmModal && confirmModal.classList.contains('flex')) {
                        closeConfirmModal();
                        __pendingConfirmForm = null;
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
