<x-app-layout>
    <x-slot:title>Salary Management</x-slot:title>
    <x-slot:header>Salary Management</x-slot:header>

    <div class="flex items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Salary Records</h1>
            <p class="text-sm text-slate-500">Manage employee salaries, pay frequencies, and salary history.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <!-- Salary Summary Cards -->
    <div class="grid gap-4 sm:grid-cols-3 mb-6">
        <div class="card p-5">
            <p class="text-sm text-slate-500">Total Employees</p>
            <p class="mt-1 text-2xl font-semibold">{{ $employees->count() }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-slate-500">With Active Salary</p>
            <p class="mt-1 text-2xl font-semibold">{{ $employees->filter(fn($e) => $e->salaryRecords()->whereNull('end_date')->exists())->count() }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-slate-500">Salary Records Total</p>
            <p class="mt-1 text-2xl font-semibold">{{ $employees->sum(fn($e) => $e->salaryRecords->count()) }}</p>
        </div>
    </div>

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
                            $activeSalary = $employee->salaryRecords()->whereNull('end_date')->first();
                            $payFrequencyLabels = [1 => 'Hourly', 2 => 'Daily', 3 => 'Weekly', 4 => 'Bi-weekly', 5 => 'Monthly', 6 => 'Annual'];
                        @endphp
                        <tr>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-semibold">
                                        {{ collect(explode(' ', $employee->full_name))->map(fn($name) => $name[0] ?? '')->join('') }}
                                    </div>
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
        @else
            <div class="px-6 py-12 text-center text-sm text-slate-500">No employees found.</div>
        @endif
    </div>
</x-app-layout>
