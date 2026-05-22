<x-app-layout>
    <x-slot:title>Contribution Tables</x-slot:title>
    <x-slot:header>Contribution Tables</x-slot:header>

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">Contribution Tables</h1>
            <p class="mt-1 text-sm text-slate-500">Bracket-based contribution tables used by payroll for government premium calculations.</p>
        </div>

        <a href="{{ route('salary.government-premiums') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Government Premiums</a>
    </div>

    @if ($governmentPremiums->isEmpty())
        <div class="card p-12 text-center">
            <p class="text-sm text-slate-500">No government premiums configured yet. <a href="{{ route('salary.government-premiums') }}" class="text-indigo-600 hover:text-indigo-800">Add premiums first</a> before creating contribution tables.</p>
        </div>
    @else
        <div class="card overflow-hidden shadow-sm">
            <div class="border-b border-slate-200 bg-slate-50 px-4 pt-3">
                <nav class="flex gap-8 overflow-x-auto" aria-label="Contribution table tabs">
                    @foreach ($governmentPremiums as $premium)
                        <button
                            type="button"
                            class="contribution-tab whitespace-nowrap border-b-2 px-1 pb-3 pt-2 text-sm font-semibold transition {{ $loop->first ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}"
                            data-target="contribution-panel-{{ $premium->id }}">
                            {{ str_replace(' Premium', '', $premium->name) }}
                            @if ($premium->brackets->isEmpty())
                                <span class="ml-1 inline-block h-1.5 w-1.5 rounded-full bg-amber-400" title="No table rows"></span>
                            @endif
                        </button>
                    @endforeach
                </nav>
            </div>

            @foreach ($governmentPremiums as $premium)
                <form method="POST" action="{{ route('salary.contribution-tables.save', $premium) }}" id="contribution-panel-{{ $premium->id }}" class="contribution-panel {{ $loop->first ? '' : 'hidden' }}">
                    @csrf
                    <div class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50 px-6 py-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">{{ $premium->name }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ $premium->description ?: 'Configure bracket rows for this premium.' }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="badge {{ $premium->brackets->count() > 0 ? 'badge-blue' : 'badge-amber' }}">{{ $premium->brackets->count() }} rows</span>
                            <button type="button" class="add-bracket-row-btn inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-600 transition hover:border-indigo-300 hover:bg-indigo-100" data-form-id="contribution-panel-{{ $premium->id }}">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add Row
                            </button>
                            <span class="text-xs font-semibold {{ $premium->is_active ? 'text-emerald-600' : 'text-slate-500' }}">
                                {{ $premium->is_active ? 'Active' : 'Inactive' }}
                            </span>
                            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                                Save Table
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-white text-left text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">Compensation Range</th>
                                    <th class="px-4 py-3">Type</th>
                                    <th class="px-4 py-3">Employee Share</th>
                                    <th class="px-4 py-3">Employer Share</th>
                                    <th class="px-4 py-3">Employer Extra</th>
                                    <th class="px-4 py-3 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($premium->brackets as $bracket)
                                    <tr>
                                        <td class="px-4 py-3 text-slate-700">
                                            <input type="hidden" name="brackets[{{ $loop->index }}][id]" value="{{ $bracket->id }}">
                                            <input type="text" name="brackets[{{ $loop->index }}][label]" value="{{ old('brackets.' . $loop->index . '.label', $bracket->label) }}" class="w-48 rounded border border-slate-200 px-2 py-1 text-sm">
                                            <div class="mt-2 grid w-48 grid-cols-2 gap-2">
                                                <input type="number" name="brackets[{{ $loop->index }}][min_compensation]" value="{{ old('brackets.' . $loop->index . '.min_compensation', number_format($bracket->min_compensation, 2, '.', '')) }}" step="0.01" min="0" class="rounded border border-slate-200 px-2 py-1 text-xs" title="Minimum compensation">
                                                <input type="number" name="brackets[{{ $loop->index }}][max_compensation]" value="{{ old('brackets.' . $loop->index . '.max_compensation', $bracket->max_compensation === null ? '' : number_format($bracket->max_compensation, 2, '.', '')) }}" step="0.01" min="0" class="rounded border border-slate-200 px-2 py-1 text-xs" placeholder="No max" title="Maximum compensation">
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-slate-600">
                                            <select name="brackets[{{ $loop->index }}][calculation_type]" class="w-32 rounded border border-slate-200 px-2 py-1 text-sm">
                                                <option value="Fixed" @selected(old('brackets.' . $loop->index . '.calculation_type', $bracket->calculation_type) === 'Fixed')>Fixed</option>
                                                <option value="Percentage" @selected(old('brackets.' . $loop->index . '.calculation_type', $bracket->calculation_type) === 'Percentage')>Percentage</option>
                                            </select>
                                        </td>
                                        <td class="px-4 py-3 text-slate-600">
                                            <input type="number" name="brackets[{{ $loop->index }}][employee_value]" value="{{ old('brackets.' . $loop->index . '.employee_value', number_format($bracket->employee_value, 2, '.', '')) }}" step="0.01" min="0" class="w-28 rounded border border-slate-200 px-2 py-1 text-sm">
                                        </td>
                                        <td class="px-4 py-3 text-slate-600">
                                            <input type="number" name="brackets[{{ $loop->index }}][employer_value]" value="{{ old('brackets.' . $loop->index . '.employer_value', number_format($bracket->employer_value, 2, '.', '')) }}" step="0.01" min="0" class="w-28 rounded border border-slate-200 px-2 py-1 text-sm">
                                        </td>
                                        <td class="px-4 py-3 text-slate-600">
                                            <input type="number" name="brackets[{{ $loop->index }}][employer_extra_value]" value="{{ old('brackets.' . $loop->index . '.employer_extra_value', number_format($bracket->employer_extra_value, 2, '.', '')) }}" step="0.01" min="0" class="w-28 rounded border border-slate-200 px-2 py-1 text-sm">
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <button type="button" class="remove-bracket-row-btn text-rose-600 hover:text-rose-900" title="Remove row">
                                                <svg class="pointer-events-none mx-auto h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row">
                                        <td colspan="6" class="px-4 py-6 text-center text-slate-500">No table rows configured yet. Click <strong>Add Row</strong> to create bracket entries.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </form>
            @endforeach
        </div>
    @endif

    @if(auth()->check() && !auth()->user()->hasPermission('payroll.edit'))
        <style>
            button[type="submit"],
            .add-bracket-row-btn,
            .remove-bracket-row-btn {
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

    <script>
        (function () {
            const inputCls = 'rounded border border-slate-200 px-2 py-1 text-sm';
            const inputXsCls = 'rounded border border-slate-200 px-2 py-1 text-xs';

            // Tab switching
            const tabs = document.querySelectorAll('.contribution-tab');
            const panels = document.querySelectorAll('.contribution-panel');

            tabs.forEach((tab) => {
                tab.addEventListener('click', () => {
                    const target = tab.dataset.target;

                    tabs.forEach((item) => {
                        item.classList.remove('border-indigo-600', 'text-indigo-600');
                        item.classList.add('border-transparent', 'text-slate-500');
                    });

                    tab.classList.add('border-indigo-600', 'text-indigo-600');
                    tab.classList.remove('border-transparent', 'text-slate-500');

                    panels.forEach((panel) => {
                        panel.classList.toggle('hidden', panel.id !== target);
                    });
                });
            });

            // Re-index all bracket rows in a given form's tbody
            function reindexRows(tbody) {
                const rows = tbody.querySelectorAll('tr:not(.empty-row)');
                rows.forEach((row, idx) => {
                    row.querySelectorAll('[name]').forEach((input) => {
                        input.name = input.name.replace(/brackets\[\d+\]/, `brackets[${idx}]`);
                    });
                });
            }

            // Add Row buttons
            document.querySelectorAll('.add-bracket-row-btn').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const formId = btn.dataset.formId;
                    const form = document.getElementById(formId);
                    if (!form) return;

                    const tbody = form.querySelector('tbody');
                    if (!tbody) return;

                    // Remove empty-state row
                    const emptyRow = tbody.querySelector('.empty-row');
                    if (emptyRow) emptyRow.remove();

                    const idx = tbody.querySelectorAll('tr').length;
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="px-4 py-3 text-slate-700">
                            <input type="text" name="brackets[${idx}][label]" class="w-48 ${inputCls}" placeholder="Label (optional)">
                            <div class="mt-2 grid w-48 grid-cols-2 gap-2">
                                <input type="number" name="brackets[${idx}][min_compensation]" value="0" step="0.01" min="0" class="${inputXsCls}" title="Minimum compensation">
                                <input type="number" name="brackets[${idx}][max_compensation]" step="0.01" min="0" class="${inputXsCls}" placeholder="No max" title="Maximum compensation">
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            <select name="brackets[${idx}][calculation_type]" class="w-32 ${inputCls}">
                                <option value="Fixed">Fixed</option>
                                <option value="Percentage">Percentage</option>
                            </select>
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            <input type="number" name="brackets[${idx}][employee_value]" value="0.00" step="0.01" min="0" class="w-28 ${inputCls}">
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            <input type="number" name="brackets[${idx}][employer_value]" value="0.00" step="0.01" min="0" class="w-28 ${inputCls}">
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            <input type="number" name="brackets[${idx}][employer_extra_value]" value="0.00" step="0.01" min="0" class="w-28 ${inputCls}">
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button type="button" class="remove-bracket-row-btn text-rose-600 hover:text-rose-900" title="Remove row">
                                <svg class="pointer-events-none mx-auto h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                    tr.querySelector('input')?.focus();
                });
            });

            // Delete Row buttons (event delegation)
            document.querySelectorAll('.contribution-panel tbody').forEach((tbody) => {
                tbody.addEventListener('click', (event) => {
                    const btn = event.target.closest('.remove-bracket-row-btn');
                    if (!btn) return;
                    btn.closest('tr')?.remove();
                    reindexRows(tbody);
                });
            });
        })();
    </script>
</x-app-layout>
