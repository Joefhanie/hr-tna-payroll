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
                    class="inline-flex items-center gap-2 rounded-[0.5rem] bg-[#1a56db] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1e40af]">
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

    <section class="card p-6">
        <!-- Search and Filter Bar -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex-1">
                <label for="search-employee" class="mb-1.5 block text-sm font-medium text-slate-700">Search Employee</label>
                <div class="relative">
                    <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text"
                           id="search-employee"
                           placeholder="Search by employee name…"
                           class="w-full rounded-lg border border-slate-300 pl-10 pr-3 py-2.5 text-sm transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
            </div>
            <div class="flex-1 sm:max-w-xs">
                <label for="filter-status" class="mb-1.5 block text-sm font-medium text-slate-700">Filter by Status</label>
                <select id="filter-status"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm transition focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">All Statuses</option>
                    <option value="present">Present</option>
                    <option value="late">Late</option>
                    <option value="absent">Absent</option>
                    <option value="excused">Excused</option>
                </select>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <table class="min-w-full text-sm" id="attendance-table">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Employee</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Time In</th>
                        <th class="px-4 py-3">Time Out</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($todayAttendance as $attendance)
                        @php
                            $statusKey = is_numeric($attendance->status) ? (int) $attendance->status : strtolower((string) $attendance->status);
                            $statusLabel = $attendanceStatusLabels[$statusKey] ?? ucfirst((string) $attendance->status);
                            $statusLabelLower = strtolower($statusLabel);
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
                        <tr class="group hover:bg-slate-50/50 transition attendance-row"
                            data-employee-name="{{ $attendance->user->display_name }}"
                            data-status="{{ $statusLabelLower }}">
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $attendance->user->display_name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $attendance->attendance_date->format('M d, Y') }}</td>
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
                        <tr id="empty-state">
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">No attendance records yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search-employee');
            const statusFilter = document.getElementById('filter-status');
            const attendanceTable = document.getElementById('attendance-table');

            function filterTable() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                const selectedStatus = statusFilter.value.toLowerCase().trim();

                const rows = attendanceTable.querySelectorAll('tbody tr.attendance-row');
                let visibleCount = 0;

                rows.forEach(row => {
                    const employeeName = row.getAttribute('data-employee-name').toLowerCase();
                    const status = row.getAttribute('data-status').toLowerCase();

                    const matchesSearch = employeeName.includes(searchTerm);
                    const matchesStatus = !selectedStatus || status === selectedStatus;

                    if (matchesSearch && matchesStatus) {
                        row.classList.remove('hidden');
                        visibleCount++;
                    } else {
                        row.classList.add('hidden');
                    }
                });

                // Show or hide empty state
                const emptyState = document.getElementById('empty-state');
                if (emptyState) {
                    if (visibleCount === 0) {
                        emptyState.classList.remove('hidden');
                    } else {
                        emptyState.classList.add('hidden');
                    }
                }
            }

            searchInput.addEventListener('input', filterTable);
            statusFilter.addEventListener('change', filterTable);
        });
    </script>

        </div>
    </section>

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
                        <label for="manual-user-id" class="mb-1.5 block text-[0.8rem] font-bold text-[#06112e]">
                            Employee <span class="text-red-500">*</span>
                        </label>
                        <select id="manual-user-id" name="user_id" required
                                class="w-full rounded-[0.5rem] border border-slate-300 bg-white px-3 py-2 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]">
                            <option value="" disabled selected>Select employee…</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}"
                                    {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->display_name }}
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
                               class="w-full rounded-[0.5rem] border border-slate-300 bg-white px-3 py-2 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]">
                    </div>

                    {{-- Time In / Time Out --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="manual-check-in" class="mb-1.5 block text-[0.8rem] font-bold text-[#06112e]">
                                Time In <span class="text-red-500">*</span>
                            </label>
                            <input type="time" id="manual-check-in" name="check_in" required
                                   value="{{ old('check_in') }}"
                                   class="w-full rounded-[0.5rem] border border-slate-300 bg-white px-3 py-2 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]">
                        </div>

                        <div>
                            <label for="manual-check-out" class="mb-1.5 block text-[0.8rem] font-bold text-[#06112e]">
                                Time Out
                            </label>
                            <input type="time" id="manual-check-out" name="check_out"
                                   value="{{ old('check_out') }}"
                                   class="w-full rounded-[0.5rem] border border-slate-300 bg-white px-3 py-2 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]">
                        </div>
                    </div>

                    {{-- Status --}}
                    <div>
                        <label for="manual-status" class="mb-1.5 block text-[0.8rem] font-bold text-[#06112e]">
                            Status
                            <span class="ml-1 text-[0.75rem] font-normal text-slate-400">(auto-detected from Time In if blank)</span>
                        </label>
                        <select id="manual-status" name="status"
                                class="w-full rounded-[0.5rem] border border-slate-300 bg-white px-3 py-2 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]">
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
                                  class="w-full resize-none rounded-[0.5rem] border border-slate-300 bg-white px-3 py-2 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]">{{ old('notes') }}</textarea>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="mt-8 flex justify-end gap-3">
                    <button type="button"
                            onclick="document.getElementById('manualEntryModal').classList.replace('flex', 'hidden')"
                            class="rounded-[0.5rem] border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-[#06112e] shadow-sm transition hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit"
                            class="rounded-[0.5rem] bg-[#1a56db] px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-[#1e40af]">
                        Save Entry
                    </button>
                </div>
            </form>
        </div>
    </div>

</x-app-layout>
