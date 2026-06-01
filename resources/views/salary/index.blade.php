<x-app-layout>
    <x-slot:title>Salary Management</x-slot:title>
    <x-slot:header>Salary Management</x-slot:header>

    <div class="flex items-end justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-semibold">Salary Records</h1>
            <p class="text-sm text-slate-500">Manage employee salaries, pay frequencies, and salary history.</p>
        </div>
    </div>

    <!-- Salary Summary Cards -->
    <div class="grid gap-4 sm:grid-cols-3 mb-6">
        <div class="card p-5">
            <p class="text-sm text-slate-500">Total Employees</p>
            <p class="mt-1 text-2xl font-semibold">{{ $totalEmployees }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-slate-500">With Active Salary</p>
            <p class="mt-1 text-2xl font-semibold">{{ $withActiveSalary }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-slate-500">Salary Records Total</p>
            <p class="mt-1 text-2xl font-semibold">{{ $totalSalaryRecords }}</p>
        </div>
    </div>

    {{-- Live Filters --}}
    <form id="filterForm" method="GET" action="{{ route('salary.index') }}" class="card p-4 mb-6">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-medium text-slate-600 mb-1">Search</label>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" name="q" id="filterSearch" placeholder="Search by name, code, email…" value="{{ $filters['q'] ?? '' }}"
                        class="w-full rounded-lg border border-slate-200 pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div class="min-w-[160px]">
                <label class="block text-xs font-medium text-slate-600 mb-1">Department</label>
                <select name="department_id" id="filterDepartment" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ ($filters['department_id'] ?? '') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[140px]">
                <label class="block text-xs font-medium text-slate-600 mb-1">Pay Frequency</label>
                <select name="pay_frequency" id="filterPayFrequency" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                    <option value="">All Frequencies</option>
                    <option value="1" {{ ($filters['pay_frequency'] ?? '') == '1' ? 'selected' : '' }}>Hourly</option>
                    <option value="2" {{ ($filters['pay_frequency'] ?? '') == '2' ? 'selected' : '' }}>Daily</option>
                    <option value="3" {{ ($filters['pay_frequency'] ?? '') == '3' ? 'selected' : '' }}>Weekly</option>
                    <option value="4" {{ ($filters['pay_frequency'] ?? '') == '4' ? 'selected' : '' }}>Bi-weekly</option>
                    <option value="5" {{ ($filters['pay_frequency'] ?? '') == '5' ? 'selected' : '' }}>Monthly</option>
                    <option value="6" {{ ($filters['pay_frequency'] ?? '') == '6' ? 'selected' : '' }}>Annual</option>
                </select>
            </div>
            <button type="button" id="salary-clear" class="px-4 py-2 rounded-lg border border-slate-200 text-sm text-slate-600 hover:bg-slate-50 transition flex items-center gap-1.5">
                Clear
            </button>
            @if(auth()->user()->role === 4)
            <a href="{{ route('salary.export') }}" id="btnExport" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-lg border border-slate-200 text-sm font-medium transition flex items-center gap-1.5 ml-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export CSV
            </a>
            @endif
        </div>
    </form>

    <!-- Employees Table -->
    <div class="card overflow-hidden">
        @if ($employees->count() > 0)
            <table id="salaryRecordsTable" class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Employee</th>
                        <th class="px-4 py-3">Position</th>
                        <th class="px-4 py-3">Current Salary</th>
                        <th class="px-4 py-3">Frequency</th>
                        <th class="px-4 py-3">Effective From</th>
                        <th class="px-4 py-3">Records</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($employees as $employee)
                        @php
                            $activeSalary = $employee->salaryRecords->where('end_date', null)->first();
                            $payFrequencyLabels = [1 => 'Hourly', 2 => 'Daily', 3 => 'Weekly', 4 => 'Bi-weekly', 5 => 'Monthly', 6 => 'Annual'];
                        @endphp
                        <tr class="salary-row" data-department-id="{{ $employee->department_id }}" data-pay-frequency="{{ $activeSalary?->pay_frequency ?? '' }}">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if ($employee->profile_picture)
                                        <div class="h-8 w-8 overflow-hidden rounded-full bg-slate-100">
                                            <img src="{{ route('media.file', ['path' => ltrim($employee->profile_picture, '/')]) }}" alt="{{ $employee->full_name }}" class="h-8 w-8 object-cover">
                                        </div>
                                    @else
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700">
                                            {{ collect(explode(' ', $employee->full_name))->map(fn($name) => $name[0] ?? '')->join('') }}
                                        </div>
                                    @endif
                                    <div>
                                        <p class="font-medium text-slate-900">{{ $employee->full_name }}</p>
                                        <p class="text-xs text-slate-500">{{ $employee->employee_code }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $employee->position->title ?? 'N/A' }}</td>
                            <td class="px-4 py-3 font-medium text-slate-900">
                                @if ($activeSalary)
                                    ₱{{ number_format($activeSalary->amount, 2) }}
                                @else
                                    <span class="text-amber-600">No active salary</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                @if ($activeSalary)
                                    {{ $payFrequencyLabels[$activeSalary->pay_frequency] ?? $activeSalary->pay_frequency }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                @if ($activeSalary)
                                    {{ $activeSalary->effective_date->format('Y-m-d') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge badge-blue">
                                    {{ $employee->salaryRecords->count() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('salary.show', $employee) }}" class="text-slate-600 hover:text-slate-900 transition">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                    <a href="{{ route('salary.create', $employee) }}" class="text-indigo-600 hover:text-indigo-900 transition" title="Add new salary record">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <x-table-pagination target="salaryRecordsTable" itemsPerPage="10" />
        @else
            <div class="px-6 py-12 text-center text-sm text-slate-500">No employees found.</div>
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('filterSearch');
            const deptSelect = document.getElementById('filterDepartment');
            const freqSelect = document.getElementById('filterPayFrequency');
            const clearButton = document.getElementById('salary-clear');
            const exportButton = document.getElementById('btnExport');

            function getSalaryRows() {
                return Array.from(document.querySelectorAll('#salaryRecordsTable tbody tr.salary-row'));
            }

            function updateExportUrl() {
                if (!exportButton) return;

                const params = new URLSearchParams();
                const q = searchInput?.value?.trim() || '';
                const dept = deptSelect?.value || '';
                const freq = freqSelect?.value || '';

                if (q) params.set('q', q);
                if (dept) params.set('department_id', dept);
                if (freq) params.set('pay_frequency', freq);

                exportButton.href = `{{ route('salary.export') }}${[...params].length ? '?' + params.toString() : ''}`;
            }

            function filterSalaries() {
                const qTerm = (searchInput?.value || '').trim().toLowerCase();
                const deptVal = deptSelect?.value || '';
                const freqVal = freqSelect?.value || '';

                getSalaryRows().forEach((row) => {
                    const searchable = row.textContent.toLowerCase();
                    const rowDeptId = row.getAttribute('data-department-id') || '';
                    const rowFreq = row.getAttribute('data-pay-frequency') || '';

                    const matchesSearch = !qTerm || searchable.includes(qTerm);
                    const matchesDept = !deptVal || rowDeptId === deptVal;
                    const matchesFreq = !freqVal || rowFreq === freqVal;

                    const isVisible = matchesSearch && matchesDept && matchesFreq;
                    row.setAttribute('data-filter-hidden', isVisible ? 'false' : 'true');
                });

                updateExportUrl();
            }

            searchInput?.addEventListener('input', filterSalaries);
            deptSelect?.addEventListener('change', filterSalaries);
            freqSelect?.addEventListener('change', filterSalaries);

            clearButton?.addEventListener('click', function() {
                if (searchInput) searchInput.value = '';
                if (deptSelect) deptSelect.value = '';
                if (freqSelect) freqSelect.value = '';
                filterSalaries();
            });

            searchInput?.addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                }
            });

            filterSalaries();
        });
    </script>
</x-app-layout>
