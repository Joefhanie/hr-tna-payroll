<x-app-layout>
    <x-slot:title>Timekeeping</x-slot:title>
    <x-slot:header>Timekeeping</x-slot:header>

    <div class="space-y-4">

        {{-- Flash success banner --}}
        @if(session('success'))
            <div id="flash-success"
                 class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-medium text-emerald-700 shadow-sm">
                <i class="fas fa-circle-check"></i>
                {{ session('success') }}
                <button onclick="document.getElementById('flash-success').remove()"
                        class="ml-auto text-emerald-500 hover:text-emerald-700">
                    <i class="fas fa-xmark"></i>
                </button>
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

        <div class="nw-panel rounded-2xl px-6 py-5">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-sky-700">
                        <i class="fas fa-clock text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">Your status</p>
                        <p class="text-xl font-bold text-slate-900">
                            @if($activeAttendance)
                                Clocked in at {{ $activeAttendance->check_in ? $activeAttendance->check_in->format('h:i A') : '—' }}
                            @else
                                No attendance yet today
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-slate-50">
                        <i class="fas fa-arrow-right-to-bracket"></i>
                        Clock In
                    </button>
                    <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-blue-700 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800">
                        <i class="fas fa-arrow-right-from-bracket"></i>
                        Clock Out
                    </button>
                </div>
            </div>
        </div>

        <div class="nw-panel overflow-hidden rounded-2xl">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-4 text-left text-sm font-semibold text-slate-700">Employee</th>
                            <th class="px-4 py-4 text-left text-sm font-semibold text-slate-700">Date</th>
                            <th class="px-4 py-4 text-left text-sm font-semibold text-slate-700">Time In</th>
                            <th class="px-4 py-4 text-left text-sm font-semibold text-slate-700">Time Out</th>
                            <th class="px-4 py-4 text-left text-sm font-semibold text-slate-700">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse($todayAttendance as $attendance)
                            @php
                                $statusKey = is_numeric($attendance->status) ? (int) $attendance->status : strtolower((string) $attendance->status);
                                $statusLabel = $attendanceStatusLabels[$statusKey] ?? ucfirst((string) $attendance->status);
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
                                $employeeHistory = collect($recentAttendance[$attendance->user_id] ?? [])->map(function ($history) use ($attendanceStatusLabels) {
                                    $historyStatusKey = is_numeric($history->status) ? (int) $history->status : strtolower((string) $history->status);

                                    return [
                                        'date' => $history->attendance_date?->format('M d, Y'),
                                        'check_in' => $history->check_in ? $history->check_in->format('h:i A') : '—',
                                        'check_out' => $history->check_out ? $history->check_out->format('h:i A') : '—',
                                        'status' => $attendanceStatusLabels[$historyStatusKey] ?? ucfirst((string) $history->status),
                                        'notes' => $history->notes ?: '—',
                                    ];
                                })->values();
                            @endphp
                            <tr class="attendance-row cursor-pointer transition hover:bg-slate-50"
                                role="button"
                                tabindex="0"
                                data-employee-name="{{ $attendance->user->display_name }}"
                                data-attendance-history='@json($employeeHistory)'>
                                <td class="px-4 py-4 text-sm font-semibold text-slate-900">
                                    <span class="font-semibold text-slate-900">{{ $attendance->user->display_name }}</span>
                                </td>
                                <td class="px-4 py-4 text-sm text-slate-600">{{ $attendance->attendance_date->format('M d, Y') }}</td>
                                <td class="px-4 py-4 text-sm text-slate-900">{{ $attendance->check_in ? $attendance->check_in->format('H:i') : '—' }}</td>
                                <td class="px-4 py-4 text-sm text-slate-900">{{ $attendance->check_out ? $attendance->check_out->format('H:i') : '—' }}</td>
                                <td class="px-4 py-4 text-sm">
                                    <span class="inline-flex items-center rounded-full border px-3 py-1 font-medium {{ $pillClass }}">{{ $statusLabel }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">No attendance records yet</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Attendance History Modal --}}
    <div id="attendance-history-modal"
                        class="fixed inset-y-0 left-[var(--sidebar-width)] right-0 z-50 hidden items-center justify-center p-4"
         role="dialog" aria-modal="true" aria-labelledby="attendance-history-title">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"
             onclick="closeAttendanceModal()"></div>

                     <div class="relative w-full overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-200"
                             style="width: 80%; max-width: none;">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                <div>
                    <h2 id="attendance-history-title" class="text-base font-bold text-slate-900">Attendance Records</h2>
                    <p id="attendance-history-subtitle" class="mt-1 text-sm text-slate-500"></p>
                </div>
                <button type="button"
                        onclick="closeAttendanceModal()"
                        class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                    <i class="fas fa-xmark text-base"></i>
                </button>
            </div>

            <div class="max-h-[calc(100vh-10rem)] overflow-y-auto px-6 py-5">
                <div id="attendance-history-empty" class="hidden rounded-xl border border-dashed border-slate-200 px-5 py-8 text-center text-sm text-slate-500">
                    No attendance records found for this employee.
                </div>

                <div id="attendance-history-table-wrap" class="overflow-hidden rounded-xl border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Time In</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Time Out</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Notes</th>
                            </tr>
                        </thead>
                        <tbody id="attendance-history-body" class="divide-y divide-slate-200 bg-white"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>



    <script>
        document.querySelectorAll('.attendance-row').forEach(function (row) {
            row.addEventListener('click', function () {
                openAttendanceModal(row.dataset.employeeName, JSON.parse(row.dataset.attendanceHistory || '[]'));
            });

            row.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openAttendanceModal(row.dataset.employeeName, JSON.parse(row.dataset.attendanceHistory || '[]'));
                }
            });
        });

        function openAttendanceModal(employeeName, records) {
            const modal = document.getElementById('attendance-history-modal');
            const subtitle = document.getElementById('attendance-history-subtitle');
            const body = document.getElementById('attendance-history-body');
            const emptyState = document.getElementById('attendance-history-empty');
            const tableWrap = document.getElementById('attendance-history-table-wrap');

            subtitle.textContent = employeeName;
            body.innerHTML = '';

            if (!records || !records.length) {
                emptyState.classList.remove('hidden');
                tableWrap.classList.add('hidden');
            } else {
                emptyState.classList.add('hidden');
                tableWrap.classList.remove('hidden');

                records.forEach(function (record) {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td class="px-4 py-3 text-sm text-slate-700">${record.date ?? '—'}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">${record.check_in ?? '—'}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">${record.check_out ?? '—'}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">${record.status ?? '—'}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">${record.notes ?? '—'}</td>
                    `;
                    body.appendChild(row);
                });
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeAttendanceModal() {
            const modal = document.getElementById('attendance-history-modal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeAttendanceModal();
            }
        });
    </script>

</x-app-layout>
