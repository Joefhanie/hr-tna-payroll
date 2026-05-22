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
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-1.5">
                Apply
            </button>
            <a href="{{ route('salary.index') }}" class="px-4 py-2 rounded-lg border border-slate-200 text-sm text-slate-600 hover:bg-slate-50 transition flex items-center gap-1.5">
                Clear
            </a>
            <a href="{{ route('salary.export') }}" id="btnExport" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-lg border border-slate-200 text-sm font-medium transition flex items-center gap-1.5 ml-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export CSV
            </a>
        </div>
    </form>

    <!-- Employees Table -->
    <div class="card overflow-hidden">
        @if ($employees->count() > 0)
            <table class="w-full text-sm">
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
                        <tr>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if ($employee->profile_picture)
                                        <div class="h-8 w-8 overflow-hidden rounded-full bg-slate-100">
                                            <img src="{{ asset('storage/' . $employee->profile_picture) }}" alt="{{ $employee->full_name }}" class="h-8 w-8 object-cover">
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
            @if ($employees->hasPages())
                <div class="border-t border-slate-100 px-6 py-4">
                    {{ $employees->links() }}
                </div>
            @endif
        @else
            <div class="px-6 py-12 text-center text-sm text-slate-500">No employees found.</div>
        @endif
    </div>

    <script>
        // Update export button URL dynamically based on form inputs
        function updateExportUrl() {
            const q = document.getElementById('filterSearch')?.value || '';
            const departmentId = document.getElementById('filterDepartment')?.value || '';
            const payFrequency = document.getElementById('filterPayFrequency')?.value || '';

            let url = "{{ route('salary.export') }}";
            const params = [];
            if (q) params.push(`q=${encodeURIComponent(q)}`);
            if (departmentId) params.push(`department_id=${encodeURIComponent(departmentId)}`);
            if (payFrequency) params.push(`pay_frequency=${encodeURIComponent(payFrequency)}`);
            if (params.length > 0) {
                url += `?${params.join('&')}`;
            }
            const exportBtn = document.getElementById('btnExport');
            if (exportBtn) exportBtn.href = url;
        }

        document.getElementById('filterSearch')?.addEventListener('input', updateExportUrl);
        document.getElementById('filterDepartment')?.addEventListener('change', updateExportUrl);
        document.getElementById('filterPayFrequency')?.addEventListener('change', updateExportUrl);

        // Run once on load
        updateExportUrl();
    </script>
</x-app-layout>
