<x-app-layout>
    <x-slot:title>Shift Schedule</x-slot:title>
    <x-slot:header>Shift Schedule</x-slot:header>

    @php
        $canCreateShifts = auth()->user() && auth()->user()->hasPermission('timekeeping.create');
        $canEditShifts = auth()->user() && auth()->user()->hasPermission('timekeeping.edit');
        $canManageShifts = $canCreateShifts || $canEditShifts;
    @endphp

    <style>
        /* Force styling for checked day pills without relying on Tailwind compiler */
        input[type="checkbox"]:checked + span.day-pill {
            border-color: #3b82f6 !important;
            background-color: #eff6ff !important;
            color: #1d4ed8 !important;
        }

        /* Style for disabled already scheduled day pills */
        .day-pill-disabled-scheduled {
            border-color: #d97706 !important; /* amber-600 */
            background-color: #fef3c7 !important; /* amber-100 */
            color: #b45309 !important; /* amber-700 */
            cursor: not-allowed !important;
            pointer-events: none !important;
            opacity: 0.95;
        }

        /* Reset modal sidebar offset on mobile/tablet (sidebar is collapsed < 1024px) */
        @media (max-width: 1023px) {
            #addShiftModal,
            #editShiftModal {
                padding-left: 0 !important;
            }
        }
    </style>

    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Shift Schedule</h1>
                <p class="mt-1 text-sm text-slate-600">Manage employee shift schedules.</p>
            </div>
            @if($canCreateShifts)
                <button type="button" onclick="openAddShiftModal()" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                    <i class="ti ti-plus"></i>
                    Add Shift
                </button>
            @endif
        </div>

        <!-- Desktop View -->
        <div class="hidden lg:block overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table id="shiftScheduleTable" class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">CODE</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">EMPLOYEE NAME</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">DEPARTMENT</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">DEFAULT SHIFT</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">WORKING DAYS</th>
                            @if($canCreateShifts)
                                <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-slate-500">ACTIONS</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse($employees as $employee)
                            <tr class="hover:bg-slate-50/50 transition-colors group" id="row-{{ $employee->id }}">
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-500">
                                    {{ $employee->employee_code }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-900">
                                    {{ $employee->full_name }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">
                                    {{ $employee->department->name ?? 'Unassigned' }}
                                </td>

                                <!-- VIEW MODE -->
                                <td class="whitespace-nowrap px-4 py-3 view-mode-{{ $employee->id }}">
                                    <div class="shift-container flex flex-col gap-2">
                                        @php
                                            $activeShifts = $employee->currentShifts ?? collect();
                                            if ($activeShifts->isEmpty() && $employee->currentShift) {
                                                $activeShifts = collect([$employee->currentShift]);
                                            }

                                            // Predefined premium translucent color palettes
                                            $palettes = [
                                                [
                                                    'time_bg' => 'bg-blue-100 text-blue-800 border-blue-200',
                                                    'time_icon' => 'text-blue-600',
                                                    'day_pill' => 'bg-blue-50 text-blue-700 border-blue-200 font-semibold'
                                                ],
                                                [
                                                    'time_bg' => 'bg-rose-100 text-rose-800 border-rose-200',
                                                    'time_icon' => 'text-rose-600',
                                                    'day_pill' => 'bg-rose-50 text-rose-700 border-rose-200 font-semibold'
                                                ],
                                                [
                                                    'time_bg' => 'bg-amber-100 text-amber-900 border-amber-200',
                                                    'time_icon' => 'text-amber-600',
                                                    'day_pill' => 'bg-amber-50 text-amber-700 border-amber-200 font-semibold'
                                                ],
                                                [
                                                    'time_bg' => 'bg-violet-100 text-violet-800 border-violet-200',
                                                    'time_icon' => 'text-violet-600',
                                                    'day_pill' => 'bg-violet-50 text-violet-700 border-violet-200 font-semibold'
                                                ],
                                                [
                                                    'time_bg' => 'bg-rose-100 text-rose-800 border-rose-200',
                                                    'time_icon' => 'text-rose-600',
                                                    'day_pill' => 'bg-rose-50 text-rose-700 border-rose-200 font-semibold'
                                                ],
                                                [
                                                    'time_bg' => 'bg-sky-100 text-sky-800 border-sky-200',
                                                    'time_icon' => 'text-sky-600',
                                                    'day_pill' => 'bg-sky-50 text-sky-700 border-sky-200 font-semibold'
                                                ],
                                            ];

                                            $dayColorMap = [];
                                            $shiftIndex = 0;
                                        @endphp
                                        @forelse($activeShifts as $assignment)
                                            @if($assignment->shift)
                                                @php
                                                    if ($assignment->shift->crosses_midnight) {
                                                        $palette = [
                                                            'time_bg' => 'bg-purple-100 text-purple-800 border-purple-200',
                                                            'time_icon' => 'text-purple-600',
                                                            'day_pill' => 'bg-purple-50 text-purple-700 border-purple-200 font-semibold'
                                                        ];
                                                    } else {
                                                        $palette = $palettes[$shiftIndex % count($palettes)];
                                                        $shiftIndex++;
                                                    }
                                                    foreach ($assignment->shift->days_of_week ?? [] as $d) {
                                                        $dayColorMap[$d] = $palette['day_pill'];
                                                    }
                                                @endphp
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium border time-badge {{ $palette['time_bg'] }}">
                                                        @if($assignment->shift->crosses_midnight)
                                                            <i class="ti ti-moon {{ $palette['time_icon'] }}"></i>
                                                        @else
                                                            <i class="ti ti-clock {{ $palette['time_icon'] }}"></i>
                                                        @endif
                                                        <span class="time-display">
                                                            @if($assignment->shift->is_flexible && $assignment->shift->flexible_until_time)
                                                                {{ \Carbon\Carbon::parse($assignment->shift->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($assignment->shift->flexible_until_time)->format('g:i A') }} to {{ \Carbon\Carbon::parse($assignment->shift->end_time)->format('g:i A') }}
                                                            @else
                                                                {{ \Carbon\Carbon::parse($assignment->shift->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($assignment->shift->end_time)->format('h:i A') }}
                                                            @endif
                                                        </span>
                                                    </span>
                                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-800 border border-amber-100 break-badge">
                                                        <i class="ti ti-coffee text-amber-500"></i>
                                                        <span class="break-display">
                                                            {{ $assignment->shift->break_minutes }}m
                                                        </span>
                                                    </span>
                                                    @if($assignment->shift->is_flexible)
                                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-800 border border-emerald-100" title="Flexible Shift">
                                                        <i class="ti ti-infinity text-emerald-500"></i>
                                                        <span>Flex</span>
                                                    </span>
                                                    @endif
                                                    @if($canEditShifts)
                                                        <!-- Edit Shift Segment Button -->
                                                        <button type="button" onclick="openEditModal({{ $employee->id }}, '{{ addslashes($employee->full_name) }}', {{ $assignment->id }}, '{{ $assignment->shift->start_time }}', '{{ $assignment->shift->end_time }}', {{ $assignment->shift->break_minutes }}, {{ json_encode($assignment->shift->days_of_week ?? []) }}, {{ $assignment->shift->is_flexible ? 'true' : 'false' }}, {{ $assignment->shift->flexible_until_time ? "'".substr($assignment->shift->flexible_until_time, 0, 5)."'" : 'null' }})" class="ml-1 inline-flex items-center justify-center rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-blue-600 transition-colors" title="Edit this shift segment">
                                                            <i class="ti ti-pencil text-sm"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            @endif
                                        @empty
                                            <span class="text-slate-400 italic text-xs no-shift">Not assigned</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-600 view-mode-{{ $employee->id }}">
                                    <div class="flex gap-1 days-display">
                                        @php
                                            $allActiveDays = [];
                                            foreach($activeShifts as $assignment) {
                                                if ($assignment->shift && is_array($assignment->shift->days_of_week)) {
                                                    $allActiveDays = array_merge($allActiveDays, $assignment->shift->days_of_week);
                                                }
                                            }
                                            $allActiveDays = array_unique($allActiveDays);
                                        @endphp
                                        @if(count($allActiveDays) > 0)
                                            @foreach(['Mon'=>'M', 'Tue'=>'T', 'Wed'=>'W', 'Thu'=>'T', 'Fri'=>'F', 'Sat'=>'S', 'Sun'=>'S'] as $day => $label)
                                                @if(in_array($day, $allActiveDays))
                                                    @php
                                                        $dayClass = $dayColorMap[$day] ?? 'bg-blue-100 text-blue-700 border-blue-200';
                                                    @endphp
                                                    <span class="flex h-6 w-6 items-center justify-center rounded text-xs border {{ $dayClass }}">{{ $label }}</span>
                                                @else
                                                    <span class="flex h-6 w-6 items-center justify-center rounded bg-slate-100 text-xs font-semibold text-slate-400 border border-slate-200/50">{{ $label }}</span>
                                                @endif
                                            @endforeach
                                        @else
                                            <span class="text-slate-400 italic text-xs no-days">Not assigned</span>
                                        @endif
                                    </div>
                                </td>
                                @if($canCreateShifts)
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-slate-600">
                                        @php
                                            $displayParts = [];
                                            foreach($activeShifts as $assignment) {
                                                if ($assignment->shift) {
                                                    if ($assignment->shift->is_flexible && $assignment->shift->flexible_until_time) {
                                                        $timeStr = \Carbon\Carbon::parse($assignment->shift->start_time)->format('g:i A') . ' - ' . \Carbon\Carbon::parse($assignment->shift->flexible_until_time)->format('g:i A') . ' to ' . \Carbon\Carbon::parse($assignment->shift->end_time)->format('g:i A');
                                                    } else {
                                                        $flex = $assignment->shift->is_flexible ? ' [Flex]' : '';
                                                        $timeStr = \Carbon\Carbon::parse($assignment->shift->start_time)->format('h:i A') . ' - ' . \Carbon\Carbon::parse($assignment->shift->end_time)->format('h:i A') . $flex;
                                                    }
                                                    $displayParts[] = $timeStr . ' (' . implode(', ', array_map(function($d) { return substr($d, 0, 3); }, $assignment->shift->days_of_week ?? [])) . ')';
                                                }
                                            }
                                        @endphp
                                        <button type="button" onclick="openAddModalPreselected({{ $employee->id }}, '{{ addslashes($employee->full_name) }}', '{{ implode('; ', $displayParts) }}', {{ json_encode(array_values($allActiveDays)) }})" class="action-icon inline-flex items-center justify-center rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-emerald-600 transition-colors" title="Add another shift segment">
                                            <i class="ti ti-plus text-lg"></i>
                                        </button>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canCreateShifts ? 6 : 5 }}" class="px-4 py-8 text-center text-slate-500">
                                    No employees found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-table-pagination target="shiftScheduleTable" />
        </div>

        <!-- Mobile View (Cards) -->
        <div class="block lg:hidden space-y-4">
            @forelse($employees as $employee)
                @php
                    $activeShifts = $employee->currentShifts ?? collect();
                    if ($activeShifts->isEmpty() && $employee->currentShift) {
                        $activeShifts = collect([$employee->currentShift]);
                    }
                    $allActiveDays = [];
                    foreach($activeShifts as $assignment) {
                        if ($assignment->shift && is_array($assignment->shift->days_of_week)) {
                            $allActiveDays = array_merge($allActiveDays, $assignment->shift->days_of_week);
                        }
                    }
                    $allActiveDays = array_unique($allActiveDays);
                @endphp
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 hover:border-blue-200 transition-colors" id="card-{{ $employee->id }}">
                    <!-- Card Header -->
                    <div class="flex items-start justify-between gap-2 border-b border-slate-100 pb-3 mb-3">
                        <div>
                            <div class="font-semibold text-slate-900 text-base">{{ $employee->full_name }}</div>
                            <div class="flex flex-wrap gap-2 items-center mt-1.5">
                                <span class="font-mono text-[10px] text-slate-500 bg-slate-50 px-1.5 py-0.5 rounded border border-slate-200">
                                    {{ $employee->employee_code }}
                                </span>
                                <span class="text-xs text-slate-600 bg-slate-100 px-2 py-0.5 rounded-full font-medium">
                                    {{ $employee->department->name ?? 'Unassigned' }}
                                </span>
                            </div>
                        </div>
                        @if($canCreateShifts)
                            @php
                                $displayParts = [];
                                foreach($activeShifts as $assignment) {
                                    if ($assignment->shift) {
                                        if ($assignment->shift->is_flexible && $assignment->shift->flexible_until_time) {
                                            $timeStr = \Carbon\Carbon::parse($assignment->shift->start_time)->format('g:i A') . ' - ' . \Carbon\Carbon::parse($assignment->shift->flexible_until_time)->format('g:i A') . ' to ' . \Carbon\Carbon::parse($assignment->shift->end_time)->format('g:i A');
                                        } else {
                                            $flex = $assignment->shift->is_flexible ? ' [Flex]' : '';
                                            $timeStr = \Carbon\Carbon::parse($assignment->shift->start_time)->format('h:i A') . ' - ' . \Carbon\Carbon::parse($assignment->shift->end_time)->format('h:i A') . $flex;
                                        }
                                        $displayParts[] = $timeStr . ' (' . implode(', ', array_map(function($d) { return substr($d, 0, 3); }, $assignment->shift->days_of_week ?? [])) . ')';
                                    }
                                }
                            @endphp
                            <button type="button" onclick="openAddModalPreselected({{ $employee->id }}, '{{ addslashes($employee->full_name) }}', '{{ implode('; ', $displayParts) }}', {{ json_encode(array_values($allActiveDays)) }})" class="inline-flex items-center justify-center rounded-lg bg-slate-50 p-2 text-slate-400 hover:bg-slate-100 hover:text-emerald-600 transition-colors" title="Add another shift segment">
                                <i class="ti ti-plus text-lg"></i>
                            </button>
                        @endif
                    </div>

                    <!-- Card Body -->
                    <div class="space-y-4">
                        <!-- Shifts List -->
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Shift Schedules</div>
                            <div class="shift-container flex flex-col gap-2">
                                @php
                                    // Predefined premium translucent color palettes
                                    $palettes = [
                                        [
                                            'time_bg' => 'bg-blue-100 text-blue-800 border-blue-200',
                                            'time_icon' => 'text-blue-600',
                                            'day_pill' => 'bg-blue-50 text-blue-700 border-blue-200 font-semibold'
                                        ],
                                        [
                                            'time_bg' => 'bg-rose-100 text-rose-800 border-rose-200',
                                            'time_icon' => 'text-rose-600',
                                            'day_pill' => 'bg-rose-50 text-rose-700 border-rose-200 font-semibold'
                                        ],
                                        [
                                            'time_bg' => 'bg-amber-100 text-amber-900 border-amber-200',
                                            'time_icon' => 'text-amber-600',
                                            'day_pill' => 'bg-amber-50 text-amber-700 border-amber-200 font-semibold'
                                        ],
                                        [
                                            'time_bg' => 'bg-violet-100 text-violet-800 border-violet-200',
                                            'time_icon' => 'text-violet-600',
                                            'day_pill' => 'bg-violet-50 text-violet-700 border-violet-200 font-semibold'
                                        ],
                                        [
                                            'time_bg' => 'bg-rose-100 text-rose-800 border-rose-200',
                                            'time_icon' => 'text-rose-600',
                                            'day_pill' => 'bg-rose-50 text-rose-700 border-rose-200 font-semibold'
                                        ],
                                        [
                                            'time_bg' => 'bg-sky-100 text-sky-800 border-sky-200',
                                            'time_icon' => 'text-sky-600',
                                            'day_pill' => 'bg-sky-50 text-sky-700 border-sky-200 font-semibold'
                                        ],
                                    ];

                                    $dayColorMap = [];
                                    $shiftIndex = 0;
                                @endphp
                                @forelse($activeShifts as $assignment)
                                    @if($assignment->shift)
                                        @php
                                            if ($assignment->shift->crosses_midnight) {
                                                $palette = [
                                                    'time_bg' => 'bg-purple-100 text-purple-800 border-purple-200',
                                                    'time_icon' => 'text-purple-600',
                                                    'day_pill' => 'bg-purple-50 text-purple-700 border-purple-200 font-semibold'
                                                ];
                                            } else {
                                                $palette = $palettes[$shiftIndex % count($palettes)];
                                                $shiftIndex++;
                                            }
                                            foreach ($assignment->shift->days_of_week ?? [] as $d) {
                                                $dayColorMap[$d] = $palette['day_pill'];
                                            }
                                        @endphp
                                        <div class="flex items-center gap-1.5 flex-wrap bg-slate-50/50 rounded-lg p-2 border border-slate-100">
                                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium border time-badge {{ $palette['time_bg'] }}">
                                                @if($assignment->shift->crosses_midnight)
                                                    <i class="ti ti-moon {{ $palette['time_icon'] }}"></i>
                                                @else
                                                    <i class="ti ti-clock {{ $palette['time_icon'] }}"></i>
                                                @endif
                                                <span class="time-display">
                                                    @if($assignment->shift->is_flexible && $assignment->shift->flexible_until_time)
                                                        {{ \Carbon\Carbon::parse($assignment->shift->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($assignment->shift->flexible_until_time)->format('g:i A') }} to {{ \Carbon\Carbon::parse($assignment->shift->end_time)->format('g:i A') }}
                                                    @else
                                                        {{ \Carbon\Carbon::parse($assignment->shift->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($assignment->shift->end_time)->format('h:i A') }}
                                                    @endif
                                                </span>
                                            </span>
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-800 border border-amber-100 break-badge">
                                                <i class="ti ti-coffee text-amber-500"></i>
                                                <span class="break-display">
                                                    {{ $assignment->shift->break_minutes }}m
                                                </span>
                                            </span>
                                            @if($assignment->shift->is_flexible)
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-800 border border-emerald-100" title="Flexible Shift">
                                                    <i class="ti ti-infinity text-emerald-500"></i>
                                                    <span>Flex</span>
                                                </span>
                                            @endif
                                            @if($canEditShifts)
                                                <button type="button" onclick="openEditModal({{ $employee->id }}, '{{ addslashes($employee->full_name) }}', {{ $assignment->id }}, '{{ $assignment->shift->start_time }}', '{{ $assignment->shift->end_time }}', {{ $assignment->shift->break_minutes }}, {{ json_encode($assignment->shift->days_of_week ?? []) }}, {{ $assignment->shift->is_flexible ? 'true' : 'false' }}, {{ $assignment->shift->flexible_until_time ? "'".substr($assignment->shift->flexible_until_time, 0, 5)."'" : 'null' }})" class="ml-auto inline-flex items-center justify-center rounded-lg bg-white border border-slate-200 p-1.5 text-slate-500 hover:bg-slate-50 hover:text-blue-600 transition-colors shadow-xs" title="Edit this shift segment">
                                                    <i class="ti ti-pencil text-sm"></i>
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                @empty
                                    <span class="text-slate-400 italic text-xs no-shift">Not assigned</span>
                                @endforelse
                            </div>
                        </div>

                        <!-- Working Days -->
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Working Days</div>
                            <div class="flex gap-1.5 days-display">
                                @if(count($allActiveDays) > 0)
                                    @foreach(['Mon'=>'M', 'Tue'=>'T', 'Wed'=>'W', 'Thu'=>'T', 'Fri'=>'F', 'Sat'=>'S', 'Sun'=>'S'] as $day => $label)
                                        @if(in_array($day, $allActiveDays))
                                            @php
                                                $dayClass = $dayColorMap[$day] ?? 'bg-blue-100 text-blue-700 border-blue-200';
                                            @endphp
                                            <span class="flex h-7 w-7 items-center justify-center rounded-lg text-xs border {{ $dayClass }}">{{ $label }}</span>
                                        @else
                                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-xs font-semibold text-slate-400 border border-slate-200/50">{{ $label }}</span>
                                        @endif
                                    @endforeach
                                @else
                                    <span class="text-slate-400 italic text-xs no-days">Not assigned</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-xl border border-slate-200 p-8 text-center text-slate-500">
                    No employees found.
                </div>
            @endforelse
        </div>
    </div>
    <!-- Add Shift Modal -->
    <div id="addShiftModal" class="hidden fixed inset-0 z-30 justify-center items-start sm:items-center bg-black/40 p-4 transition-opacity overflow-y-auto" style="padding-left: var(--sidebar-width);">
        <div class="my-auto w-full max-w-md rounded-xl bg-white shadow-xl relative max-h-[calc(100vh-2rem)] sm:max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Add Shift Schedule</h3>
                <button onclick="const m = document.getElementById('addShiftModal'); m.classList.add('hidden'); m.classList.remove('flex'); resetModalShifts();" class="text-slate-400 hover:text-slate-600 transition">
                    <i class="ti ti-x text-xl"></i>
                </button>
            </div>
                <form action="#" onsubmit="event.preventDefault(); handleAddShiftSubmit();" class="p-6">
                    @csrf
                    <div class="space-y-5">
                        <div class="relative">
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Assign to Employee</label>
                            <div class="relative">
                                <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="text" id="employee_search_input" style="padding-left: 2.25rem;" autocomplete="off" placeholder="Search employee by name or code..." class="w-full rounded-lg border border-slate-300 pr-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-700" required>
                                <input type="hidden" id="modal_employee_id" required>
                            </div>
                            <div id="employee_suggestions" class="absolute left-0 right-0 top-full z-10 mt-1 max-h-48 overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg hidden">
                                @foreach($employees as $emp)
                                    @php
                                        $allDays = [];
                                        $displayParts = [];
                                        if ($emp->currentShifts) {
                                            foreach ($emp->currentShifts as $cs) {
                                                if ($cs->shift) {
                                                    $allDays = array_merge($allDays, $cs->shift->days_of_week ?? []);
                                                    if ($cs->shift->is_flexible && $cs->shift->flexible_until_time) {
                                                        $timeStr = \Carbon\Carbon::parse($cs->shift->start_time)->format('g:i A') . ' - ' . \Carbon\Carbon::parse($cs->shift->flexible_until_time)->format('g:i A') . ' to ' . \Carbon\Carbon::parse($cs->shift->end_time)->format('g:i A');
                                                    } else {
                                                        $flex = $cs->shift->is_flexible ? ' [Flex]' : '';
                                                        $timeStr = \Carbon\Carbon::parse($cs->shift->start_time)->format('h:i A') . ' - ' . \Carbon\Carbon::parse($cs->shift->end_time)->format('h:i A') . $flex;
                                                    }
                                                    $displayParts[] = $timeStr . ' (' . implode(', ', array_map(function($d) { return substr($d, 0, 3); }, $cs->shift->days_of_week ?? [])) . ')';
                                                }
                                            }
                                        }
                                        $allDays = array_unique($allDays);
                                    @endphp
                                    <button type="button" class="suggestion-item w-full px-4 py-2 text-left text-sm hover:bg-slate-50 focus:bg-slate-50 focus:outline-none transition-colors text-slate-700"
                                            data-id="{{ $emp->id }}"
                                            data-name="{{ $emp->full_name }}"
                                            data-search="{{ strtolower($emp->full_name . ' ' . $emp->employee_code) }}"
                                            data-shift-start=""
                                            data-shift-end=""
                                            data-shift-display="{{ implode('; ', $displayParts) }}"
                                            data-shift-days="{{ json_encode(array_values($allDays)) }}">
                                        {{ $emp->full_name }}
                                    </button>
                                @endforeach
                                <div id="no_suggestions" class="hidden px-4 py-3 text-sm text-slate-500 text-center">No employee found.</div>
                            </div>
                        </div>

                        <!-- Existing Shift Warning Card -->
                        <div id="existing_shift_alert" class="hidden rounded-lg border border-amber-200 bg-amber-50/70 p-2.5 text-xs text-amber-800 transition-all duration-300">
                            <div class="flex items-center gap-2">
                                <i class="ti ti-alert-triangle text-amber-600 text-base shrink-0"></i>
                                <div class="flex-1 flex flex-wrap items-center justify-between gap-x-2 gap-y-1">
                                    <span class="font-bold text-amber-900">Already Scheduled:</span>
                                    <div class="flex gap-1.5 items-center">
                                        <span class="inline-flex items-center gap-1 rounded bg-amber-100/80 px-1.5 py-0.5 font-medium text-amber-800">
                                            <i class="ti ti-clock text-amber-600"></i>
                                            <span id="existing_shift_time_text">--:-- - --:--</span>
                                        </span>
                                        <span class="inline-flex items-center gap-1 rounded bg-amber-100/80 px-1.5 py-0.5 font-medium text-amber-800">
                                            <i class="ti ti-calendar text-amber-600"></i>
                                            <span id="existing_shift_days_text">--</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_flexible" value="1" class="rounded border-slate-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="text-sm font-medium text-slate-700">Flexible Shift</span>
                            </label>
                        </div>
                        <div>
                            <label class="mb-1.5 flex items-center gap-1.5 text-sm font-medium text-slate-700">
                                <span>Shift Schedule</span>
                                <div class="relative inline-flex items-center">
                                    <span id="end_time_info_icon" class="hidden text-slate-400 hover:text-slate-600 cursor-pointer" title="Information about flexible check-out">
                                        <i class="ti ti-info-circle text-sm"></i>
                                    </span>
                                    <!-- Tooltip Container -->
                                    <div id="flexible_hint" class="hidden absolute z-40 bottom-full left-1/2 -translate-x-1/2 mb-2 w-72 rounded-lg bg-white border border-slate-200 p-3 text-xs text-slate-600 shadow-xl leading-normal pointer-events-auto transition-opacity duration-200">
                                        <div class="font-semibold mb-1 flex items-center gap-1 text-slate-800 pr-5">
                                            <i class="ti ti-info-circle text-sm text-blue-500"></i>
                                            Flexible Checkout
                                        </div>
                                        <button type="button" id="close_tooltip_btn" class="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 transition-colors" title="Close info panel">
                                            <i class="ti ti-x text-sm"></i>
                                        </button>
                                        <div class="text-slate-500 mt-1">
                                            Enter the earliest start time and the standard/earliest check-out time. The check-out time will dynamically shift forward if the employee logs in later within the range.
                                        </div>
                                        <!-- Arrow Pointer -->
                                        <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1.5 h-3 w-3 rotate-45 border-r border-b border-slate-200 bg-white"></div>
                                    </div>
                                </div>
                            </label>
                            <div class="flex items-center gap-3">
                                <div id="start_time_wrapper" class="flex-1 flex items-center gap-2">
                                    <input type="time" name="start_time" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-700" required>
                                    <div id="flex_until_container" class="hidden flex items-center gap-2 flex-1">
                                        <span class="text-slate-400 font-medium">-</span>
                                        <input type="time" name="flexible_until_time" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-700">
                                    </div>
                                </div>
                                <span class="text-slate-400 font-medium">to</span>
                                <div class="flex-1">
                                    <input type="time" name="end_time" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-700" required>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Break Duration</label>
                            <div class="flex items-center gap-2">
                                <input type="number" name="break_minutes" min="0" value="60" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-700" required>
                                <span class="text-xs font-semibold text-slate-500">minutes</span>
                            </div>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Working Days</label>
                            <div class="flex flex-wrap gap-2">
                                @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day)
                                    <label class="cursor-pointer">
                                        <input type="checkbox" name="days[]" value="{{ $day }}" class="peer hidden" @if(!in_array($day, ['Sat', 'Sun'])) checked @endif>
                                        <span class="day-pill inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700">{{ $day }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="mt-8 flex justify-end gap-3">
                        <button type="button" onclick="const m = document.getElementById('addShiftModal'); m.classList.add('hidden'); m.classList.remove('flex'); resetModalShifts();" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700"> Create Shift</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toastNotification" class="fixed top-6 right-6 z-[60] flex translate-y-[-150%] opacity-0 items-center gap-3 rounded-lg border border-green-200 bg-white px-4 py-3 text-slate-800 shadow-xl transition-all duration-300 ease-out">
        <i class="ti ti-circle-check-filled text-2xl text-green-500"></i>
        <div>
            <h4 class="text-sm font-bold text-slate-900">Success!</h4>
            <p class="text-xs text-slate-600">Shift schedule successfully created.</p>
        </div>
        <button onclick="hideToast()" class="ml-2 rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
            <i class="ti ti-x text-lg"></i>
        </button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('employee_search_input');
            const suggestionsContainer = document.getElementById('employee_suggestions');
            const suggestionItems = document.querySelectorAll('.suggestion-item');
            const hiddenIdInput = document.getElementById('modal_employee_id');
            const noSuggestions = document.getElementById('no_suggestions');

            const flexCheckbox = document.querySelector('#addShiftModal input[name="is_flexible"]');
            const flexUntilContainer = document.getElementById('flex_until_container');
            const flexUntilInput = document.querySelector('#addShiftModal input[name="flexible_until_time"]');
            const startWrapper = document.getElementById('start_time_wrapper');
            const flexHint = document.getElementById('flexible_hint');
            
            if (flexCheckbox && flexUntilContainer && flexUntilInput) {
                flexCheckbox.addEventListener('change', function() {
                    const infoIcon = document.getElementById('end_time_info_icon');
                    if (this.checked) {
                        flexUntilContainer.classList.remove('hidden');
                        flexUntilInput.required = true;
                        if (startWrapper) {
                            startWrapper.classList.remove('flex-1');
                            startWrapper.classList.add('flex-[2]');
                        }
                        if (infoIcon) infoIcon.classList.remove('hidden');
                    } else {
                        flexUntilContainer.classList.add('hidden');
                        flexUntilInput.required = false;
                        flexUntilInput.value = '';
                        if (startWrapper) {
                            startWrapper.classList.remove('flex-[2]');
                            startWrapper.classList.add('flex-1');
                        }
                        if (infoIcon) infoIcon.classList.add('hidden');
                        if (flexHint) flexHint.classList.add('hidden');
                    }
                });
            }

            // Show flex hint on end_time input focus/select
            const endTimeInput = document.querySelector('#addShiftModal input[name="end_time"]');
            if (endTimeInput && flexHint) {
                endTimeInput.addEventListener('focus', function() {
                    if (flexCheckbox && flexCheckbox.checked) {
                        flexHint.classList.remove('hidden');
                    }
                });
            }

            // Toggle flex hint on info icon click
            const infoIcon = document.getElementById('end_time_info_icon');
            if (infoIcon && flexHint) {
                infoIcon.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (flexCheckbox && flexCheckbox.checked) {
                        flexHint.classList.toggle('hidden');
                    }
                });
            }

            // Close tooltip on close button click
            const closeTooltipBtn = document.getElementById('close_tooltip_btn');
            if (closeTooltipBtn && flexHint) {
                closeTooltipBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    flexHint.classList.add('hidden');
                });
            }

            // Close tooltip when clicking outside input, icon, or tooltip
            document.addEventListener('click', function(e) {
                if (flexCheckbox && flexCheckbox.checked && flexHint) {
                    const isInputClick = endTimeInput && endTimeInput.contains(e.target);
                    const isIconClick = infoIcon && infoIcon.contains(e.target);
                    const isHintClick = flexHint && flexHint.contains(e.target);
                    
                    if (!isInputClick && !isIconClick && !isHintClick) {
                        flexHint.classList.add('hidden');
                    }
                }
            });

            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    const term = e.target.value.toLowerCase().trim();
                    hiddenIdInput.value = ''; // Reset on typing
                    resetModalShifts(); // Reset existing shift alert/highlights on typing

                    if (!term) {
                        suggestionsContainer.classList.add('hidden');
                        return;
                    }

                    suggestionsContainer.classList.remove('hidden');
                    let found = false;

                    suggestionItems.forEach(item => {
                        if (item.getAttribute('data-search').includes(term)) {
                            item.classList.remove('hidden');
                            found = true;
                        } else {
                            item.classList.add('hidden');
                        }
                    });

                    if (found) {
                        noSuggestions.classList.add('hidden');
                    } else {
                        noSuggestions.classList.remove('hidden');
                    }
                });

                suggestionItems.forEach(item => {
                    item.addEventListener('click', function() {
                        searchInput.value = this.getAttribute('data-name');
                        hiddenIdInput.value = this.getAttribute('data-id');
                        suggestionsContainer.classList.add('hidden');
                        handleEmployeeSelect(this);
                    });
                });

                document.addEventListener('click', function(e) {
                    if (!searchInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                        suggestionsContainer.classList.add('hidden');
                    }
                });
            }
        });

        function resetModalShifts() {
            // Hide existing shift warning card
            const alertCard = document.getElementById('existing_shift_alert');
            if (alertCard) {
                alertCard.classList.add('hidden');
            }

            // Enable all checkboxes and restore default checked states (Mon-Fri checked, Sat-Sun unchecked)
            const checkboxes = document.querySelectorAll('#addShiftModal input[name="days[]"]');
            checkboxes.forEach(cb => {
                cb.disabled = false;
                cb.checked = !['Sat', 'Sun'].includes(cb.value);

                const pill = cb.nextElementSibling;
                if (pill) {
                    pill.classList.remove('day-pill-disabled-scheduled');
                    pill.removeAttribute('title');
                }
            });

            // Reset flexible inputs
            const flexCheckbox = document.querySelector('#addShiftModal input[name="is_flexible"]');
            if (flexCheckbox) flexCheckbox.checked = false;
            
            const flexUntilContainer = document.getElementById('flex_until_container');
            const flexUntilInput = document.querySelector('#addShiftModal input[name="flexible_until_time"]');
            const startWrapper = document.getElementById('start_time_wrapper');
            const flexHint = document.getElementById('flexible_hint');
            const infoIcon = document.getElementById('end_time_info_icon');
            if (flexUntilContainer && flexUntilInput) {
                flexUntilContainer.classList.add('hidden');
                flexUntilInput.required = false;
                flexUntilInput.value = '';
                if (startWrapper) {
                    startWrapper.classList.remove('flex-[2]');
                    startWrapper.classList.add('flex-1');
                }
                if (flexHint) flexHint.classList.add('hidden');
                if (infoIcon) infoIcon.classList.add('hidden');
            }
        }

        function handleEmployeeSelect(item) {
            // Reset modal first
            resetModalShifts();

            const start = item.getAttribute('data-shift-start');
            const end = item.getAttribute('data-shift-end');
            const display = item.getAttribute('data-shift-display');
            const daysJson = item.getAttribute('data-shift-days');

            if (!display || !daysJson) {
                return; // No active shift for this employee
            }

            let days = [];
            try {
                days = JSON.parse(daysJson);
            } catch (e) {
                console.error('Error parsing days:', e);
                return;
            }

            if (!days || days.length === 0) {
                return;
            }

            // Show existing shift details
            const alertCard = document.getElementById('existing_shift_alert');
            const timeText = document.getElementById('existing_shift_time_text');
            const daysText = document.getElementById('existing_shift_days_text');

            if (alertCard && timeText && daysText) {
                timeText.innerText = display;
                daysText.innerText = days.join(', ');
                alertCard.classList.remove('hidden');
            }

            // Disable and highlight scheduled days
            days.forEach(day => {
                const cb = document.querySelector(`#addShiftModal input[name="days[]"][value="${day}"]`);
                if (cb) {
                    cb.checked = false; // Uncheck for the new shift schedule
                    cb.disabled = true; // Disable to prevent duplication
                    
                    const pill = cb.nextElementSibling;
                    if (pill) {
                        pill.classList.add('day-pill-disabled-scheduled');
                        pill.setAttribute('title', `Already scheduled on ${day}`);
                    }
                }
            });
        }

        function handleAddShiftSubmit() {
            const employeeId = document.getElementById('modal_employee_id').value;
            const startTime = document.querySelector('#addShiftModal input[name="start_time"]').value;
            const endTime = document.querySelector('#addShiftModal input[name="end_time"]').value;
            const breakMinutes = document.querySelector('#addShiftModal input[name="break_minutes"]').value;
            const isFlexible = document.querySelector('#addShiftModal input[name="is_flexible"]')?.checked || false;
            const flexibleUntilTime = isFlexible ? (document.querySelector('#addShiftModal input[name="flexible_until_time"]')?.value || null) : null;
            
            // Get checked days
            const checkedDays = Array.from(document.querySelectorAll('#addShiftModal input[name="days[]"]:checked')).map(cb => cb.value);

            if (checkedDays.length === 0) {
                alert('Please select at least one working day for the new shift.');
                return;
            }

            const assignmentIdInput = document.getElementById('modal_assignment_id');
            const assignmentId = assignmentIdInput ? assignmentIdInput.value : null;

            if (!startTime || !endTime) {
                alert('Please select start and end time');
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            // Perform AJAX request directly from modal!
            fetch('{{ route("timekeeping.shift-schedule.save") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    employee_id: employeeId,
                    start_time: startTime,
                    end_time: endTime,
                    break_minutes: breakMinutes,
                    days: checkedDays,
                    is_flexible: isFlexible,
                    flexible_until_time: flexibleUntilTime,
                    assignment_id: assignmentId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error saving shift');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving shift');
            });
        }

        function showToast() {
            const toast = document.getElementById('toastNotification');
            toast.classList.remove('translate-y-[-150%]', 'opacity-0');
            toast.classList.add('translate-y-0', 'opacity-100');
            setTimeout(hideToast, 4000);
        }

        function hideToast() {
            const toast = document.getElementById('toastNotification');
            toast.classList.add('translate-y-[-150%]', 'opacity-0');
            toast.classList.remove('translate-y-0', 'opacity-100');
        }

        function openAddShiftModal() {
            const m = document.getElementById('addShiftModal');
            
            m.querySelector('h3').innerText = 'Add Shift Schedule';
            
            document.getElementById('modal_employee_id').value = '';
            const searchInput = document.getElementById('employee_search_input');
            searchInput.value = '';
            searchInput.readOnly = false;
            searchInput.classList.remove('bg-slate-50', 'text-slate-500');
            
            const assignmentIdInput = document.getElementById('modal_assignment_id');
            if (assignmentIdInput) assignmentIdInput.value = '';
            
            m.querySelector('form').reset();
            m.querySelector('input[name="break_minutes"]').value = '60';
            
            resetModalShifts();
            
            const alertCard = document.getElementById('existing_shift_alert');
            if (alertCard) alertCard.classList.add('hidden');
            
            m.classList.remove('hidden');
            m.classList.add('flex');
        }

        function openAddModalPreselected(employeeId, employeeName, displayString = '', days = []) {
            const m = document.getElementById('addShiftModal');
            m.querySelector('form').reset();
            
            document.getElementById('modal_employee_id').value = employeeId;
            
            const searchInput = document.getElementById('employee_search_input');
            searchInput.value = employeeName;
            searchInput.readOnly = true;
            searchInput.classList.add('bg-slate-50', 'text-slate-500');
            
            let assignmentIdInput = document.getElementById('modal_assignment_id');
            if (assignmentIdInput) assignmentIdInput.value = '';

            resetModalShifts();
            
            const alertCard = document.getElementById('existing_shift_alert');
            const timeText = document.getElementById('existing_shift_time_text');
            const daysText = document.getElementById('existing_shift_days_text');

            if (displayString && days && days.length > 0) {
                if (alertCard && timeText && daysText) {
                    timeText.innerText = displayString;
                    daysText.innerText = days.join(', ');
                    alertCard.classList.remove('hidden');
                }
                
                days.forEach(day => {
                    const cb = document.querySelector(`#addShiftModal input[name="days[]"][value="${day}"]`);
                    if (cb) {
                        cb.checked = false;
                        cb.disabled = true;
                        
                        const pill = cb.nextElementSibling;
                        if (pill) {
                            pill.classList.add('day-pill-disabled-scheduled');
                            pill.setAttribute('title', `Already scheduled on ${day}`);
                        }
                    }
                });
            } else {
                if (alertCard) alertCard.classList.add('hidden');
            }
            
            m.querySelector('h3').innerText = 'Add Shift Schedule';
            m.classList.remove('hidden');
            m.classList.add('flex');
        }

        function openEditModal(employeeId, employeeName, assignmentId, startTime, endTime, breakMinutes, days, isFlexible = false, flexibleUntilTime = null) {
            const m = document.getElementById('addShiftModal');
            m.querySelector('form').reset();
            
            m.querySelector('h3').innerText = 'Edit Shift Segment';
            
            document.getElementById('modal_employee_id').value = employeeId;
            const searchInput = document.getElementById('employee_search_input');
            searchInput.value = employeeName;
            searchInput.readOnly = true;
            searchInput.classList.add('bg-slate-50', 'text-slate-500');
            
            let assignmentIdInput = document.getElementById('modal_assignment_id');
            if (!assignmentIdInput) {
                assignmentIdInput = document.createElement('input');
                assignmentIdInput.type = 'hidden';
                assignmentIdInput.id = 'modal_assignment_id';
                assignmentIdInput.name = 'assignment_id';
                m.querySelector('form').appendChild(assignmentIdInput);
            }
            assignmentIdInput.value = assignmentId;
            
            m.querySelector('input[name="start_time"]').value = startTime.substring(0, 5); 
            m.querySelector('input[name="end_time"]').value = endTime.substring(0, 5);
            m.querySelector('input[name="break_minutes"]').value = breakMinutes;
            const flexCheckbox = m.querySelector('input[name="is_flexible"]');
            if(flexCheckbox) {
                flexCheckbox.checked = isFlexible;
            }
            
            const flexUntilContainer = document.getElementById('flex_until_container');
            const flexUntilInput = m.querySelector('input[name="flexible_until_time"]');
            const startWrapper = document.getElementById('start_time_wrapper');
            const flexHint = document.getElementById('flexible_hint');
            const infoIcon = document.getElementById('end_time_info_icon');
            if (flexUntilContainer && flexUntilInput) {
                if (isFlexible) {
                    flexUntilContainer.classList.remove('hidden');
                    flexUntilInput.required = true;
                    if (flexibleUntilTime) {
                        flexUntilInput.value = flexibleUntilTime.substring(0, 5);
                    }
                    if (startWrapper) {
                        startWrapper.classList.remove('flex-1');
                        startWrapper.classList.add('flex-[2]');
                    }
                    if (infoIcon) infoIcon.classList.remove('hidden');
                } else {
                    flexUntilContainer.classList.add('hidden');
                    flexUntilInput.required = false;
                    flexUntilInput.value = '';
                    if (startWrapper) {
                        startWrapper.classList.remove('flex-[2]');
                        startWrapper.classList.add('flex-1');
                    }
                    if (flexHint) flexHint.classList.add('hidden');
                    if (infoIcon) infoIcon.classList.add('hidden');
                }
            }
            
            resetModalShifts();
            const checkboxes = m.querySelectorAll('input[name="days[]"]');
            checkboxes.forEach(cb => {
                cb.checked = days.includes(cb.value);
            });
            
            const alertCard = document.getElementById('existing_shift_alert');
            if (alertCard) alertCard.classList.add('hidden');
            
            m.classList.remove('hidden');
            m.classList.add('flex');
        }
    </script>
</x-app-layout>
