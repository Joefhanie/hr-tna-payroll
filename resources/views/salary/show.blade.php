<x-app-layout>
    <x-slot:title>{{ $employee->full_name }} - Salary Details</x-slot:title>
    <x-slot:header>Salary Details</x-slot:header>

    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold">{{ $employee->full_name }}</h1>
            <p class="text-sm text-slate-500">Salary records and payment history for {{ $employee->employee_code }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('salary.create', $employee) }}" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition">+ Add Salary Record</a>
            <a href="{{ route('salary.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">Back</a>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <!-- Current Salary Card -->
    @php
        $activeSalary = $employee->salaryRecords()->whereNull('end_date')->first();
    @endphp

    @if ($activeSalary)
        <div class="mb-6 grid gap-4 sm:grid-cols-4">
            <div class="card p-5 border-l-4 border-l-green-500">
                <p class="text-sm text-slate-500">Current Salary</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">₱{{ number_format($activeSalary->amount, 2) }}</p>
            </div>
            <div class="card p-5">
                <p class="text-sm text-slate-500">Pay Frequency</p>
                <p class="mt-2 text-lg font-semibold text-slate-900">{{ $payFrequencies[$activeSalary->salary_type] ?? $activeSalary->salary_type }}</p>
            </div>
            <div class="card p-5">
                <p class="text-sm text-slate-500">Effective From</p>
                <p class="mt-2 text-lg font-semibold text-slate-900">{{ $activeSalary->effective_date->format('M d, Y') }}</p>
            </div>
            <div class="card p-5">
                <p class="text-sm text-slate-500">Reason</p>
                <p class="mt-2 text-lg font-semibold text-slate-900">{{ $activeSalary->reason ?? 'Initial' }}</p>
            </div>
        </div>
    @else
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700">
            <p class="font-semibold">No active salary record</p>
            <p class="mt-1">This employee does not have an active salary record. Create one to enable payroll processing.</p>
        </div>
    @endif

    <!-- Salary History -->
    <div class="card overflow-hidden">
        <div class="border-b border-slate-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-slate-900">Salary History</h2>
            <p class="mt-1 text-sm text-slate-600">All salary records for this employee</p>
        </div>

        @if ($employee->salaryRecords->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-6 py-3">Salary Amount</th>
                            <th class="px-6 py-3">Frequency</th>
                            <th class="px-6 py-3">Effective From</th>
                            <th class="px-6 py-3">End Date</th>
                            <th class="px-6 py-3">Reason</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($employee->salaryRecords->sortByDesc('effective_date') as $salary)
                            <tr>
                                <td class="px-6 py-4 font-medium text-slate-900">₱{{ number_format($salary->amount, 2) }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $payFrequencies[$salary->salary_type] ?? $salary->salary_type }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $salary->effective_date->format('M d, Y') }}</td>
                                <td class="px-6 py-4 text-slate-600">
                                    @if ($salary->end_date)
                                        {{ $salary->end_date->format('M d, Y') }}
                                    @else
                                        <span class="badge badge-green">Active</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-slate-600">{{ $salary->reason ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    @if (! $salary->end_date)
                                        <span class="badge badge-green">Active</span>
                                    @else
                                        <span class="badge badge-gray">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('salary.edit', $salary) }}" class="text-slate-600 hover:text-slate-900 transition">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </a>
                                        <form method="POST" action="{{ route('salary.destroy', $salary) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this salary record?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 transition">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-6 py-12 text-center text-sm text-slate-500">
                No salary records found. <a href="{{ route('salary.create', $employee) }}" class="text-indigo-600 hover:text-indigo-800">Create one</a>
            </div>
        @endif
    </div>
</x-app-layout>
