<x-app-layout>
    <x-slot:title>Timekeeping</x-slot:title>
    <x-slot:header>Timekeeping</x-slot:header>

    <div class="space-y-4">
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
                            @endphp
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-4 py-4 text-sm font-semibold text-slate-900">{{ $attendance->user->name }}</td>
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
</x-app-layout>
