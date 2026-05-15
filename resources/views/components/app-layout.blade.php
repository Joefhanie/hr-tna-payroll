<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'HR System' }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            --sidebar-width: 5rem;
        }

        body:has(.sidebar:hover) {
            --sidebar-width: 18rem;
        }

        html,
        body {
            overflow-x: hidden;
        }

        details > .organization-submenu {
            display: none;
        }

        details[open] > .organization-submenu {
            display: block;
            margin-top: 0.25rem;
            position: relative;
            padding-left: 0;
        }

        /* vertical separator line to the left of submenu text */
        details.organization-menu[open] > .organization-submenu::before {
            content: "";
            position: absolute;
            left: 1.125rem; /* adjust position to sit just before text */
            top: 0;
            bottom: 0;
            width: 1px;
            background: rgba(226,232,240,1); /* slate-200 */
        }

        details .organization-arrow {
            transition: transform 300ms ease;
        }

        details[open] .organization-arrow {
            transform: rotate(180deg);
        }

        details > summary::-webkit-details-marker {
            display: none;
        }

        .sidebar {
            width: var(--sidebar-width);
        }

        .sidebar-scroll {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .sidebar-scroll::-webkit-scrollbar {
            display: none;
        }

        .sidebar nav > a,
        .sidebar nav > details > summary,
        .sidebar .logout-button {
            justify-content: center;
            gap: 0;
            padding-left: 0;
            padding-right: 0;
        }

        body:has(.sidebar:hover) .sidebar nav > a,
        body:has(.sidebar:hover) .sidebar nav > details > summary,
        body:has(.sidebar:hover) .sidebar .logout-button {
            justify-content: flex-start;
            gap: 0.75rem;
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }

        .main-content {
            margin-left: 0;
            padding-left: var(--sidebar-width);
            width: 100%;
        }
    </style>
</head>
<body class="min-h-screen font-sans text-slate-900">
    <div class="flex min-h-screen bg-transparent">
        <!-- Sidebar -->
        <aside class="sidebar group fixed left-0 top-0 z-40 flex h-screen flex-col overflow-hidden border-r border-slate-200 bg-white p-3 text-slate-700 shadow-sm transition-[width,padding] duration-300 ease-in-out">
            <div class="sidebar-scroll flex flex-1 flex-col overflow-y-auto pb-4">

                <!-- Navigation -->
                <nav class="space-y-2">
                    <p class="hidden px-3 pt-3 pb-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 group-hover:block">Overview</p>
                    <a href="{{ route('dashboard') }}" class="flex w-full items-center rounded-xl px-3 py-3 text-slate-700 transition hover:bg-slate-200 hover:text-slate-900 {{ request()->routeIs('dashboard') ? 'bg-slate-100 text-slate-800 shadow-sm ring-1 ring-slate-200' : '' }}">
                        <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center">
                            <i class="fas fa-home text-base"></i>
                        </span>
                        <span class="hidden whitespace-nowrap font-medium group-hover:inline">Dashboard</span>
                    </a>

                    <p class="hidden px-3 pt-3 pb-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 group-hover:block">Modules</p>

                    <a href="{{ route('employees.index') }}" class="flex w-full items-center rounded-xl px-3 py-3 text-slate-700 transition hover:bg-slate-200 hover:text-slate-900 {{ request()->routeIs('employees.*') ? 'bg-slate-100 text-slate-800 shadow-sm ring-1 ring-slate-200' : '' }}">
                        <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center">
                            <i class="fas fa-user text-base"></i>
                        </span>
                        <span class="hidden whitespace-nowrap font-medium group-hover:inline">Employees</span>
                    </a>

                    <a href="{{ route('payroll.index') }}" class="flex w-full items-center rounded-xl px-3 py-3 text-slate-700 transition hover:bg-slate-200 hover:text-slate-900 {{ request()->routeIs('payroll.*') ? 'bg-slate-100 text-slate-800 shadow-sm ring-1 ring-slate-200' : '' }}">
                        <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center">
                            <i class="fas fa-money-bill text-base"></i>
                        </span>
                        <span class="hidden whitespace-nowrap font-medium group-hover:inline">Payroll</span>
                    </a>

                    <a href="{{ route('timekeeping.index') }}" class="flex w-full items-center rounded-xl px-3 py-3 text-slate-700 transition hover:bg-slate-200 hover:text-slate-900 {{ request()->routeIs('timekeeping.*') ? 'bg-slate-100 text-slate-800 shadow-sm ring-1 ring-slate-200' : '' }}">
                        <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center">
                            <i class="fas fa-clock text-base"></i>
                        </span>
                        <span class="hidden whitespace-nowrap font-medium group-hover:inline">Timekeeping</span>
                    </a>

                    <details class="organization-menu rounded-xl">
                        <summary class="flex w-full cursor-pointer list-none items-center rounded-xl px-3 py-3 text-slate-700 transition hover:bg-slate-200 hover:text-slate-900 {{ request()->routeIs('organization.departments.*', 'organization.positions.*') ? 'bg-slate-100 text-slate-800 ring-1 ring-slate-200' : '' }}">
                            <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center">
                                <i class="fas fa-building text-base"></i>
                            </span>
                            <span class="hidden flex-1 whitespace-nowrap font-medium group-hover:block {{ request()->routeIs('organization.departments.*', 'organization.positions.*') ? 'font-semibold text-slate-800' : '' }}">Organization</span>
                            <span class="organization-arrow hidden text-xs text-slate-500 group-hover:block">▼</span>
                        </summary>

                        <div class="organization-submenu mx-3 mb-2 mt-1 space-y-1">
                            <a href="{{ route('organization.departments.index') }}" class="flex items-center rounded-lg px-8 py-2 text-sm text-slate-700 transition hover:bg-slate-200 hover:text-slate-900 {{ request()->routeIs('organization.departments.*') ? 'font-bold text-slate-800' : '' }}">
                                <span>Departments</span>
                            </a>

                            <a href="{{ route('organization.positions.index') }}" class="flex items-center rounded-lg px-8 py-2 text-sm text-slate-700 transition hover:bg-slate-200 hover:text-slate-900 {{ request()->routeIs('organization.positions.*') ? 'font-semibold text-slate-800' : '' }}">
                                <span>Positions</span>
                            </a>
                        </div>
                    </details>
                </nav>
            </div>

            <!-- Footer: logout pinned to bottom -->
            <div class="mt-auto pt-4">
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <button type="submit" class="logout-button flex w-full items-center rounded-xl border border-slate-200 bg-white px-3 py-3 text-slate-700 transition hover:bg-slate-100">
                        <svg class="h-6 w-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        <span class="hidden whitespace-nowrap font-medium group-hover:inline">Logout</span>
                    </button>
                </form>
            </div>
            @foreach ($groups as $groupName => $items)
                <div class="mt-4">
                    <p class="sidebar-group-label px-3 text-xs font-medium uppercase tracking-wide text-slate-400 mb-1">{{ $groupName }}</p>
                    <nav class="space-y-1">
                        @foreach ($items as $item)
                            <a href="{{ route($item['route']) }}" class="nav-link sidebar-nav-link {{ request()->routeIs($item['route']) ? 'active' : '' }}">
                                <span class="w-4 shrink-0 text-center"><i class="fas fa-{{ $item['icon'] }}"></i></span>
                                <span class="sidebar-nav-label">{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </nav>
                </div>
            @endforeach
        </aside>

        <!-- Main Content -->
        <main class="main-content flex-1 overflow-y-auto transition-[padding-left] duration-300 ease-in-out">
            <!-- Top Bar -->
            <div class="sticky top-0 z-20 border-b border-slate-200/80 bg-white px-8 py-5 backdrop-blur-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Workspace</p>
                        <h1 class="text-3xl font-extrabold text-slate-900">{{ $header ?? '' }}</h1>
                    </div>
                    <div class="flex items-center gap-3">
                        <button class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:bg-slate-50" type="button" aria-label="Notifications">
                            <i class="fas fa-bell text-sm"></i>
                        </button>
                        <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-sky-100 text-sm font-bold text-sky-700">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button class="btn-outline text-sm" type="button">🔔</button>
                    <div class="h-8 w-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-semibold">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div>
                </div>
            </header>

            <main class="flex-1 p-6 space-y-6">{{ $slot }}</main>
        </div>
    </div>
    {{ $scripts ?? '' }}

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.querySelector('.sidebar');
            if (!sidebar) return;

            // When the sidebar collapses (mouse leaves), close any open organization dropdowns
            sidebar.addEventListener('mouseleave', function () {
                document.querySelectorAll('details.organization-menu[open]').forEach(function (d) {
                    d.removeAttribute('open');
                });
            });

            // Optional: ensure dropdowns can open on click when sidebar is expanded
            sidebar.addEventListener('mouseenter', function () {
                // no-op: allow user to click to open
            });
        });
    </script>
</body>
</html>
