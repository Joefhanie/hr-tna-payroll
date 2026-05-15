<x-app-layout>
    <x-slot:title>Salary Settings</x-slot:title>
    <x-slot:header>Salary Settings</x-slot:header>

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Salaries / Tax & Deductions</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">Payroll Rules Configuration</h1>
            <p class="mt-1 text-sm text-slate-500">Edit tax brackets, government contributions, and deduction rules.</p>
        </div>

        <a href="{{ route('salary.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Salary Records</a>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <!-- Tax Brackets Section -->
    <div class="card p-6 shadow-sm mb-6">
        <div class="flex items-center justify-between gap-3 mb-5">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Tax Brackets</h2>
                <p class="mt-1 text-sm text-slate-500">Configure income thresholds and tax rates.</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="badge badge-blue">{{ $taxBrackets->count() }} brackets</span>
                <button type="button" id="add-tax-bracket-btn" class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-600 transition hover:bg-indigo-100 hover:border-indigo-300">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add
                </button>
            </div>
        </div>

        <form method="POST" action="{{ route('salary.save-tax-brackets') }}" id="tax-brackets-form" class="space-y-4">
            @csrf
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Threshold</th>
                            <th class="px-4 py-3">Rate (%)</th>
                            <th class="px-4 py-3">Label</th>
                            <th class="px-4 py-3">Active</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($taxBrackets as $bracket)
                            <tr>
                                <td class="px-4 py-3">
                                    <input type="hidden" name="brackets[{{ $loop->index }}][id]" value="{{ $bracket->id }}">
                                    <input type="number" name="brackets[{{ $loop->index }}][threshold]" value="{{ $bracket->threshold }}" step="0.01" class="w-24 rounded border border-slate-200 px-2 py-1 text-sm" required>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" name="brackets[{{ $loop->index }}][rate]" value="{{ $bracket->rate * 100 }}" step="0.01" min="0" max="100" class="w-24 rounded border border-slate-200 px-2 py-1 text-sm" required>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" name="brackets[{{ $loop->index }}][label]" value="{{ $bracket->label }}" class="w-32 rounded border border-slate-200 px-2 py-1 text-sm">
                                </td>
                                <td class="px-4 py-3">
                                    <input type="checkbox" name="brackets[{{ $loop->index }}][is_active]" @checked($bracket->is_active) class="rounded">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-slate-500">No tax brackets configured yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end gap-3 mt-4">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                    Save Tax Brackets
                </button>
            </div>
        </form>
    </div>

    <!-- Government Contributions Section -->
    <div class="card p-6 shadow-sm mb-6">
        <div class="flex items-center justify-between gap-3 mb-5">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Government Contributions</h2>
                <p class="mt-1 text-sm text-slate-500">Set employee and employer contribution rates.</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="badge badge-green">{{ $governmentContributions->count() }} contributions</span>
                <button type="button" id="add-contribution-btn" class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-600 transition hover:bg-indigo-100 hover:border-indigo-300">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add
                </button>
            </div>
        </div>

        <form method="POST" action="{{ route('salary.save-government-contributions') }}" id="contributions-form" class="space-y-4">
            @csrf
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Employee Rate (%)</th>
                            <th class="px-4 py-3">Employer Rate (%)</th>
                            <th class="px-4 py-3">Active</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($governmentContributions as $contrib)
                            <tr>
                                <td class="px-4 py-3">
                                    <input type="hidden" name="contributions[{{ $loop->index }}][id]" value="{{ $contrib->id }}">
                                    <input type="text" name="contributions[{{ $loop->index }}][name]" value="{{ $contrib->name }}" class="w-32 rounded border border-slate-200 px-2 py-1 text-sm" required>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" name="contributions[{{ $loop->index }}][employee_rate]" value="{{ $contrib->employee_rate * 100 }}" step="0.01" min="0" max="100" class="w-24 rounded border border-slate-200 px-2 py-1 text-sm" required>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" name="contributions[{{ $loop->index }}][employer_rate]" value="{{ $contrib->employer_rate * 100 }}" step="0.01" min="0" max="100" class="w-24 rounded border border-slate-200 px-2 py-1 text-sm" required>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="checkbox" name="contributions[{{ $loop->index }}][is_active]" @checked($contrib->is_active) class="rounded">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-slate-500">No government contributions configured yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end gap-3 mt-4">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                    Save Contributions
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

    addRow('add-tax-bracket-btn', 'tax-brackets-form', i => `
        <td class="px-4 py-3"><input type="number" name="brackets[${i}][threshold]" value="0" step="0.01" class="w-24 ${inputCls}" required></td>
        <td class="px-4 py-3"><input type="number" name="brackets[${i}][rate]" value="0" step="0.01" min="0" max="100" class="w-24 ${inputCls}" required></td>
        <td class="px-4 py-3"><input type="text" name="brackets[${i}][label]" class="w-32 ${inputCls}" placeholder="Label"></td>
        <td class="px-4 py-3"><input type="checkbox" name="brackets[${i}][is_active]" checked class="rounded"></td>
    `);

    addRow('add-contribution-btn', 'contributions-form', i => `
        <td class="px-4 py-3"><input type="text" name="contributions[${i}][name]" class="w-32 ${inputCls}" placeholder="Name" required></td>
        <td class="px-4 py-3"><input type="number" name="contributions[${i}][employee_rate]" value="0" step="0.01" min="0" max="100" class="w-24 ${inputCls}" required></td>
        <td class="px-4 py-3"><input type="number" name="contributions[${i}][employer_rate]" value="0" step="0.01" min="0" max="100" class="w-24 ${inputCls}" required></td>
        <td class="px-4 py-3"><input type="checkbox" name="contributions[${i}][is_active]" checked class="rounded"></td>
    `);

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

</x-app-layout>
