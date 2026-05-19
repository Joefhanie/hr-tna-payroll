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
        $payFrequencies = [1 => 'Hourly', 2 => 'Daily', 3 => 'Weekly', 4 => 'Bi-weekly', 5 => 'Monthly', 6 => 'Annual'];
    @endphp

    @if ($activeSalary)
        <div class="mb-6 grid gap-4 sm:grid-cols-5">
            <div class="card p-5 border-l-4 border-l-green-500">
                <p class="text-sm text-slate-500">Current Salary</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">₱{{ number_format($activeSalary->amount, 2) }}</p>
            </div>
            <div class="card p-5">
                <p class="text-sm text-slate-500">Pay Frequency</p>
                <p class="mt-2 text-lg font-semibold text-slate-900">{{ $payFrequencies[$activeSalary->pay_frequency] ?? $activeSalary->pay_frequency }}</p>
            </div>
            <div class="card p-5">
                <p class="text-sm text-slate-500">Daily Divisor</p>
                <p class="mt-2 text-lg font-semibold text-slate-900">{{ number_format($activeSalary->daily_divisor, 4) }}</p>
                <p class="text-xs text-slate-400">{{ $activeSalary->daily_divisor == 21.8 ? '5-day/week' : ($activeSalary->daily_divisor == 26.1667 ? '6-day/week' : 'Custom') }}</p>
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
                            <th class="px-6 py-3">Daily Divisor</th>
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
                                <td class="px-6 py-4 text-slate-600">{{ $payFrequencies[$salary->pay_frequency] ?? $salary->pay_frequency }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ number_format($salary->daily_divisor, 4) }}</td>
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

    <!-- Tax & Deductions Assignments -->
    <div class="card overflow-hidden mt-6">
        <div class="border-b border-slate-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-slate-900">Tax & Deductions Assignments</h2>
            <p class="mt-1 text-sm text-slate-600">Select which tax brackets, government contributions, and deduction rules apply to this employee.</p>
        </div>

        <form method="POST" action="{{ route('salary.save-assignments', $employee) }}" class="p-6">
            @csrf

            @php
                $assignedTaxIds = $employee->taxBrackets->pluck('id')->toArray();
                $assignedContribIds = $employee->governmentContributionRates->pluck('id')->toArray();
                $assignedDeductionIds = $employee->deductionRules->pluck('id')->toArray();
            @endphp

            <!-- Tax Brackets -->
            <div class="mb-8">
                <div class="flex items-center gap-3 mb-4">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-800">Tax Brackets</h3>
                        <p class="text-xs text-slate-500">Income thresholds and tax rates</p>
                    </div>
                </div>

                @if ($allTaxBrackets->count() > 0)
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($allTaxBrackets as $bracket)
                            <label class="flex items-center gap-3 rounded-lg border border-slate-200 p-3 cursor-pointer transition hover:border-indigo-300 hover:bg-indigo-50/30 {{ in_array($bracket->id, $assignedTaxIds) ? 'border-indigo-300 bg-indigo-50/50' : '' }}">
                                <input type="checkbox" name="tax_brackets[]" value="{{ $bracket->id }}" {{ in_array($bracket->id, $assignedTaxIds) ? 'checked' : '' }} class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-800 truncate">{{ $bracket->label ?: 'Bracket #' . $bracket->id }}</p>
                                    <p class="text-xs text-slate-500">Threshold: ₱{{ number_format($bracket->threshold, 2) }} · Rate: {{ $bracket->rate * 100 }}%</p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-slate-500 italic">No active tax brackets configured. <a href="{{ route('salary.settings') }}" class="text-indigo-600 hover:text-indigo-800">Configure settings</a></p>
                @endif
            </div>

            <!-- Government Contributions -->
            <div class="mb-8">
                <div class="flex items-center gap-3 mb-4">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-green-100 text-green-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-800">Government Contributions</h3>
                        <p class="text-xs text-slate-500">SSS, PhilHealth, Pag-IBIG, etc.</p>
                    </div>
                </div>

                @if ($allContributions->count() > 0)
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($allContributions as $contrib)
                            <label class="flex items-center gap-3 rounded-lg border border-slate-200 p-3 cursor-pointer transition hover:border-indigo-300 hover:bg-indigo-50/30 {{ in_array($contrib->id, $assignedContribIds) ? 'border-indigo-300 bg-indigo-50/50' : '' }}">
                                <input type="checkbox" name="contributions[]" value="{{ $contrib->id }}" {{ in_array($contrib->id, $assignedContribIds) ? 'checked' : '' }} class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-800 truncate">{{ $contrib->name }}</p>
                                    <p class="text-xs text-slate-500">Employee: {{ $contrib->employee_rate * 100 }}% · Employer: {{ $contrib->employer_rate * 100 }}%</p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-slate-500 italic">No active contributions configured. <a href="{{ route('salary.settings') }}" class="text-indigo-600 hover:text-indigo-800">Configure settings</a></p>
                @endif
            </div>

            <!-- Deduction Rules -->
            <div class="mb-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-800">Deduction Rules</h3>
                        <p class="text-xs text-slate-500">Operational deductions applied to this employee</p>
                    </div>
                </div>

                @if ($allDeductionRules->count() > 0)
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($allDeductionRules as $rule)
                            <label class="flex items-center gap-3 rounded-lg border border-slate-200 p-3 cursor-pointer transition hover:border-indigo-300 hover:bg-indigo-50/30 {{ in_array($rule->id, $assignedDeductionIds) ? 'border-indigo-300 bg-indigo-50/50' : '' }}">
                                <input type="checkbox" name="deduction_rules[]" value="{{ $rule->id }}" {{ in_array($rule->id, $assignedDeductionIds) ? 'checked' : '' }} class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-800 truncate">{{ $rule->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $rule->type }} · {{ $rule->type === 'Fixed' ? '₱' . number_format($rule->amount, 2) : ($rule->amount ?? 0) . '%' }}</p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-slate-500 italic">No active deduction rules configured. <a href="{{ route('salary.settings') }}" class="text-indigo-600 hover:text-indigo-800">Configure settings</a></p>
                @endif
            </div>

            <!-- Save Button -->
            <div class="flex justify-end border-t border-slate-100 pt-5">
                <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                    Save Assignments
                </button>
            </div>
        </form>
    </div>

</x-app-layout>
