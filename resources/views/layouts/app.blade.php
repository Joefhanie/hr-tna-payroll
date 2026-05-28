<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  @php
      $themeSettings = \App\Models\CompanySetting::current();
        $faviconUrl = $themeSettings && $themeSettings->logo_path
          ? route('media.file', ['path' => ltrim($themeSettings->logo_path, '/')])
          : asset('favicon.ico');
  @endphp
  <title>@yield('title', 'HR System')</title>
  <link rel="icon" href="{{ $faviconUrl }}">
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
    body { background: var(--bg); color: var(--fg); font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; overflow: hidden; height: 100vh; }
    .card { background: #fff; border: 1px solid var(--border); border-radius: 12px; }
    .btn-primary { background: var(--primary); color: #fff; padding: .5rem 1rem; border-radius: 8px; font-size: .875rem; font-weight: 500; cursor: pointer; border: none; }
    .btn-primary:hover { opacity: 0.9; }
    .btn-outline { border: 1px solid #c7d2fe; padding: .5rem 1rem; border-radius: 8px; font-size: .875rem; background: #eef2ff; color: var(--primary); cursor: pointer; }
    .btn-outline:hover { background: #e0e7ff; }
    .nav-link { display: flex; align-items: center; gap: .625rem; padding: .5rem .75rem; border-radius: 8px; color: var(--muted-fg); font-size: .875rem; text-decoration: none; }
    .nav-link:hover { background: var(--muted); color: var(--fg); }
    .nav-link.active { background: #eef2ff; color: var(--primary); font-weight: 500; }
    .nav-link-sub { display: flex; align-items: center; gap: .5rem; padding: .45rem .75rem .45rem 1.25rem; border-radius: 8px; color: var(--muted-fg); font-size: .84rem; text-decoration: none; }
    .nav-link-sub:hover { background: var(--muted); color: var(--fg); }
    .nav-link-sub.active { background: #eef2ff; color: var(--primary); font-weight: 500; }
    .badge { display: inline-flex; align-items: center; padding: .125rem .5rem; border-radius: 9999px; font-size: .75rem; font-weight: 500; }
    .badge-green   { background: #dcfce7; color: #166534; }
    .badge-amber   { background: #fef3c7; color: #92400e; }
    .badge-red     { background: #fee2e2; color: #991b1b; }
    .badge-blue    { background: #dbeafe; color: #1e40af; }
    .badge-gray    { background: #f1f5f9; color: #475569; }
    .badge-indigo   { background: #e0e7ff; color: #3730a3; }
    .badge-purple   { background: #f3e8ff; color: #6b21a8; }
    .badge-emerald  { background: #d1fae5; color: #065f46; }
    .badge-rose     { background: #ffe4e6; color: #9f1239; }
    .badge-yellow   { background: #fef9c3; color: #854d0e; }
  </style>
</head>
<body>
  @php
    $user = auth()->user();
    $nav = [
      ['url' => route('dashboard'),     'label' => 'Dashboard',     'group' => 'Overview'],
      ['label' => 'Employees', 'group' => 'Modules', 'permission' => 'employees.view,employees.create,employees.edit,employees.delete', 'children' => [
        ['url' => route('employees.index'),           'label' => 'Employee List'],
        ['url' => route('employees.temporary-access'), 'label' => 'Temporary Access', 'roles' => [4]],
      ]],
      ['url' => route('onboarding'),   'label' => 'Onboarding',    'group' => 'Modules', 'permission' => 'onboarding.view'],
      ['label' => 'Timekeeping', 'group' => 'Modules', 'permission' => 'timekeeping.view,timekeeping.create,timekeeping.edit,timekeeping.delete', 'children' => [
        ['url' => route('timekeeping.index'),          'label' => 'Attendance'],
        ['url' => route('timekeeping.shift-schedule'), 'label' => 'Shift Schedule'],
      ]],
      ['url' => route('leave'),        'label' => 'Leave',         'group' => 'Modules', 'permission' => 'leaves.view,leaves.create,leaves.edit,leaves.delete'],
      ['label' => 'Salaries', 'group' => 'Modules', 'permission' => 'payroll.view,payroll.create,payroll.edit,payroll.delete', 'children' => [
        ['url' => route('salary.index'), 'label' => 'Salary Records'],
        ['url' => route('salary.settings'), 'label' => 'Salary Settings'],
      ]],
      ['label' => 'Payroll', 'group' => 'Modules', 'permission' => 'payroll.view,payroll.create,payroll.edit,payroll.delete', 'children' => [
        ['url' => route('payroll.index'),                                                                        'label' => 'Payroll Runs'],
        ['url' => route('payroll.plotting-payment'),                                                             'label' => 'Plotting of Payments'],
        ['url' => route('payroll.previous-claims.index'),                                                        'label' => 'Previous Claims'],
        ['url' => route('payroll.previous-claims.index', ['type' => 'Overtime']),                                'label' => 'Overtime Requests'],
        ['url' => route('payroll.previous-claims.index', ['type' => 'Night Differential']),                      'label' => 'Night Differential Requests'],
        ['url' => route('payroll.disputes.index'),                                                               'label' => 'Disputes'],
      ]],
      ['url' => route('benefits'),     'label' => 'Benefits',      'group' => 'Modules', 'permission' => 'benefits.view,benefits.create,benefits.edit,benefits.delete'],
      ['url' => route('self-service'), 'label' => 'Self-Service',  'group' => 'Modules', 'permission' => 'self-service.view'],
      ['url' => route('reports'),      'label' => 'Reports',       'group' => 'Modules', 'permission' => 'reports.view,reports.create,reports.edit,reports.delete'],
    ];

    $nav = array_filter($nav, function ($item) use ($user) {
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

    $groups = collect($nav)->groupBy('group');
    $current = url()->current();
    $currentPath = request()->path();

    $isActive = function ($url) use ($currentPath) {
      $parsedLink = parse_url($url);
      $linkPath = ltrim($parsedLink['path'] ?? '', '/');
      if ($currentPath !== $linkPath && !str_starts_with($currentPath, $linkPath . '/')) {
        return false;
      }
      if (isset($parsedLink['query'])) {
        parse_str($parsedLink['query'], $linkQuery);
        foreach ($linkQuery as $key => $val) {
          if (request()->query($key) !== $val) {
            return false;
          }
        }
      } else {
        if (request()->has('type') && !str_contains($url, 'type=')) {
          return false;
        }
      }
      return true;
    };
  @endphp

  <div class="h-screen flex w-full">
    {{-- Sidebar --}}
    <aside class="w-60 shrink-0 border-r border-slate-200 bg-white p-4 hidden md:block h-full overflow-y-auto">
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
              @if (isset($item['children']))
                @php
                  $hasActiveChild = collect($item['children'])->contains(
                    fn ($child) => $isActive($child['url'])
                  );
                @endphp
                <details class="group" @if ($hasActiveChild) open @endif>
                  <summary class="nav-link cursor-pointer list-none {{ $hasActiveChild ? 'active' : '' }}">
                    <span class="h-1.5 w-1.5 rounded-full bg-current opacity-60"></span>
                    <span class="flex-1">{{ $item['label'] }}</span>
                    <span class="text-xs text-slate-400 group-open:rotate-180">▾</span>
                  </summary>
                  <div class="mt-1 space-y-1 pl-2">
                    @foreach ($item['children'] as $child)
                      @if(isset($child['roles']) && !in_array($user->role ?? 0, $child['roles']))
                          @continue
                      @endif
                      <a href="{{ $child['url'] }}" class="nav-link-sub {{ $isActive($child['url']) ? 'active' : '' }}">
                        <span class="h-1 w-1 rounded-full bg-current opacity-50"></span>
                        {{ $child['label'] }}
                      </a>
                    @endforeach
                  </div>
                </details>
              @else
                <a href="{{ $item['url'] }}"
                   class="nav-link {{ $isActive($item['url']) ? 'active' : '' }}">
                  <span class="h-1.5 w-1.5 rounded-full bg-current opacity-60"></span>
                  {{ $item['label'] }}
                </a>
              @endif
            @endforeach
          </nav>
        </div>
      @endforeach
    </aside>

    {{-- Main --}}
    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
      <header class="h-14 flex shrink-0 items-center justify-between border-b border-slate-200 bg-white px-6">
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
