<x-app-layout>
    <x-slot:title>Run Payroll</x-slot:title>
    <x-slot:header>Run Payroll</x-slot:header>

    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold">New Pay Run</h1>
            <p class="text-sm text-slate-500">Configure dates and select employees to generate draft payslips.</p>
        </div>
        <a href="{{ route('payroll.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">Cancel</a>
    </div>

    <form action="{{ route('payroll.store') }}" method="POST">
        @csrf
        <div class="grid gap-6 md:grid-cols-3">
            <div class="md:col-span-1 space-y-6">
                <!-- Pay Period -->
                <div class="card p-6">
                    <h2 class="text-lg font-semibold text-slate-900 mb-4">Pay Period</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Period Start</label>
                            <input type="date" name="period_start" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" value="{{ old('period_start', now()->startOfMonth()->format('Y-m-d')) }}" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Period End</label>
                            <input type="date" name="period_end" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" value="{{ old('period_end', now()->endOfMonth()->format('Y-m-d')) }}" required>
                        </div>

                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 space-y-2">
                            <label class="flex items-start gap-3 text-sm font-medium text-slate-700">
                                <input type="checkbox" name="deduct_government_contributions" value="1" id="deductGovContribToggle" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" {{ old('deduct_government_contributions') ? 'checked' : '' }}>
                                <span>
                                    Deduct government contributions
                                    <span class="block text-xs font-normal text-slate-500">Enable this for the pay run that should carry monthly government deductions.</span>
                                </span>
                            </label>
                            <p id="deductGovContribHelp" class="text-xs text-slate-500"></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="md:col-span-2">
                <div class="card overflow-hidden">
                    <div class="border-b border-slate-200 px-6 py-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Select Employees</h2>
                            <p class="mt-1 text-sm text-slate-600">Choose who should be included in this pay run.</p>
                        </div>
                        <button type="button" id="toggleSelectAllBtn" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Select All</button>
                    </div>

                    <div class="overflow-x-auto max-h-[500px] overflow-y-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500 sticky top-0">
                                <tr>
                                    <th class="px-6 py-3 w-12"></th>
                                    <th class="px-6 py-3">Employee</th>
                                    <th class="px-6 py-3">Department</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($employees as $employee)
                                    <tr class="employee-row hover:bg-slate-50 cursor-pointer" data-employee-id="{{ $employee->id }}" onclick="const cb = document.getElementById('emp_{{ $employee->id }}'); if (!cb.disabled) cb.click();">
                                        <td class="px-6 py-4">
                                            <input type="checkbox" id="emp_{{ $employee->id }}" name="employee_ids[]" value="{{ $employee->id }}" class="employee-checkbox h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" checked onclick="event.stopPropagation()">
                                        </td>
                                        <td class="px-6 py-4 font-medium text-slate-900">
                                            {{ $employee->full_name }}
                                        </td>
                                        <td class="px-6 py-4 text-slate-600">
                                            {{ $employee->department->name ?? 'N/A' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-6 py-12 text-center text-slate-500">
                                            No active employees found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-slate-200 px-6 py-4 bg-slate-50 flex justify-end">
                        <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg hover:bg-indigo-700 transition font-medium">
                            Generate Draft Run
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <x-slot:scripts>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const btn = document.getElementById('toggleSelectAllBtn');
                const checkboxes = document.querySelectorAll('.employee-checkbox');
                const startInput = document.querySelector('input[name="period_start"]');
                const endInput = document.querySelector('input[name="period_end"]');
                const activePayRuns = @json($activePayRuns ?? []);
                const deductGovToggle = document.getElementById('deductGovContribToggle');
                const deductGovHelp = document.getElementById('deductGovContribHelp');

                function monthKey(value) {
                    return value ? String(value).slice(0, 7) : '';
                }

                function monthAlreadyHasGovernmentContributions(startVal, endVal) {
                    const selectedMonth = monthKey(endVal || startVal);
                    if (!selectedMonth) {
                        return false;
                    }

                    return activePayRuns.some(run => {
                        const runMonth = monthKey(run.period_end);
                        const hasGovContribs = (run.deduct_government_contributions || Number(run.government_contribution_count) > 0);
                        return runMonth === selectedMonth && hasGovContribs;
                    });
                }

                function updateGovernmentContributionToggle() {
                    if (!deductGovToggle) {
                        return;
                    }

                    const startVal = startInput?.value;
                    const endVal = endInput?.value;
                    const locked = monthAlreadyHasGovernmentContributions(startVal, endVal);

                    deductGovToggle.disabled = locked;
                    if (locked) {
                        deductGovToggle.checked = false;
                        if (deductGovHelp) {
                            deductGovHelp.textContent = 'Government contributions are already applied in a pay run for this month.';
                        }
                    } else if (deductGovHelp) {
                        deductGovHelp.textContent = 'Leave off for the first cutoff; enable only for the pay run that should deduct contributions.';
                    }
                }

                function updateButtonLabel() {
                    const enabledCheckboxes = Array.from(checkboxes).filter(cb => !cb.disabled);
                    if (enabledCheckboxes.length === 0) {
                        btn.textContent = 'Select All';
                        return;
                    }

                    const checkedCount = enabledCheckboxes.filter(cb => cb.checked).length;
                    if (checkedCount === enabledCheckboxes.length) {
                        btn.textContent = 'Unselect All';
                    } else {
                        btn.textContent = 'Select All';
                    }
                }

                function updateEmployeeAvailability() {
                    const startVal = startInput?.value;
                    const endVal = endInput?.value;
                    if (!startVal || !endVal) return;

                    const busyEmployees = {};

                    activePayRuns.forEach(run => {
                        // Check overlap
                        if (run.period_start <= endVal && run.period_end >= startVal) {
                            run.employee_ids.forEach(empId => {
                                busyEmployees[empId] = {
                                    name: run.name,
                                    status: run.status_label
                                };
                            });
                        }
                    });

                    document.querySelectorAll('.employee-row').forEach(row => {
                        const empId = row.getAttribute('data-employee-id');
                        const cb = document.getElementById('emp_' + empId);
                        if (!cb) return;

                        if (busyEmployees[empId]) {
                            const info = busyEmployees[empId];
                            cb.checked = false;
                            cb.disabled = true;
                            row.classList.add('opacity-50', 'cursor-not-allowed');
                            row.classList.remove('hover:bg-slate-50');
                            row.setAttribute('title', `Already in Pay Run: ${info.name} (${info.status})`);
                        } else {
                            if (cb.disabled) {
                                cb.disabled = false;
                                cb.checked = true;
                            }
                            row.classList.remove('opacity-50', 'cursor-not-allowed');
                            row.classList.add('hover:bg-slate-50');
                            row.removeAttribute('title');
                        }
                    });

                    updateButtonLabel();
                }

                btn?.addEventListener('click', function () {
                    const enabledCheckboxes = Array.from(checkboxes).filter(cb => !cb.disabled);
                    if (enabledCheckboxes.length === 0) return;

                    const allChecked = enabledCheckboxes.every(cb => cb.checked);

                    enabledCheckboxes.forEach(cb => {
                        cb.checked = !allChecked;
                    });

                    updateButtonLabel();
                });

                checkboxes.forEach(cb => {
                    cb.addEventListener('change', updateButtonLabel);
                });

                startInput?.addEventListener('change', updateEmployeeAvailability);
                endInput?.addEventListener('change', updateEmployeeAvailability);
                startInput?.addEventListener('change', updateGovernmentContributionToggle);
                endInput?.addEventListener('change', updateGovernmentContributionToggle);

                // Initialize state
                updateEmployeeAvailability();
                updateGovernmentContributionToggle();
            });
        </script>
    </x-slot:scripts>
</x-app-layout>
