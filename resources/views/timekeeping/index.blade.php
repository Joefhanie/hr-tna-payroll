<x-app-layout>
    <x-slot:title>Timekeeping</x-slot:title>
    <x-slot:header>Timekeeping</x-slot:header>

    <style>
        /* Mobile vs Desktop Display for Badges */
        @media (max-width: 639px) {
            .tk-mobile-dots {
                display: flex !important;
            }
            .tk-desktop-badges {
                display: none !important;
            }
        }
        @media (min-width: 640px) {
            .tk-mobile-dots {
                display: none !important;
            }
            .tk-desktop-badges {
                display: flex !important;
            }
        }

        /* Modal offset reset on mobile/tablet */
        @media (max-width: 1023px) {
            #manualEntryModal {
                padding-left: 0 !important;
            }
        }

        .tab-active {
            border-color: var(--brand-primary-soft) !important;
            background-color: var(--brand-primary-soft) !important;
            color: var(--brand-primary) !important;
            opacity: 1 !important;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.04);
        }

        .tab-inactive {
            border-color: transparent !important;
            background: transparent !important;
            color: #64748b !important;
        }

        .tab-inactive:hover {
            border-color: var(--brand-primary) !important;
            color: var(--brand-primary) !important;
        }
    </style>

    <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-[1.65rem] font-bold text-[#06112e]">Timekeeping</h1>
            <p class="mt-1 text-sm text-slate-500">Track and manage employee attendance records.</p>
        </div>
        @if(auth()->user()->role === 4)
        <div class="flex gap-2">
                <button type="button"
                    onclick="document.getElementById('manualEntryModal').classList.replace('hidden', 'flex')"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                <i class="ti ti-pencil text-base"></i>
                Manual Entry
            </button>
        </div>
        @endif
    </div>

    <div class="mb-4 h-px w-full bg-slate-200"></div>



    {{-- Summary cards --}}
    <div class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-4">
        <div class="nw-panel rounded-2xl p-4 md:p-6 transition hover:-translate-y-0.5 hover:shadow-xl">
            <p class="text-xs md:text-sm font-medium text-slate-500">Present today</p>
            <p class="mt-1.5 md:mt-3 text-2xl md:text-3xl font-extrabold text-slate-900">{{ $presentToday }}</p>
        </div>

        <div class="nw-panel rounded-2xl p-4 md:p-6 transition hover:-translate-y-0.5 hover:shadow-xl">
            <p class="text-xs md:text-sm font-medium text-slate-500">Late</p>
            <p class="mt-1.5 md:mt-3 text-2xl md:text-3xl font-extrabold text-slate-900">{{ $lateToday }}</p>
        </div>

        <div class="nw-panel rounded-2xl p-4 md:p-6 transition hover:-translate-y-0.5 hover:shadow-xl">
            <p class="text-xs md:text-sm font-medium text-slate-500">Absent</p>
            <p class="mt-1.5 md:mt-3 text-2xl md:text-3xl font-extrabold text-slate-900">{{ $absentToday }}</p>
        </div>

        <div class="nw-panel rounded-2xl p-4 md:p-6 transition hover:-translate-y-0.5 hover:shadow-xl">
            <p class="text-xs md:text-sm font-medium text-slate-500">On Leave</p>
            <p class="mt-1.5 md:mt-3 text-2xl md:text-3xl font-extrabold text-slate-900">{{ $onLeaveToday }}</p>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="mb-4">
        <div class="border-b border-slate-200">
            <nav class="-mb-px flex gap-6" aria-label="Tabs">
                <button type="button" onclick="switchTab('calendar')" id="tab-calendar" class="tab-active whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold transition">Calendar View</button>
                <button type="button" onclick="switchTab('list')" id="tab-list" class="tab-inactive whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition">List View</button>
            </nav>
        </div>
    </div>

    {{-- Calendar View --}}
    <div id="view-calendar" class="block mb-12">
        @php
            $selectedDateCarbon = \Carbon\Carbon::parse($selectedDate);
            $startOfMonth = $selectedDateCarbon->copy()->startOfMonth();
            $endOfMonth = $selectedDateCarbon->copy()->endOfMonth();
            $endOfPrevMonth = $startOfMonth->copy()->subDay();
            $daysInMonth = $endOfMonth->daysInMonth;
            $daysInPrevMonth = $endOfPrevMonth->daysInMonth;
            $firstDayOfWeek = $startOfMonth->dayOfWeek; // 0 (Sun) to 6 (Sat)
            $monthName = $startOfMonth->format('F');
            $year = $startOfMonth->format('Y');
            $todayStr = \Carbon\Carbon::now()->toDateString();

            // Calculate previous and next month dates for navigation
            $prevMonthDate = $startOfMonth->copy()->subMonth()->startOfMonth()->toDateString();
            $nextMonthDate = $startOfMonth->copy()->addMonth()->startOfMonth()->toDateString();
        @endphp
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Left Sidebar -->
            <div class="order-2 lg:order-1 lg:col-span-1 bg-[#f8f9fc] border border-slate-200 rounded-2xl shadow-sm flex flex-col lg:h-[700px] overflow-hidden">
                <div class="p-8 border-b border-slate-200 bg-white text-center">
                    <h2 id="selectedDateNumber" class="text-7xl font-black text-[#06112e] tracking-tight">{{ $selectedDateCarbon->format('d') }}</h2>
                    <p id="selectedDateDay" class="text-sm font-bold uppercase tracking-widest text-slate-500 mt-2">{{ $selectedDateCarbon->format('l') }}</p>
                </div>
                <div class="p-6 flex-1 overflow-y-auto">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-5">Attendance Records</h3>
                    <div id="attendanceRecordsList" class="space-y-4">
                        <!-- JS populated -->
                    </div>
                </div>
            </div>

            <!-- Right side Calendar -->
            <div class="order-1 lg:order-2 lg:col-span-3 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden flex flex-col lg:h-[700px]">
                <div class="bg-brand-primary p-6 flex justify-center items-center" style="color: var(--brand-text-on-primary);">
                    <div class="flex items-center justify-between w-[300px]">
                        <a href="{{ route('timekeeping.index') }}?date={{ $prevMonthDate }}&tab=calendar"
                           class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/40 bg-white/90 text-slate-900 shadow-sm transition hover:bg-slate-900 hover:text-white hover:border-slate-900 shrink-0"
                           title="Previous Month">
                            <i class="ti ti-chevron-left text-lg"></i>
                        </a>
                        <h2 class="text-xl font-bold tracking-wider uppercase flex-1 text-center select-none">{{ $monthName }} {{ $year }}</h2>
                        <a href="{{ route('timekeeping.index') }}?date={{ $nextMonthDate }}&tab=calendar"
                           class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/40 bg-white/90 text-slate-900 shadow-sm transition hover:bg-slate-900 hover:text-white hover:border-slate-900 shrink-0"
                           title="Next Month">
                            <i class="ti ti-chevron-right text-lg"></i>
                        </a>
                    </div>
                </div>
                <div class="p-6 flex-1 flex flex-col">
                    <div class="grid text-center mb-2" style="grid-template-columns: repeat(7, 1fr);">
                        @foreach(['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'] as $day)
                            <div class="text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest" style="font-size: 0.65rem;">{{ $day }}</div>
                        @endforeach
                    </div>
                    <div class="grid flex-1 border-t border-l border-slate-100 rounded-lg overflow-hidden" style="grid-template-columns: repeat(7, 1fr);">
                        @for ($i = 0; $i < $firstDayOfWeek; $i++)
                            @php
                                $prevMonthDay = $daysInPrevMonth - $firstDayOfWeek + $i + 1;
                            @endphp
                            <div class="bg-slate-50 border-r border-b border-slate-100 p-1 lg:p-2 min-h-[48px] sm:min-h-[70px] lg:min-h-[90px] flex items-start justify-center pt-2 lg:pt-4">
                                <span class="inline-flex h-6 w-6 sm:h-7 sm:w-7 lg:h-8 lg:w-8 items-center justify-center rounded-full text-xs sm:text-sm font-semibold text-slate-300">
                                    {{ $prevMonthDay }}
                                </span>
                            </div>
                        @endfor

                        @for ($day = 1; $day <= $daysInMonth; $day++)
                            @php
                                $currentDateObj = clone $startOfMonth;
                                $currentDateObj->addDays($day - 1);
                                $currentDateStr = $currentDateObj->toDateString();
                                $hasEvents = isset($calendarData[$currentDateStr]) && count($calendarData[$currentDateStr]) > 0;
                                $isToday = $todayStr === $currentDateStr;
                                $calendarDateRecords = $calendarData[$currentDateStr] ?? [];
                                $calendarDayTotal = count($calendarDateRecords);
                                $calendarDayStatusCounts = [
                                    'present' => 0,
                                    'late' => 0,
                                    'absent' => 0,
                                    'excused' => 0,
                                ];

                                foreach ($calendarDateRecords as $calendarRecord) {
                                    $rawStatus = data_get($calendarRecord, 'status');
                                    $statusKey = is_numeric($rawStatus) ? (int) $rawStatus : strtolower((string) $rawStatus);

                                    if ($statusKey === 1 || $statusKey === 'present') {
                                        $calendarDayStatusCounts['present']++;
                                    } elseif ($statusKey === 2 || $statusKey === 'late') {
                                        $calendarDayStatusCounts['late']++;
                                    } elseif ($statusKey === 3 || $statusKey === 'absent') {
                                        $calendarDayStatusCounts['absent']++;
                                    } elseif ($statusKey === 4 || $statusKey === 'excused') {
                                        $calendarDayStatusCounts['excused']++;
                                    }
                                }

                                $calendarStatusSummary = 'P: ' . $calendarDayStatusCounts['present']
                                    . ' | L: ' . $calendarDayStatusCounts['late']
                                    . ' | A: ' . $calendarDayStatusCounts['absent']
                                    . ' | OL: ' . $calendarDayStatusCounts['excused'];
                            @endphp
                            <div data-date="{{ $currentDateStr }}" class="calendar-day-cell bg-white border-r border-b border-slate-100 p-1 lg:p-2 cursor-pointer hover:bg-[#f0f4ff] transition group relative flex flex-col items-center pt-2 lg:pt-4 min-h-[48px] sm:min-h-[70px] lg:min-h-[90px]" onclick="selectDate('{{ $currentDateStr }}', {{ $day }}, '{{ strtoupper($currentDateObj->format('l')) }}', this)">
                                <span class="inline-flex h-6 w-6 sm:h-7 sm:w-7 lg:h-8 lg:w-8 items-center justify-center rounded-full text-xs sm:text-sm font-semibold {{ $isToday ? 'bg-indigo-600 text-white' : 'text-slate-700 group-hover:text-indigo-600' }}">
                                    {{ $day }}
                                </span>
                                @if($hasEvents)
                                    {{-- Mobile (<sm): tiny dots below the day number --}}
                                    <div class="tk-mobile-dots items-center justify-center gap-0.5 mt-1">
                                        @if($calendarDayStatusCounts['present'] > 0)
                                            <span class="inline-block h-[5px] w-[5px] rounded-full bg-emerald-500" title="Present: {{ $calendarDayStatusCounts['present'] }}"></span>
                                        @endif
                                        @if($calendarDayStatusCounts['late'] > 0)
                                            <span class="inline-block h-[5px] w-[5px] rounded-full bg-yellow-400" title="Late: {{ $calendarDayStatusCounts['late'] }}"></span>
                                        @endif
                                        @if($calendarDayStatusCounts['absent'] > 0)
                                            <span class="inline-block h-[5px] w-[5px] rounded-full bg-red-400" title="Absent: {{ $calendarDayStatusCounts['absent'] }}"></span>
                                        @endif
                                        @if($calendarDayStatusCounts['excused'] > 0)
                                            <span class="inline-block h-[5px] w-[5px] rounded-full bg-blue-400" title="On Leave: {{ $calendarDayStatusCounts['excused'] }}"></span>
                                        @endif
                                    </div>

                                    {{-- Tablet/Desktop (sm+): numbered circular badges, absolutely positioned --}}
                                    <div class="tk-desktop-badges absolute bottom-1.5 left-0 right-0 flex flex-wrap justify-center gap-1 px-1">
                                        @if($calendarDayStatusCounts['present'] > 0)
                                            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500 text-[0.62rem] font-bold text-white shadow-sm" title="Present: {{ $calendarDayStatusCounts['present'] }}">
                                                {{ $calendarDayStatusCounts['present'] }}
                                            </span>
                                        @endif
                                        @if($calendarDayStatusCounts['late'] > 0)
                                            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-yellow-400 text-[0.62rem] font-bold text-slate-900 shadow-sm" title="Late: {{ $calendarDayStatusCounts['late'] }}">
                                                {{ $calendarDayStatusCounts['late'] }}
                                            </span>
                                        @endif
                                        @if($calendarDayStatusCounts['absent'] > 0)
                                            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-red-400 text-[0.62rem] font-bold text-white shadow-sm" title="Absent: {{ $calendarDayStatusCounts['absent'] }}">
                                                {{ $calendarDayStatusCounts['absent'] }}
                                            </span>
                                        @endif
                                        @if($calendarDayStatusCounts['excused'] > 0)
                                            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-blue-400 text-[0.62rem] font-bold text-white shadow-sm" title="On Leave: {{ $calendarDayStatusCounts['excused'] }}">
                                                {{ $calendarDayStatusCounts['excused'] }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endfor

                        @php
                            $remainingCells = (7 - (($firstDayOfWeek + $daysInMonth) % 7)) % 7;
                        @endphp
                        @for ($i = 0; $i < $remainingCells; $i++)
                            @php
                                $nextMonthDay = $i + 1;
                            @endphp
                            <div class="bg-slate-50 border-r border-b border-slate-100 p-1 lg:p-2 min-h-[48px] sm:min-h-[70px] lg:min-h-[90px] flex items-start justify-center pt-2 lg:pt-4">
                                <span class="inline-flex h-6 w-6 sm:h-7 sm:w-7 lg:h-8 lg:w-8 items-center justify-center rounded-full text-xs sm:text-sm font-semibold text-slate-300">
                                    {{ $nextMonthDay }}
                                </span>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- List View --}}
    <div id="view-list" class="hidden">
        <section class="card p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-5 border-b border-slate-100 pb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-800">Attendance Records</h3>
                    <p class="text-xs text-slate-500">View and manage attendance logs for the selected date.</p>
                </div>
            </div>

            {{-- Filters Form --}}
            <form id="filterForm" method="GET" action="{{ route('timekeeping.index') }}" class="mb-5 grid grid-cols-2 gap-3 items-end sm:flex sm:flex-wrap">
                <input type="hidden" name="tab" value="list">

                <div class="col-span-2 sm:flex-1 sm:min-w-[200px]">
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Search Employee</label>
                    <div class="relative">
                        <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"></i>
                        <input type="text" name="q" id="filterSearch" value="{{ $filters['q'] ?? '' }}" autocomplete="off"
                            placeholder="Search by name, code, email…"
                            class="w-full rounded-lg border border-slate-200 bg-white pl-9 pr-3 py-2 text-sm text-slate-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="col-span-1 sm:min-w-[150px]">
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Status</label>
                    <select name="status" id="filterStatus"
                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">All Statuses</option>
                        <option value="1" {{ ($filters['status'] ?? '') == '1' ? 'selected' : '' }}>Present</option>
                        <option value="2" {{ ($filters['status'] ?? '') == '2' ? 'selected' : '' }}>Late</option>
                        <option value="3" {{ ($filters['status'] ?? '') == '3' ? 'selected' : '' }}>Absent</option>
                        <option value="4" {{ ($filters['status'] ?? '') == '4' ? 'selected' : '' }}>On Leave</option>
                        <option value="5" {{ ($filters['status'] ?? '') == '5' ? 'selected' : '' }}>Shift Not Started</option>
                    </select>
                </div>

                <div class="col-span-1 sm:min-w-[150px]">
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Date</label>
                    <input type="date" name="date" id="filterDate" value="{{ $selectedDate }}" onchange="this.form.submit()"
                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <button type="button" id="timekeeping-clear"
                    class="col-span-1 w-full sm:w-auto text-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
                    Clear
                </button>

                @if(auth()->user()->role === 4)
                    <a href="{{ route('timekeeping.export') }}" id="btnExport"
                        class="col-span-1 w-full sm:w-auto sm:ml-auto inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-200">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export CSV
                    </a>
                @endif
            </form>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const searchInput = document.getElementById('filterSearch');
                    const statusSelect = document.getElementById('filterStatus');
                    const dateInput = document.getElementById('filterDate');
                    const clearButton = document.getElementById('timekeeping-clear');
                    const exportButton = document.getElementById('btnExport');

                    function getAttendanceRows() {
                        return Array.from(document.querySelectorAll('#attendanceTable tbody tr.group'));
                    }

                    function getAttendanceCards() {
                        return Array.from(document.querySelectorAll('[id^="attendance-card-"]'));
                    }

                    function updateExportUrl() {
                        if (!exportButton) {
                            return;
                        }

                        const params = new URLSearchParams();
                        const q = searchInput?.value?.trim() || '';
                        const status = statusSelect?.value || '';
                        const date = dateInput?.value || '';

                        if (q) params.set('q', q);
                        if (status) params.set('status', status);
                        if (date) params.set('date', date);
                        params.set('tab', 'list');

                        exportButton.href = `{{ route('timekeeping.export') }}${[...params].length ? '?' + params.toString() : ''}`;
                    }

                    function filterAttendanceRows() {
                        const term = (searchInput?.value || '').trim().toLowerCase();
                        const statusVal = statusSelect?.value || '';

                        getAttendanceRows().forEach((row) => {
                            const searchable = row.textContent.toLowerCase();
                            const rowStatus = row.getAttribute('data-status') || '';

                            const matchesSearch = !term || searchable.includes(term);
                            const matchesStatus = !statusVal || rowStatus === statusVal;

                            const isVisible = matchesSearch && matchesStatus;
                            row.setAttribute('data-filter-hidden', isVisible ? 'false' : 'true');
                        });

                        getAttendanceCards().forEach((card) => {
                            const searchable = card.textContent.toLowerCase();
                            const cardStatus = card.getAttribute('data-status') || '';

                            const matchesSearch = !term || searchable.includes(term);
                            const matchesStatus = !statusVal || cardStatus === statusVal;

                            const isVisible = matchesSearch && matchesStatus;
                            card.classList.toggle('hidden', !isVisible);
                        });

                        updateExportUrl();
                    }

                    searchInput?.addEventListener('input', filterAttendanceRows);
                    statusSelect?.addEventListener('change', filterAttendanceRows);
                    dateInput?.addEventListener('change', updateExportUrl);

                    searchInput?.addEventListener('keydown', function(event) {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                        }
                    });

                    clearButton?.addEventListener('click', function() {
                        if (searchInput) searchInput.value = '';
                        if (statusSelect) statusSelect.value = '';
                        filterAttendanceRows();
                    });

                    filterAttendanceRows();
                });
            </script>
            <!-- Desktop View -->
            <div class="hidden lg:block overflow-x-auto rounded-lg border border-slate-200 bg-white">
                <table id="attendanceTable" class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Employee</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Shift</th>
                            <th class="px-4 py-3">Time In</th>
                            <th class="px-4 py-3">Time Out</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($todayAttendance as $attendance)
                            @php
                                $shift = $attendance->shift ?? $attendance->user?->employee?->currentShift?->shift;
                                $displayShiftTime = $shift?->getDisplayTimeRange() ?? null;
                                $workedHours = null;

                                if ($attendance->check_in && $attendance->check_out) {
                                    $timeIn = \Carbon\Carbon::parse($attendance->attendance_date->toDateString() . ' ' . $attendance->check_in->format('H:i:s'));
                                    $timeOut = \Carbon\Carbon::parse($attendance->attendance_date->toDateString() . ' ' . $attendance->check_out->format('H:i:s'));

                                    if ($shift?->crosses_midnight && $timeOut->lt($timeIn)) {
                                        $timeOut->addDay();
                                    }

                                    if ($timeOut->gte($timeIn)) {
                                        $totalMins = $timeOut->diffInMinutes($timeIn);
                                        $breakMins = $shift ? $shift->break_minutes : 0;
                                        $workedHours = round(max(0, $totalMins - $breakMins) / 60, 2);
                                    } else {
                                        $workedHours = 0;
                                    }
                                }

                                $computedStatus = $attendance->status;

                                $statusKey = is_numeric($computedStatus) ? (int) $computedStatus : strtolower((string) $computedStatus);
                                $statusLabel = $attendanceStatusLabels[$statusKey] ?? ucfirst((string) $computedStatus);
                                $statusClasses = [
                                    1 => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                    2 => 'bg-amber-100 text-amber-700 border-amber-200',
                                    3 => 'bg-rose-100 text-rose-700 border-rose-200',
                                    4 => 'bg-sky-100 text-sky-700 border-sky-200',
                                    5 => 'bg-slate-100 text-slate-500 border-slate-200',
                                    'present' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                    'late' => 'bg-amber-100 text-amber-700 border-amber-200',
                                    'absent' => 'bg-rose-100 text-rose-700 border-rose-200',
                                    'excused' => 'bg-sky-100 text-sky-700 border-sky-200',
                                    'not_started' => 'bg-slate-100 text-slate-500 border-slate-200',
                                ];
                                $pillClass = $statusClasses[$statusKey] ?? 'bg-slate-100 text-slate-700 border-slate-200';

                                $employeeDisplayName = trim((string) (
                                    optional($attendance->user->employee)->full_name
                                    ?? ($attendance->user->display_name ?? $attendance->user->name ?? 'Unknown')
                                ));
                                $employeeNameParts = preg_split('/\s+/', $employeeDisplayName, -1, PREG_SPLIT_NO_EMPTY);
                                if (count($employeeNameParts) >= 3) {
                                    $employeeDisplayName = $employeeNameParts[0] . ' ' . strtoupper(substr($employeeNameParts[1], 0, 1)) . '. ' . $employeeNameParts[count($employeeNameParts) - 1];
                                }
                            @endphp
                            <tr data-status="{{ $statusKey }}" class="group hover:bg-slate-50/50 transition">
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $employeeDisplayName }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $attendance->attendance_date->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-slate-600">
                                    <div class="flex flex-col gap-1">
                                        <span class="font-medium text-slate-900">{{ $displayShiftTime ?? '—' }}</span>
                                        @if($shift)
                                            <div class="flex flex-wrap gap-1 mt-0.5">
                                                @if($shift->crosses_midnight)
                                                    <span class="inline-flex items-center gap-1 rounded bg-indigo-50 border border-indigo-200 px-1.5 py-0.5 text-[9px] font-bold text-indigo-700 uppercase tracking-wide" title="Cross-day Shift (Crosses Midnight)">
                                                        <i class="ti ti-moon"></i>
                                                        CROSS-DAY
                                                    </span>
                                                @endif
                                                @if($shift->getWorkingHoursPerDay() <= 4.0)
                                                    <span class="inline-flex items-center gap-1 rounded bg-cyan-50 border border-cyan-200 px-1.5 py-0.5 text-[9px] font-bold text-cyan-700 uppercase tracking-wide" title="Half Day Shift (4 hours or less)">
                                                        <i class="ti ti-circle-half"></i>
                                                        HALF DAY
                                                    </span>
                                                @endif
                                                @if(!is_null($workedHours) && $workedHours > 0 && $workedHours <= 4.0)
                                                    <span class="inline-flex items-center gap-1 rounded bg-cyan-50 border border-cyan-200 px-1.5 py-0.5 text-[9px] font-bold text-cyan-700 uppercase tracking-wide" title="Worked 4 hours or less today">
                                                        <i class="ti ti-clock-2"></i>
                                                        WORKED HALF DAY
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-900">{{ $attendance->check_in ? $attendance->check_in->format('H:i') : '—' }}</td>
                                <td class="px-4 py-3 text-slate-900">{{ $attendance->check_out ? $attendance->check_out->format('H:i') : '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold {{ $pillClass }}">{{ $statusLabel }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <a href="{{ route('timekeeping.show', $attendance->user) }}" class="text-slate-600 hover:text-slate-900 transition" title="View All Records">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">No attendance records yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <x-table-pagination target="attendanceTable" />
            </div>

            <!-- Mobile View -->
            <div class="block lg:hidden space-y-4">
                @forelse($todayAttendance as $attendance)
                    @php
                        $shift = $attendance->shift ?? $attendance->user?->employee?->currentShift?->shift;
                        $displayShiftTime = $shift?->getDisplayTimeRange() ?? null;
                        $workedHours = null;

                        if ($attendance->check_in && $attendance->check_out) {
                            $timeIn = \Carbon\Carbon::parse($attendance->attendance_date->toDateString() . ' ' . $attendance->check_in->format('H:i:s'));
                            $timeOut = \Carbon\Carbon::parse($attendance->attendance_date->toDateString() . ' ' . $attendance->check_out->format('H:i:s'));

                            if ($shift?->crosses_midnight && $timeOut->lt($timeIn)) {
                                $timeOut->addDay();
                            }

                            if ($timeOut->gte($timeIn)) {
                                $totalMins = $timeOut->diffInMinutes($timeIn);
                                $breakMins = $shift ? $shift->break_minutes : 0;
                                $workedHours = round(max(0, $totalMins - $breakMins) / 60, 2);
                            } else {
                                $workedHours = 0;
                            }
                        }

                        $computedStatus = $attendance->status;

                        $statusKey = is_numeric($computedStatus) ? (int) $computedStatus : strtolower((string) $computedStatus);
                        $statusLabel = $attendanceStatusLabels[$statusKey] ?? ucfirst((string) $computedStatus);
                        $statusClasses = [
                            1 => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                            2 => 'bg-amber-100 text-amber-700 border-amber-200',
                            3 => 'bg-rose-100 text-rose-700 border-rose-200',
                            4 => 'bg-sky-100 text-sky-700 border-sky-200',
                            5 => 'bg-slate-100 text-slate-500 border-slate-200',
                            'present' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                            'late' => 'bg-amber-100 text-amber-700 border-amber-200',
                            'absent' => 'bg-rose-100 text-rose-700 border-rose-200',
                            'excused' => 'bg-sky-100 text-sky-700 border-sky-200',
                            'not_started' => 'bg-slate-100 text-slate-500 border-slate-200',
                        ];
                        $pillClass = $statusClasses[$statusKey] ?? 'bg-slate-100 text-slate-700 border-slate-200';

                        $employeeDisplayName = trim((string) (
                            optional($attendance->user->employee)->full_name
                            ?? ($attendance->user->display_name ?? $attendance->user->name ?? 'Unknown')
                        ));
                        $employeeNameParts = preg_split('/\s+/', $employeeDisplayName, -1, PREG_SPLIT_NO_EMPTY);
                        if (count($employeeNameParts) >= 3) {
                            $employeeDisplayName = $employeeNameParts[0] . ' ' . strtoupper(substr($employeeNameParts[1], 0, 1)) . '. ' . $employeeNameParts[count($employeeNameParts) - 1];
                        }
                    @endphp
                    <div data-status="{{ $statusKey }}" class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 hover:border-blue-200 transition-colors" id="attendance-card-{{ $attendance->id ?? $loop->index }}">
                        <!-- Card Header -->
                        <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-3 mb-3">
                            <span class="font-semibold text-slate-900 text-base">{{ $employeeDisplayName }}</span>
                            <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $pillClass }}">{{ $statusLabel }}</span>
                        </div>

                        <!-- Card Body -->
                        <div class="space-y-3 text-sm">
                            <!-- Date Info -->
                            <div class="flex justify-between items-center text-slate-600">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Date</span>
                                <span class="font-medium text-slate-800">
                                    {{ $attendance->attendance_date->format('M d, Y') }}
                                    <span class="text-xs text-slate-400 font-normal ml-1">{{ $attendance->attendance_date->format('D') }}</span>
                                </span>
                            </div>

                            <!-- Shift Details -->
                            <div class="flex justify-between items-start text-slate-600">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider mt-0.5">Shift</span>
                                <div class="flex flex-col items-end gap-1">
                                    <span class="font-medium text-slate-800 text-right">{{ $displayShiftTime ?? '—' }}</span>
                                    @if($shift)
                                        <div class="flex flex-wrap gap-1 mt-0.5 justify-end">
                                            @if($shift->crosses_midnight)
                                                <span class="inline-flex items-center gap-1 rounded bg-indigo-50 border border-indigo-200 px-1.5 py-0.5 text-[9px] font-bold text-indigo-700 uppercase tracking-wide" title="Cross-day Shift (Crosses Midnight)">
                                                    <i class="ti ti-moon"></i>
                                                    CROSS-DAY
                                                </span>
                                            @endif
                                            @if($shift->getWorkingHoursPerDay() <= 4.0)
                                                <span class="inline-flex items-center gap-1 rounded bg-cyan-50 border border-cyan-200 px-1.5 py-0.5 text-[9px] font-bold text-cyan-700 uppercase tracking-wide" title="Half Day Shift (4 hours or less)">
                                                    <i class="ti ti-circle-half"></i>
                                                    HALF DAY
                                                </span>
                                            @endif
                                            @if(!is_null($workedHours) && $workedHours > 0 && $workedHours <= 4.0)
                                                <span class="inline-flex items-center gap-1 rounded bg-cyan-50 border border-cyan-200 px-1.5 py-0.5 text-[9px] font-bold text-cyan-700 uppercase tracking-wide" title="Worked 4 hours or less today">
                                                    <i class="ti ti-clock-2"></i>
                                                    WORKED HALF DAY
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Clock-In / Out Times -->
                            <div class="grid grid-cols-2 gap-4 border-t border-slate-100 pt-3">
                                <div>
                                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Time In</span>
                                    <span class="font-semibold text-slate-800 text-base">
                                        {{ $attendance->check_in ? $attendance->check_in->format('H:i') : '—' }}
                                    </span>
                                </div>
                                <div>
                                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Time Out</span>
                                    <span class="font-semibold text-slate-800 text-base">
                                        {{ $attendance->check_out ? $attendance->check_out->format('H:i') : '—' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer -->
                        <div class="flex items-center justify-between border-t border-slate-100 mt-3 pt-3">
                            <div>
                                @if(!is_null($workedHours))
                                    <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-2.5 py-1 rounded-md">
                                        Worked: {{ $workedHours }} hrs
                                    </span>
                                @endif
                            </div>
                            <div>
                                <a href="{{ route('timekeeping.show', $attendance->user) }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-slate-900 border border-slate-200 bg-slate-50 px-3 py-2 rounded-lg transition" title="View All Records">
                                    <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    View Records
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="bg-white rounded-xl border border-slate-200 p-8 text-center text-sm text-slate-500">
                        No attendance records yet.
                    </div>
                @endforelse
            </div>
        </section>
    </div>

    {{-- ── Manual Entry Modal ─────────────────────────────── --}}
    <div id="manualEntryModal"
         class="{{ $errors->any() ? 'flex' : 'hidden' }} fixed inset-0 z-30 justify-center items-start sm:items-center bg-black/40 p-4 transition-opacity overflow-y-auto"
         style="padding-left: var(--sidebar-width);">

        <div class="my-auto w-full max-w-lg rounded-2xl border border-slate-300 bg-white shadow-[0_25px_50px_-12px_rgba(0,0,0,0.45)] ring-4 ring-black/5 max-h-[calc(100vh-2rem)] sm:max-h-[90vh] overflow-y-auto">

            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h3 class="text-lg font-bold text-[#06112e]">Manual Time Entry</h3>
                <button type="button"
                        onclick="document.getElementById('manualEntryModal').classList.replace('flex', 'hidden')"
                        class="text-slate-400 transition hover:text-slate-600">
                    <i class="ti ti-x text-xl"></i>
                </button>
            </div>



            {{-- Form --}}
            <form method="POST" action="{{ route('timekeeping.manual.store') }}" class="p-6">
                @csrf

                <div class="grid gap-5">

                    {{-- Employee --}}
                    <div>
                        <label for="manual-employee-id" class="mb-1.5 block text-[0.8rem] font-bold text-[#06112e]">
                            Employee <span class="text-red-500">*</span>
                        </label>
                        <select id="manual-employee-id" name="employee_id" required
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm transition focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <option value="" disabled {{ old('employee_id') ? '' : 'selected' }}>Select employee…</option>
                            @foreach($users as $employee)
                                @php
                                    $employeeDisplayName = $employee->full_name ?? 'Unnamed Employee';
                                    $employeeCode = $employee->employee_code ?? '';
                                @endphp
                                <option value="{{ $employee->id }}"
                                    {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                                    {{ $employeeDisplayName }}{{ $employeeCode ? ' (' . $employeeCode . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <div id="employee-shift-info" class="mt-1.5 text-[0.75rem] text-slate-500 hidden">
                            <div id="employee-shift-text" class="space-y-1"></div>
                        </div>
                    </div>

                    {{-- Date --}}
                    <div>
                        <label for="manual-date" class="mb-1.5 block text-[0.8rem] font-bold text-[#06112e]">
                            Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" id="manual-date" name="attendance_date" required
                               value="{{ old('attendance_date', now()->toDateString()) }}"
                               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm transition focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>

                    {{-- Time In / Time Out --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="manual-check-in" class="mb-1.5 block text-[0.8rem] font-bold text-[#06112e]">
                                Time In <span class="text-red-500">*</span>
                            </label>
                            <input type="time" id="manual-check-in" name="check_in" required
                                   value="{{ old('check_in') }}"
                                   class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm transition focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label for="manual-check-out" class="mb-1.5 block text-[0.8rem] font-bold text-[#06112e]">
                                Time Out
                            </label>
                            <input type="time" id="manual-check-out" name="check_out"
                                   value="{{ old('check_out') }}"
                                   class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm transition focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                    </div>

                    {{-- Status --}}
                    <div>
                        <label for="manual-status" class="mb-1.5 block text-[0.8rem] font-bold text-[#06112e]">
                            Status
                            <span class="ml-1 text-[0.75rem] font-normal text-slate-400">(auto-detected from Time In if blank)</span>
                        </label>
                        <select id="manual-status" name="status"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm transition focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <option value="">— Auto-detect —</option>
                            <option value="1" {{ old('status') == '1' ? 'selected' : '' }}>Present</option>
                            <option value="2" {{ old('status') == '2' ? 'selected' : '' }}>Late</option>
                            <option value="3" {{ old('status') == '3' ? 'selected' : '' }}>Absent</option>
                            <option value="4" {{ old('status') == '4' ? 'selected' : '' }}>On Leave</option>
                        </select>
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label for="manual-notes" class="mb-1.5 block text-[0.8rem] font-bold text-[#06112e]">
                            Notes
                        </label>
                        <textarea id="manual-notes" name="notes" rows="2"
                                  placeholder="Optional remarks…"
                                  class="w-full resize-none rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm transition focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">{{ old('notes') }}</textarea>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="mt-8 flex justify-end gap-3">
                    <button type="button"
                            onclick="document.getElementById('manualEntryModal').classList.replace('flex', 'hidden')"
                            class="rounded-lg border border-slate-200 bg-white px-4 py-2 font-medium text-sm text-[#06112e] shadow-sm transition hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit"
                            class="rounded-lg bg-[#1a56db] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-[#1e40af]">
                        Save Entry
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const calendarAttendanceData = @json($calendarData);
        const calendarStatusLabels = @json($attendanceStatusLabels);

        const calendarStatusClasses = {
            '1': 'bg-emerald-100 text-emerald-700 border-emerald-200',
            '2': 'bg-amber-100 text-amber-700 border-amber-200',
            '3': 'bg-rose-100 text-rose-700 border-rose-200',
            '4': 'bg-sky-100 text-sky-700 border-sky-200',
            '5': 'bg-slate-100 text-slate-500 border-slate-200',
            'present': 'bg-emerald-100 text-emerald-700 border-emerald-200',
            'late': 'bg-amber-100 text-amber-700 border-amber-200',
            'absent': 'bg-rose-100 text-rose-700 border-rose-200',
            'excused': 'bg-sky-100 text-sky-700 border-sky-200',
            'not_started': 'bg-slate-100 text-slate-500 border-slate-200',
        };

        window.calendarInitialized = false;

        function switchTab(tab) {
            const tabCalendar = document.getElementById('tab-calendar');
            const tabList = document.getElementById('tab-list');

            if (!tabCalendar || !tabList) return;

            if (tab === 'list') {
                document.getElementById('view-list').classList.remove('hidden');
                document.getElementById('view-list').classList.add('block');
                document.getElementById('view-calendar').classList.remove('block');
                document.getElementById('view-calendar').classList.add('hidden');

                tabList.classList.add('tab-active');
                tabList.classList.remove('tab-inactive');

                tabCalendar.classList.remove('tab-active');
                tabCalendar.classList.add('tab-inactive');
            } else {
                document.getElementById('view-list').classList.remove('block');
                document.getElementById('view-list').classList.add('hidden');
                document.getElementById('view-calendar').classList.remove('hidden');
                document.getElementById('view-calendar').classList.add('block');

                tabCalendar.classList.add('tab-active');
                tabCalendar.classList.remove('tab-inactive');

                tabList.classList.remove('tab-active');
                tabList.classList.add('tab-inactive');

                if (!window.calendarInitialized) {
                    const initialDateStr = '{{ $selectedDate }}';
                    const initialDay = '{{ $selectedDateCarbon->format("d") }}';
                    const initialDayName = '{{ strtoupper($selectedDateCarbon->format("l")) }}';
                    selectDate(initialDateStr, initialDay, initialDayName, null);
                    window.calendarInitialized = true;
                }
            }
        }

        function formatTimeValue(timeValue) {
            if (!timeValue) {
                return '—';
            }

            if (typeof timeValue === 'string' && /^\d{2}:\d{2}(:\d{2})?$/.test(timeValue)) {
                const [hoursRaw, minutesRaw] = timeValue.split(':');
                let hours = parseInt(hoursRaw, 10);
                if (Number.isNaN(hours)) {
                    return timeValue;
                }

                const minutes = minutesRaw.substring(0, 2);
                const ampm = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12;
                hours = hours ? hours : 12;

                return `${hours}:${minutes} ${ampm}`;
            }

            const parts = String(timeValue).split(':');
            if (parts.length < 2) {
                return String(timeValue);
            }

            let hours = parseInt(parts[0], 10);
            if (Number.isNaN(hours)) {
                return String(timeValue);
            }

            const minutes = parts[1].substring(0, 2);
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12;

            return `${hours}:${minutes} ${ampm}`;
        }

        function formatEmployeeName(nameValue) {
            const fullName = String(nameValue || 'Unknown').trim();
            if (!fullName) {
                return 'Unknown';
            }

            const parts = fullName.split(/\s+/).filter(Boolean);
            if (parts.length < 3) {
                return fullName;
            }

            const middleInitial = `${parts[1].charAt(0).toUpperCase()}.`;
            return `${parts[0]} ${middleInitial} ${parts[parts.length - 1]}`;
        }

        function selectDate(dateStr, dayNum, dayName, element) {
            document.getElementById('selectedDateNumber').textContent = dayNum;
            document.getElementById('selectedDateDay').textContent = dayName;

            // Remove active style from all day cells
            document.querySelectorAll('.calendar-day-cell').forEach(cell => {
                cell.classList.remove('bg-blue-50/70', 'border-blue-300', 'z-10');
                cell.classList.add('bg-white', 'border-slate-100');
            });

            // Find element if not provided (e.g. on load)
            if (!element) {
                element = document.querySelector(`.calendar-day-cell[data-date="${dateStr}"]`);
            }

            // Add active style to the selected cell
            if (element) {
                element.classList.remove('bg-white', 'border-slate-100');
                element.classList.add('bg-blue-50/70', 'border-blue-300', 'z-10');
            }

            const listContainer = document.getElementById('attendanceRecordsList');
            listContainer.innerHTML = '';

            const records = calendarAttendanceData[dateStr] || [];

            if (records.length === 0) {
                listContainer.innerHTML = `
                    <div class="text-center py-8">
                        <i class="ti ti-calendar-x text-3xl text-slate-300 mb-2 block"></i>
                        <p class="text-sm text-slate-400">No attendance records for this date.</p>
                    </div>
                `;
                return;
            }

            records.forEach(record => {
                const timeIn = formatTimeValue(record.check_in_time ?? record.check_in);
                const timeOut = formatTimeValue(record.check_out_time ?? record.check_out);

                let computedStatus = record.status;
                const statusKey = isNaN(computedStatus) ? String(computedStatus).toLowerCase() : parseInt(computedStatus);
                const statusLabel = calendarStatusLabels[statusKey] || 'Unknown';
                const statusClass = calendarStatusClasses[statusKey] || 'bg-slate-100 text-slate-700 border-slate-200';

                // Get name - Eloquent accessors aren't serialized by default, so we fallback to .name
                const rawEmployeeName = record.employee_display_name || (record.user ? (
                    (record.user.employee && record.user.employee.full_name) || record.user.display_name || record.user.name
                ) : 'Unknown');
                const employeeName = formatEmployeeName(rawEmployeeName);

                // Get shift details for cross-day / half day badges
                const shift = record.shift || (record.user && record.user.employee && record.user.employee.current_shift ? record.user.employee.current_shift.shift : null);
                let shiftBadgesHtml = '';
                if (shift) {
                    let workedHours = null;

                    if ((record.check_in_time ?? record.check_in) && (record.check_out_time ?? record.check_out)) {
                        const checkIn = new Date(`1970-01-01T${record.check_in_time ?? record.check_in}`);
                        const checkOut = new Date(`1970-01-01T${record.check_out_time ?? record.check_out}`);

                        if (!Number.isNaN(checkIn.getTime()) && !Number.isNaN(checkOut.getTime())) {
                            let workedMinutes = (checkOut.getTime() - checkIn.getTime()) / 60000;

                            if (shift.crosses_midnight && workedMinutes < 0) {
                                workedMinutes += 24 * 60;
                            }

                            const breakMins = shift && shift.break_minutes ? parseInt(shift.break_minutes) : 0;
                            workedHours = Math.max(0, workedMinutes - breakMins) / 60;
                        }
                    }

                    if (shift.crosses_midnight) {
                        shiftBadgesHtml += `
                            <span class="inline-flex items-center gap-1 rounded bg-indigo-50 border border-indigo-200 px-1.5 py-0.5 text-[9px] font-bold text-indigo-700 uppercase tracking-wide" title="Cross-day Shift (Crosses Midnight)">
                                <i class="ti ti-moon"></i> Cross-day
                            </span>
                        `;
                    }
                    if (workedHours !== null && workedHours > 0 && workedHours <= 4.0) {
                        shiftBadgesHtml += `
                            <span class="inline-flex items-center gap-1 rounded bg-cyan-50 border border-cyan-200 px-1.5 py-0.5 text-[9px] font-bold text-cyan-700 uppercase tracking-wide" title="Worked half day (4 hours or less)">
                                <i class="ti ti-circle-half"></i> HALF DAY
                            </span>
                        `;
                    }
                }

                const card = document.createElement('div');
                card.className = 'bg-white border border-slate-200 rounded-xl p-4 shadow-sm transition hover:shadow-md';
                card.innerHTML = `
                    <p class="text-[0.7rem] font-bold uppercase tracking-wider text-slate-400 mb-0.5">In: ${timeIn}</p>
                    <p class="text-[0.7rem] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Out: ${timeOut}</p>
                    <p class="text-sm font-bold text-[#06112e] mb-3">${employeeName}</p>
                    <div class="flex flex-wrap gap-1.5 items-center">
                        <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[0.65rem] font-bold uppercase tracking-wider ${statusClass}">${statusLabel}</span>
                        ${shiftBadgesHtml}
                    </div>
                `;
                listContainer.appendChild(card);
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab') || 'calendar';
            switchTab(activeTab);

            // Dynamic Export URL update
            const filterSearch = document.getElementById('filterSearch');
            const filterStatus = document.getElementById('filterStatus');
            const filterDate = document.getElementById('filterDate');
            const btnExport = document.getElementById('btnExport');

            function updateExportUrl() {
                if (!btnExport) return;
                const q = filterSearch ? filterSearch.value : '';
                const status = filterStatus ? filterStatus.value : '';
                const date = filterDate ? filterDate.value : '';

                let url = "{{ route('timekeeping.export') }}";
                const params = [];
                if (q) params.push(`q=${encodeURIComponent(q)}`);
                if (status) params.push(`status=${encodeURIComponent(status)}`);
                if (date) params.push(`date=${encodeURIComponent(date)}`);

                if (params.length > 0) {
                    url += '?' + params.join('&');
                }
                btnExport.href = url;
            }

            if (filterSearch) filterSearch.addEventListener('input', updateExportUrl);
            if (filterStatus) filterStatus.addEventListener('change', updateExportUrl);
            if (filterDate) filterDate.addEventListener('change', updateExportUrl);

            updateExportUrl();
        });

        @php
            $employeeShiftData = $users->mapWithKeys(function ($emp) {
                $assignments = $emp->currentShifts;
                if ($assignments->isEmpty()) return [$emp->id => []];

                $shiftsInfo = [];
                foreach ($assignments as $assignment) {
                    $shift = $assignment->shift;
                    if (!$shift) continue;

                    $start = \Carbon\Carbon::createFromFormat('H:i:s', $shift->start_time)->format('g:i A');
                    $end = \Carbon\Carbon::createFromFormat('H:i:s', $shift->end_time)->format('g:i A');
                    $days = is_array($shift->days_of_week) ? $shift->days_of_week : [];

                    $shiftsInfo[] = [
                        'name'  => $shift->name,
                        'time'  => $start . ' – ' . $end,
                        'days'  => $days,
                    ];
                }

                return [$emp->id => $shiftsInfo];
            })->all();
        @endphp

        (function () {
            const openAttendanceMap = @json($openAttendanceMap ?? []);
            const employeeShiftMap = @json($employeeShiftData);

            const userSelect = document.getElementById('manual-employee-id');
            const dateInput = document.getElementById('manual-date');
            const checkInInput = document.getElementById('manual-check-in');
            const shiftInfoEl = document.getElementById('employee-shift-info');
            const shiftTextEl = document.getElementById('employee-shift-text');

            function updateShiftInfo() {
                if (!userSelect || !shiftInfoEl || !shiftTextEl) return;
                const empId = userSelect.value;
                const shifts = employeeShiftMap[empId];

                if (shifts && shifts.length > 0) {
                    const dayAbbr = { 'Monday': 'Mon', 'Tuesday': 'Tue', 'Wednesday': 'Wed', 'Thursday': 'Thu', 'Friday': 'Fri', 'Saturday': 'Sat', 'Sunday': 'Sun' };
                    let html = '';
                    shifts.forEach((shift) => {
                        const daysStr = (shift.days || []).map(d => dayAbbr[d] || d).join(', ');
                        html += `
                            <div class="flex items-center gap-1.5 mt-1 text-slate-500">
                                <i class="ti ti-clock text-slate-400"></i>
                                <span>Shift: ${shift.time}${daysStr ? ` · ${daysStr}` : ''}</span>
                            </div>
                        `;
                    });
                    shiftTextEl.innerHTML = html;
                    shiftInfoEl.classList.remove('hidden');
                } else {
                    shiftInfoEl.classList.add('hidden');
                    shiftTextEl.innerHTML = '';
                }
            }

            if (userSelect) {
                userSelect.addEventListener('change', updateShiftInfo);
                updateShiftInfo(); // run on load if value already selected (old() repopulation)
            }

            function getCurrentTimeValue() {
                const now = new Date();
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');

                return `${hours}:${minutes}`;
            }

            function setEditableCurrentTime() {
                checkInInput.value = getCurrentTimeValue();
                checkInInput.dataset.autoFilled = 'current';
                checkInInput.readOnly = false;
                checkInInput.classList.remove('bg-slate-100', 'text-slate-600', 'cursor-not-allowed');
            }

            function applyOpenAttendancePrefill() {
                if (!userSelect || !dateInput || !checkInInput) {
                    return;
                }

                const selectedUserId = userSelect.value;
                const selectedDate = dateInput.value;

                if (!selectedUserId || !selectedDate) {
                    return;
                }

                const key = `${selectedUserId}|${selectedDate}`;
                const openTimeIn = openAttendanceMap[key];

                if (openTimeIn && (!checkInInput.value || checkInInput.dataset.autoFilled === '1' || checkInInput.dataset.autoFilled === 'current')) {
                    checkInInput.value = openTimeIn;
                    checkInInput.dataset.autoFilled = '1';
                    checkInInput.readOnly = true;
                    checkInInput.classList.add('bg-slate-100', 'text-slate-600', 'cursor-not-allowed');
                } else if (!openTimeIn && (!checkInInput.value || checkInInput.dataset.autoFilled === '1' || checkInInput.dataset.autoFilled === 'current')) {
                    setEditableCurrentTime();
                } else if (checkInInput.dataset.autoFilled === '1') {
                    checkInInput.readOnly = false;
                    checkInInput.dataset.autoFilled = '0';
                    checkInInput.classList.remove('bg-slate-100', 'text-slate-600', 'cursor-not-allowed');
                }
            }

            if (userSelect && dateInput && checkInInput) {
                userSelect.addEventListener('change', applyOpenAttendancePrefill);
                dateInput.addEventListener('change', applyOpenAttendancePrefill);
                checkInInput.addEventListener('input', function () {
                    if (checkInInput.readOnly) {
                        return;
                    }
                    checkInInput.dataset.autoFilled = '0';
                });
                document.addEventListener('DOMContentLoaded', function () {
                    if (!checkInInput.value) {
                        setEditableCurrentTime();
                    }

                    applyOpenAttendancePrefill();
                });
                window.addEventListener('load', function () {
                    if (!checkInInput.value) {
                        setEditableCurrentTime();
                    }

                    applyOpenAttendancePrefill();
                });
            }
        })();
    </script>

</x-app-layout>
