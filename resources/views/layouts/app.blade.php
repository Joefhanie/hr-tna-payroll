<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>@yield('title', 'HR System')</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #4f46e5;
      --bg: #f8fafc;
      --muted: #f1f5f9;
      --border: #e2e8f0;
      --fg: #0f172a;
      --muted-fg: #64748b;
    }
    body { background: var(--bg); color: var(--fg); font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
    .card { background: #fff; border: 1px solid var(--border); border-radius: 12px; }
    .btn-primary { background: var(--primary); color: #fff; padding: .5rem 1rem; border-radius: 8px; font-size: .875rem; font-weight: 500; cursor: pointer; border: none; }
    .btn-primary:hover { opacity: 0.9; }
    .btn-outline { border: 1px solid var(--border); padding: .5rem 1rem; border-radius: 8px; font-size: .875rem; background: #fff; cursor: pointer; }
    .btn-outline:hover { background: var(--muted); }
    .nav-link { display: flex; align-items: center; gap: .625rem; padding: .5rem .75rem; border-radius: 8px; color: var(--muted-fg); font-size: .875rem; text-decoration: none; }
    .nav-link:hover { background: var(--muted); color: var(--fg); }
    .nav-link.active { background: #eef2ff; color: var(--primary); font-weight: 500; }
    .badge { display: inline-flex; align-items: center; padding: .125rem .5rem; border-radius: 9999px; font-size: .75rem; font-weight: 500; }
    .badge-green { background: #dcfce7; color: #166534; }
    .badge-amber { background: #fef3c7; color: #92400e; }
    .badge-red   { background: #fee2e2; color: #991b1b; }
    .badge-blue  { background: #dbeafe; color: #1e40af; }
    .badge-gray  { background: #f1f5f9; color: #475569; }
  </style>
</head>
<body>
  @php
    $nav = [
      ['url' => route('dashboard'),     'label' => 'Dashboard',     'group' => 'Overview'],
      ['url' => route('employees.index'), 'label' => 'Employees',    'group' => 'Modules'],
      ['url' => route('onboarding'),   'label' => 'Onboarding',    'group' => 'Modules'],
      ['url' => route('timekeeping'),  'label' => 'Timekeeping',   'group' => 'Modules'],
      ['url' => route('leave'),        'label' => 'Leave',         'group' => 'Modules'],
      ['url' => route('payroll.index'),      'label' => 'Payroll',       'group' => 'Modules'],
      ['url' => route('benefits'),     'label' => 'Benefits',      'group' => 'Modules'],
      ['url' => route('self-service'), 'label' => 'Self-Service',  'group' => 'Modules'],
      ['url' => route('reports'),      'label' => 'Reports',       'group' => 'Modules'],
    ];
    $groups = collect($nav)->groupBy('group');
    $current = url()->current();
  @endphp

  <div class="min-h-screen flex w-full">
    {{-- Sidebar --}}
    <aside class="w-60 shrink-0 border-r border-slate-200 bg-white p-4 hidden md:block">
      <div class="flex items-center gap-2 px-2 py-3">
        <div class="h-8 w-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold">N</div>
        <div>
          <p class="text-sm font-semibold">Northwind HR</p>
          <p class="text-xs text-slate-500">People Operations</p>
        </div>
      </div>

      @foreach ($groups as $groupName => $items)
        <div class="mt-4">
          <p class="px-3 text-xs font-medium uppercase tracking-wide text-slate-400 mb-1">{{ $groupName }}</p>
          <nav class="space-y-1">
            @foreach ($items as $item)
              <a href="{{ $item['url'] }}"
                 class="nav-link {{ $current === $item['url'] ? 'active' : '' }}">
                <span class="h-1.5 w-1.5 rounded-full bg-current opacity-60"></span>
                {{ $item['label'] }}
              </a>
            @endforeach
          </nav>
        </div>
      @endforeach
    </aside>

    {{-- Main --}}
    <div class="flex-1 flex flex-col min-w-0">
      <header class="h-14 flex items-center justify-between border-b border-slate-200 bg-white px-6">
        <div class="flex items-center gap-3">
          <input type="search" placeholder="Search..."
                 class="w-72 max-w-sm rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-200" />
        </div>
        <div class="flex items-center gap-3">
          <button class="btn-outline text-sm">🔔</button>
          <div class="h-8 w-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-semibold">AR</div>
        </div>
      </header>

      <main class="flex-1 p-6 space-y-6">
        @yield('content')
      </main>
    </div>
  </div>
</body>
</html>
