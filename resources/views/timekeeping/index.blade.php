<x-app-layout>
    <x-slot:title>Timekeeping</x-slot:title>
    <x-slot:header>Timekeeping</x-slot:header>

    <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-[1.65rem] font-bold text-[#06112e]">Timekeeping</h1>
            <p class="mt-1 text-sm text-slate-500">Track and manage employee attendance records.</p>
        </div>
        <div class="flex gap-2">
            <button type="button"
                    onclick="document.getElementById('manualEntryModal').classList.replace('hidden', 'flex')"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#1a56db] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1e40af]">
                <i class="ti ti-pencil text-base"></i>
                Manual Entry
            </button>
        </div>
    </div>

    <div class="mb-4 h-px w-full bg-slate-200"></div>

    @if (session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- Summary cards --}}
    <div class="grid gap-4 md:grid-cols-3">
        <div class="nw-panel rounded-2xl p-6 transition hover:-translate-y-0.5 hover:shadow-xl">
            <p class="text-sm font-medium text-slate-500">Present today</p>
            <p class="mt-3 text-3xl font-extrabold text-slate-900">{{ $presentToday }}</p>
        </div>

        <div class="nw-panel rounded-2xl p-6 transition hover:-translate-y-0.5 hover:shadow-xl">
            <p class="text-sm font-medium text-slate-500">Late</p>
            <p class="mt-3 text-3xl font-extrabold text-slate-900">{{ $lateToday }}</p>
        </div>

        <div class="nw-panel rounded-2xl p-6 transition hover:-translate-y-0.5 hover:shadow-xl">
            <p class="text-sm font-medium text-slate-500">Absent</p>
            <p class="mt-3 text-3xl font-extrabold text-slate-900">{{ $absentToday }}</p>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="mb-4">
        <div class="border-b border-slate-200">
            <nav class="-mb-px flex gap-6" aria-label="Tabs">
                <button type="button" onclick="switchTab('calendar')" id="tab-calendar" class="border-[#1a56db] text-[#1a56db] whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition">Calendar View</button>
                <button type="button" onclick="switchTab('list')" id="tab-list" class="border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition">List View</button>
            </nav>
        </div>
    </div>

    {{-- Calendar View --}}
    <div id="view-calendar" class="block mb-12">
        @php
            $startOfMonth = \Carbon\Carbon::now()->startOfMonth();
            $endOfMonth = clone $startOfMonth;
            $endOfMonth->endOfMonth();
            $endOfPrevMonth = (clone $startOfMonth)->subDay();
            $daysInMonth = $endOfMonth->daysInMonth;
            $daysInPrevMonth = $endOfPrevMonth->daysInMonth;
            $firstDayOfWeek = $startOfMonth->dayOfWeek; // 0 (Sun) to 6 (Sat)
            $monthName = $startOfMonth->format('F');
            $year = $startOfMonth->format('Y');
            $todayStr = \Carbon\Carbon::now()->toDateString();
        @endphp
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Left Sidebar -->
            <div class="lg:col-span-1 bg-[#f8f9fc] border border-slate-200 rounded-2xl shadow-sm flex flex-col h-[700px] overflow-hidden">
                <div class="p-8 border-b border-slate-200 bg-white text-center">
                    <h2 id="selectedDateNumber" class="text-7xl font-black text-[#06112e] tracking-tight">{{ now()->format('d') }}</h2>
                    <p id="selectedDateDay" class="text-sm font-bold uppercase tracking-widest text-slate-500 mt-2">{{ now()->format('l') }}</p>
                </div>
                <div class="p-6 flex-1 overflow-y-auto">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-5">Attendance Records</h3>
                    <div id="attendanceRecordsList" class="space-y-4">
                        <!-- JS populated -->
                    </div>
                </div>
            </div>

            <!-- Right side Calendar -->
            <div class="lg:col-span-3 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden flex flex-col h-[700px]">
                <div class="bg-[#06112e] text-white p-6 flex justify-between items-center">
                    <h2 class="text-xl font-bold tracking-wider uppercase">{{ $monthName }}</h2>
                    <h2 class="text-xl font-bold">{{ $year }}</h2>
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
                            <div class="bg-slate-50 border-r border-b border-slate-100 p-2 min-h-[80px] flex items-start justify-center pt-4">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold text-slate-300">
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
                                    . ' | E: ' . $calendarDayStatusCounts['excused'];
                            @endphp
                            <div class="bg-white border-r border-b border-slate-100 p-2 cursor-pointer hover:bg-[#f0f4ff] transition group relative flex flex-col items-center pt-4 min-h-[80px]" onclick="selectDate('{{ $currentDateStr }}', {{ $day }}, '{{ strtoupper($currentDateObj->format('l')) }}', this)">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold {{ $isToday ? 'bg-[#1a56db] text-white' : 'text-slate-700 group-hover:text-[#1a56db]' }}">
                                    {{ $day }}
                                </span>
                                @if($hasEvents)
                                    <span class="absolute right-2 top-2 inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500 text-[0.62rem] font-bold text-white shadow-sm" title="{{ $calendarStatusSummary }}">
                                        {{ $calendarDayTotal }}
                                    </span>
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
                            <div class="bg-slate-50 border-r border-b border-slate-100 p-2 min-h-[80px] flex items-start justify-center pt-4">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold text-slate-300">
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
            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                <table class="min-w-full text-sm">
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

                                $computedStatus = $attendance->status;
                                if ($shift && $attendance->check_in) {
                                    $computedStatus = $shift->getAttendanceStatusForClockIn($attendance->check_in, 10)['key'];
                                }

                                $statusKey = is_numeric($computedStatus) ? (int) $computedStatus : strtolower((string) $computedStatus);
                                $statusLabel = $attendanceStatusLabels[$statusKey] ?? ucfirst((string) $computedStatus);
                                $statusClasses = [
                                    1 => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                    2 => 'bg-amber-100 text-amber-700 border-amber-200',
                                    3 => 'bg-rose-100 text-rose-700 border-rose-200',
                                    4 => 'bg-sky-100 text-sky-700 border-sky-200',
                                    'present' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                    'late' => 'bg-amber-100 text-amber-700 border-amber-200',
                                    'absent' => 'bg-rose-100 text-rose-700 border-rose-200',
                                    'excused' => 'bg-sky-100 text-sky-700 border-sky-200',
                                ];
                                $pillClass = $statusClasses[$statusKey] ?? 'bg-slate-100 text-slate-700 border-slate-200';

                                $employeeDisplayName = trim((string) ($attendance->user->display_name ?? $attendance->user->name ?? 'Unknown'));
                                $employeeNameParts = preg_split('/\s+/', $employeeDisplayName, -1, PREG_SPLIT_NO_EMPTY);
                                if (count($employeeNameParts) >= 3) {
                                    $employeeDisplayName = $employeeNameParts[0] . ' ' . strtoupper(substr($employeeNameParts[1], 0, 1)) . '. ' . $employeeNameParts[count($employeeNameParts) - 1];
                                }
                            @endphp
                            <tr class="group hover:bg-slate-50/50 transition">
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $employeeDisplayName }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $attendance->attendance_date->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ $displayShiftTime ?? '—' }}
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
            </div>
        </section>
    </div>

    {{-- ── Manual Entry Modal ─────────────────────────────── --}}
    <div id="manualEntryModal"
         class="{{ $errors->any() ? 'flex' : 'hidden' }} fixed inset-0 z-30 items-center justify-center bg-black/40 p-4 transition-opacity"
         style="padding-left: var(--sidebar-width);">

        <div class="w-full max-w-lg rounded-2xl border border-slate-300 bg-white shadow-[0_25px_50px_-12px_rgba(0,0,0,0.45)] ring-4 ring-black/5">

            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h3 class="text-lg font-bold text-[#06112e]">Manual Time Entry</h3>
                <button type="button"
                        onclick="document.getElementById('manualEntryModal').classList.replace('flex', 'hidden')"
                        class="text-slate-400 transition hover:text-slate-600">
                    <i class="ti ti-x text-xl"></i>
                </button>
            </div>

            {{-- Validation errors --}}
            @if($errors->any())
                <div class="mx-6 mt-5 rounded-lg border border-red-200 bg-red-50 p-3">
                    <ul class="space-y-1 text-[0.8rem] text-red-700">
                        @foreach($errors->all() as $error)
                            <li>• {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

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
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]">
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
                    </div>

                    {{-- Date --}}
                    <div>
                        <label for="manual-date" class="mb-1.5 block text-[0.8rem] font-bold text-[#06112e]">
                            Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" id="manual-date" name="attendance_date" required
                               value="{{ old('attendance_date', now()->toDateString()) }}"
                               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]">
                    </div>

                    {{-- Time In / Time Out --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="manual-check-in" class="mb-1.5 block text-[0.8rem] font-bold text-[#06112e]">
                                Time In <span class="text-red-500">*</span>
                            </label>
                            <input type="time" id="manual-check-in" name="check_in" required
                                   value="{{ old('check_in') }}"
                                   class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]">
                        </div>

                        <div>
                            <label for="manual-check-out" class="mb-1.5 block text-[0.8rem] font-bold text-[#06112e]">
                                Time Out
                            </label>
                            <input type="time" id="manual-check-out" name="check_out"
                                   value="{{ old('check_out') }}"
                                   class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]">
                        </div>
                    </div>

                    {{-- Status --}}
                    <div>
                        <label for="manual-status" class="mb-1.5 block text-[0.8rem] font-bold text-[#06112e]">
                            Status
                            <span class="ml-1 text-[0.75rem] font-normal text-slate-400">(auto-detected from Time In if blank)</span>
                        </label>
                        <select id="manual-status" name="status"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]">
                            <option value="">— Auto-detect —</option>
                            <option value="1" {{ old('status') == '1' ? 'selected' : '' }}>Present</option>
                            <option value="2" {{ old('status') == '2' ? 'selected' : '' }}>Late</option>
                            <option value="3" {{ old('status') == '3' ? 'selected' : '' }}>Absent</option>
                            <option value="4" {{ old('status') == '4' ? 'selected' : '' }}>Excused</option>
                        </select>
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label for="manual-notes" class="mb-1.5 block text-[0.8rem] font-bold text-[#06112e]">
                            Notes
                        </label>
                        <textarea id="manual-notes" name="notes" rows="2"
                                  placeholder="Optional remarks…"
                                  class="w-full resize-none rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]">{{ old('notes') }}</textarea>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="mt-8 flex justify-end gap-3">
                    <button type="button"
                            onclick="document.getElementById('manualEntryModal').classList.replace('flex', 'hidden')"
                            class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-[#06112e] shadow-sm transition hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit"
                            class="rounded-lg bg-[#1a56db] px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-[#1e40af]">
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
            'present': 'bg-emerald-100 text-emerald-700 border-emerald-200',
            'late': 'bg-amber-100 text-amber-700 border-amber-200',
            'absent': 'bg-rose-100 text-rose-700 border-rose-200',
            'excused': 'bg-sky-100 text-sky-700 border-sky-200',
        };

        window.calendarInitialized = false;

        function switchTab(tab) {
            if (tab === 'list') {
                document.getElementById('view-list').classList.remove('hidden');
                document.getElementById('view-list').classList.add('block');
                document.getElementById('view-calendar').classList.remove('block');
                document.getElementById('view-calendar').classList.add('hidden');

                document.getElementById('tab-list').classList.add('border-[#1a56db]', 'text-[#1a56db]');
                document.getElementById('tab-list').classList.remove('border-transparent', 'text-slate-500');

                document.getElementById('tab-calendar').classList.remove('border-[#1a56db]', 'text-[#1a56db]');
                document.getElementById('tab-calendar').classList.add('border-transparent', 'text-slate-500');
            } else {
                document.getElementById('view-list').classList.remove('block');
                document.getElementById('view-list').classList.add('hidden');
                document.getElementById('view-calendar').classList.remove('hidden');
                document.getElementById('view-calendar').classList.add('block');

                document.getElementById('tab-calendar').classList.add('border-[#1a56db]', 'text-[#1a56db]');
                document.getElementById('tab-calendar').classList.remove('border-transparent', 'text-slate-500');

                document.getElementById('tab-list').classList.remove('border-[#1a56db]', 'text-[#1a56db]');
                document.getElementById('tab-list').classList.add('border-transparent', 'text-slate-500');

                if (!window.calendarInitialized) {
                    const todayStr = '{{ \Carbon\Carbon::now()->toDateString() }}';
                    const todayDay = '{{ \Carbon\Carbon::now()->format("d") }}';
                    const todayDayName = '{{ strtoupper(\Carbon\Carbon::now()->format("l")) }}';
                    selectDate(todayStr, todayDay, todayDayName, null);
                    window.calendarInitialized = true;
                }
            }
        }

        function formatTimeValue(timeValue) {
            if (!timeValue) {
                return '—';
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
                const timeIn = formatTimeValue(record.check_in);
                const timeOut = formatTimeValue(record.check_out);

                let computedStatus = record.status;
                const statusKey = isNaN(computedStatus) ? String(computedStatus).toLowerCase() : parseInt(computedStatus);
                const statusLabel = calendarStatusLabels[statusKey] || 'Unknown';
                const statusClass = calendarStatusClasses[statusKey] || 'bg-slate-100 text-slate-700 border-slate-200';

                // Get name - Eloquent accessors aren't serialized by default, so we fallback to .name
                const rawEmployeeName = record.user ? (record.user.display_name || record.user.name) : 'Unknown';
                const employeeName = formatEmployeeName(rawEmployeeName);

                const card = document.createElement('div');
                card.className = 'bg-white border border-slate-200 rounded-xl p-4 shadow-sm transition hover:shadow-md';
                card.innerHTML = `
                    <p class="text-[0.7rem] font-bold uppercase tracking-wider text-slate-400 mb-0.5">In: ${timeIn}</p>
                    <p class="text-[0.7rem] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Out: ${timeOut}</p>
                    <p class="text-sm font-bold text-[#06112e] mb-3">${employeeName}</p>
                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[0.65rem] font-bold uppercase tracking-wider ${statusClass}">${statusLabel}</span>
                `;
                listContainer.appendChild(card);
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            switchTab('calendar');
        });

        (function () {
            const openAttendanceMap = @json($openAttendanceMap ?? []);
            const userSelect = document.getElementById('manual-user-id');
            const dateInput = document.getElementById('manual-date');
            const checkInInput = document.getElementById('manual-check-in');

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
