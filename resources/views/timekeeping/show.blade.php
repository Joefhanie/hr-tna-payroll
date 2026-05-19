<x-app-layout>
    <x-slot:title>{{ $user->display_name }} - Attendance Records</x-slot:title>
    <x-slot:header>Attendance Records</x-slot:header>

    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold">{{ $user->display_name }}</h1>
            <p class="text-sm text-slate-500">Attendance history and timekeeping records</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('timekeeping.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">Back to Timekeeping</a>
        </div>
    </div>

    @php
        $attendanceStatusLabels = [
            1 => 'Present',
            2 => 'Late',
            3 => 'Absent',
            4 => 'Excused',
            'present' => 'Present',
            'late' => 'Late',
            'absent' => 'Absent',
            'excused' => 'Excused',
        ];
        
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
    @endphp

    <div class="card overflow-hidden">
        <div class="border-b border-slate-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-slate-900">Attendance History</h2>
            <p class="mt-1 text-sm text-slate-600">Showing recent records for {{ $user->display_name }}</p>
        </div>

        @if ($attendances->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-6 py-3">Date</th>
                            <th class="px-6 py-3">Time In</th>
                            <th class="px-6 py-3">Time Out</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($attendances as $record)
                            @php
                                $statusKey = is_numeric($record->status) ? (int) $record->status : strtolower((string) $record->status);
                                $statusLabel = $attendanceStatusLabels[$statusKey] ?? ucfirst((string) $record->status);
                                $pillClass = $statusClasses[$statusKey] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                            @endphp
                            <tr class="group hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4 font-medium text-slate-900">{{ $record->attendance_date->format('M d, Y') }} <span class="text-xs text-slate-400 font-normal ml-1">{{ $record->attendance_date->format('D') }}</span></td>
                                <td class="px-6 py-4 text-slate-900">{{ $record->check_in ? $record->check_in->format('h:i A') : '—' }}</td>
                                <td class="px-6 py-4 text-slate-900">{{ $record->check_out ? $record->check_out->format('h:i A') : '—' }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold {{ $pillClass }}">{{ $statusLabel }}</span>
                                </td>
                                <td class="px-6 py-4 text-slate-600">{{ $record->notes ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            @if ($attendances->hasPages())
                <div class="border-t border-slate-200 px-6 py-4">
                    {{ $attendances->links() }}
                </div>
            @endif
        @else
            <div class="px-6 py-12 text-center text-sm text-slate-500">
                <i class="ti ti-calendar-x text-4xl text-slate-300 mb-3 block"></i>
                <p>No attendance records found for this employee.</p>
            </div>
        @endif
    </div>
</x-app-layout>
