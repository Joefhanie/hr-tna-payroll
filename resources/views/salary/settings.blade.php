<x-app-layout>
    <x-slot:title>Salary Settings</x-slot:title>
    <x-slot:header>Salary Settings</x-slot:header>

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">Payroll Rules Configuration</h1>
            <p class="mt-1 text-sm text-slate-500">Edit tax brackets, attendance rates, and deduction rules.</p>
        </div>

        <a href="{{ route('salary.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Salary Records</a>
    </div>

    <!-- Attendance Rate Defaults -->
    <div class="card p-6 shadow-sm mb-6">
        <div class="flex items-center justify-between gap-3 mb-5">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Attendance Rate Defaults</h2>
                <p class="mt-1 text-sm text-slate-500">Company-wide defaults for attendance multipliers. Leave empty to use hardcoded defaults.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('salary.save-payroll-settings') }}" id="payroll-settings-form" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-medium text-slate-600">Overtime Multiplier (OT)</label>
                    <input type="number" name="attendance_overtime_multiplier" step="0.01" min="0" value="{{ old('attendance_overtime_multiplier', isset($global->attendance_overtime_multiplier) ? number_format($global->attendance_overtime_multiplier, 2, '.', '') : '1.25') }}" class="w-full rounded border border-slate-200 px-2 py-2 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600">Night Differential Multiplier</label>
                    <input type="number" name="attendance_night_differential_multiplier" step="0.01" min="0" value="{{ old('attendance_night_differential_multiplier', isset($global->attendance_night_differential_multiplier) ? number_format($global->attendance_night_differential_multiplier, 2, '.', '') : '0.10') }}" class="w-full rounded border border-slate-200 px-2 py-2 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600">Late Deduction Multiplier</label>
                    <input type="number" name="attendance_late_deduction_multiplier" step="0.01" min="0" value="{{ old('attendance_late_deduction_multiplier', isset($global->attendance_late_deduction_multiplier) ? number_format($global->attendance_late_deduction_multiplier, 2, '.', '') : '1.00') }}" class="w-full rounded border border-slate-200 px-2 py-2 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600">Undertime Deduction Multiplier</label>
                    <input type="number" name="attendance_undertime_deduction_multiplier" step="0.01" min="0" value="{{ old('attendance_undertime_deduction_multiplier', isset($global->attendance_undertime_deduction_multiplier) ? number_format($global->attendance_undertime_deduction_multiplier, 2, '.', '') : '1.00') }}" class="w-full rounded border border-slate-200 px-2 py-2 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600">Absence Deduction Multiplier</label>
                    <input type="number" name="attendance_absence_deduction_multiplier" step="0.01" min="0" value="{{ old('attendance_absence_deduction_multiplier', isset($global->attendance_absence_deduction_multiplier) ? number_format($global->attendance_absence_deduction_multiplier, 2, '.', '') : '1.00') }}" class="w-full rounded border border-slate-200 px-2 py-2 text-sm">
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-4">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                    Save Payroll Defaults
                </button>
            </div>
        </form>
    </div>

    <!-- Late Deductions Configuration -->
    <div class="card p-6 shadow-sm mb-6">
        <div class="flex items-center justify-between gap-3 mb-5">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Late Deductions Configuration</h2>
                <p class="mt-1 text-sm text-slate-500">Configure tiered late deduction thresholds and penalties (in hours).</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="badge badge-blue">{{ $lateDeductionRules->count() }} rules</span>
                <button type="button" id="add-late-rule-btn" class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-600 transition hover:bg-indigo-100 hover:border-indigo-300">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add
                </button>
            </div>
        </div>

        <form method="POST" action="{{ route('salary.save-late-deduction-rules') }}" id="late-rules-form" class="space-y-4">
            @csrf
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Rule Name</th>
                            <th class="px-4 py-3">Minutes Limit</th>
                            <th class="px-4 py-3">Deduction (Hours)</th>
                            <th class="px-4 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($lateDeductionRules as $rule)
                            <tr>
                                <td class="px-4 py-3">
                                    <input type="hidden" name="rules[{{ $loop->index }}][id]" value="{{ $rule->id }}">
                                    <input type="text" name="rules[{{ $loop->index }}][name]" value="{{ $rule->name }}" class="w-48 rounded border border-slate-200 px-3 py-1.5 text-sm" required>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" name="rules[{{ $loop->index }}][max_minutes]" value="{{ $rule->max_minutes }}" class="w-32 rounded border border-slate-200 px-3 py-1.5 text-sm" required>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" name="rules[{{ $loop->index }}][deduction_hours]" value="{{ number_format($rule->deduction_hours, 2, '.', '') }}" step="0.01" min="0" class="w-32 rounded border border-slate-200 px-3 py-1.5 text-sm" required>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button type="button" class="text-rose-600 hover:text-rose-900 remove-row-btn" title="Remove">
                                        <svg class="h-4 w-4 pointer-events-none mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-slate-500">No late deduction rules configured yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end gap-3 mt-4">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                    Save Late Deductions Config
                </button>
            </div>
        </form>
    </div>





    <!-- Deduction Rules Section -->
    <div class="card p-6 shadow-sm">
        <div class="flex items-center justify-between gap-3 mb-5">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Deduction Rules</h2>
                <p class="mt-1 text-sm text-slate-500">Define operational deduction rules.</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="badge badge-amber">{{ $deductionRules->count() }} rules</span>
                <button type="button" id="add-deduction-btn" class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-600 transition hover:bg-indigo-100 hover:border-indigo-300">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add
                </button>
            </div>
        </div>

        <form method="POST" action="{{ route('salary.save-deduction-rules') }}" id="deductions-form" class="space-y-4">
            @csrf
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Amount / Rate</th>
                            <th class="px-4 py-3">Scope</th>
                            <th class="px-4 py-3">Active</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($deductionRules as $rule)
                            <tr>
                                <td class="px-4 py-3">
                                    <input type="hidden" name="rules[{{ $loop->index }}][id]" value="{{ $rule->id }}">
                                    <input type="text" name="rules[{{ $loop->index }}][name]" value="{{ $rule->name }}" class="w-32 rounded border border-slate-200 px-2 py-1 text-sm" required>
                                </td>
                                <td class="px-4 py-3">
                                    <select name="rules[{{ $loop->index }}][type]" class="w-24 rounded border border-slate-200 px-2 py-1 text-sm">
                                        <option value="Fixed" @selected($rule->type === 'Fixed')>Fixed</option>
                                        <option value="Percentage" @selected($rule->type === 'Percentage')>Percentage</option>
                                        <option value="Prorated" @selected($rule->type === 'Prorated')>Prorated</option>
                                    </select>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" name="rules[{{ $loop->index }}][amount]" value="{{ $rule->amount }}" step="0.01" placeholder="Amount" class="w-24 rounded border border-slate-200 px-2 py-1 text-sm">
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" name="rules[{{ $loop->index }}][scope]" value="{{ $rule->scope }}" class="w-32 rounded border border-slate-200 px-2 py-1 text-sm" placeholder="e.g., Attendance linked">
                                </td>
                                <td class="px-4 py-3">
                                    <input type="checkbox" name="rules[{{ $loop->index }}][is_active]" @checked($rule->is_active) class="rounded">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-slate-500">No deduction rules configured yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end gap-3 mt-4">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                    Save Deduction Rules
                </button>
            </div>
        </form>
    </div>
<script>
(function () {
    const inputCls = 'rounded border border-slate-200 px-2 py-1 text-sm';

    function addRow(btnId, formId, buildCells) {
        document.getElementById(btnId)?.addEventListener('click', () => {
            const tbody = document.querySelector('#' + formId + ' tbody');
            if (!tbody) return;
            const empty = tbody.querySelector('tr td[colspan]');
            if (empty) empty.closest('tr').remove();
            const idx = tbody.querySelectorAll('tr').length;
            const tr = document.createElement('tr');
            tr.innerHTML = buildCells(idx);
            tbody.appendChild(tr);
            tr.querySelector('input')?.focus();
        });
    }

    addRow('add-late-rule-btn', 'late-rules-form', i => `
        <td class="px-4 py-3">
            <input type="text" name="rules[${i}][name]" class="w-48 ${inputCls}" placeholder="Rule Name" required>
        </td>
        <td class="px-4 py-3">
            <input type="number" name="rules[${i}][max_minutes]" value="0" class="w-32 ${inputCls}" placeholder="Minutes Limit" required>
        </td>
        <td class="px-4 py-3">
            <input type="number" name="rules[${i}][deduction_hours]" value="0.00" step="0.01" min="0" class="w-32 ${inputCls}" placeholder="Hours" required>
        </td>
        <td class="px-4 py-3 text-center">
            <button type="button" class="text-rose-600 hover:text-rose-900 remove-row-btn" title="Remove">
                <svg class="h-4 w-4 pointer-events-none mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
        </td>
    `);

    document.querySelector('#late-rules-form tbody')?.addEventListener('click', (e) => {
        const btn = e.target.closest('.remove-row-btn');
        if (btn) {
            const tr = btn.closest('tr');
            tr.remove();
        }
    });





    addRow('add-deduction-btn', 'deductions-form', i => `
        <td class="px-4 py-3"><input type="text" name="rules[${i}][name]" class="w-32 ${inputCls}" placeholder="Name" required></td>
        <td class="px-4 py-3">
            <select name="rules[${i}][type]" class="w-24 ${inputCls}">
                <option value="Fixed">Fixed</option>
                <option value="Percentage">Percentage</option>
                <option value="Prorated">Prorated</option>
            </select>
        </td>
        <td class="px-4 py-3"><input type="number" name="rules[${i}][amount]" value="0" step="0.01" class="w-24 ${inputCls}" placeholder="Amount"></td>
        <td class="px-4 py-3"><input type="text" name="rules[${i}][scope]" class="w-32 ${inputCls}" placeholder="e.g., Attendance linked"></td>
        <td class="px-4 py-3"><input type="checkbox" name="rules[${i}][is_active]" checked class="rounded"></td>
    `);
})();
</script>

@if(auth()->check() && !auth()->user()->hasPermission('payroll.edit'))
<style>
    button[type="submit"], 
    #add-late-rule-btn, 
    #add-deduction-btn, 
    .remove-row-btn {
        display: none !important;
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('input, select, textarea').forEach(el => {
            el.disabled = true;
        });
    });
</script>
@endif
</x-app-layout>
