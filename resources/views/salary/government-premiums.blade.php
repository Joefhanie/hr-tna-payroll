<x-app-layout>
    <x-slot:title>Goverment Premiums</x-slot:title>
    <x-slot:header>Goverment Premiums</x-slot:header>

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">Goverment Premiums</h1>
            <p class="mt-1 text-sm text-slate-500">Configure premium deductions and employer shares applied during payroll generation.</p>
        </div>

        <a href="{{ route('salary.contribution-tables') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Contribution Tables</a>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Active Premiums</p>
            <p class="mt-2 text-3xl font-semibold text-slate-900">{{ $governmentPremiums->where('is_active', true)->count() }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Employee Monthly Impact</p>
            <p class="mt-2 text-3xl font-semibold text-slate-900">PHP {{ number_format($employeeFixedTotal, 2) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Employer Monthly Impact</p>
            <p class="mt-2 text-3xl font-semibold text-slate-900">PHP {{ number_format($employerFixedTotal, 2) }}</p>
        </div>
    </div>

    <div class="card p-6 shadow-sm">
        <div class="mb-5 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Premium Rules</h2>
                <p class="mt-1 text-sm text-slate-500">Fixed values are treated as peso amounts. Percentage values are calculated from the selected basis.</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="badge badge-green">{{ $governmentPremiums->count() }} premiums</span>
                <button type="button" id="add-premium-btn" class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-600 transition hover:border-indigo-300 hover:bg-indigo-100">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add
                </button>
            </div>
        </div>

        <form method="POST" action="{{ route('salary.government-premiums.save') }}" id="government-premiums-form" class="space-y-4">
            @csrf
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Basis</th>
                            <th class="px-4 py-3">Employee</th>
                            <th class="px-4 py-3">Employer</th>
                            <th class="px-4 py-3">Taxable</th>
                            <th class="px-4 py-3">Active</th>
                            <th class="px-4 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($governmentPremiums as $premium)
                            <tr>
                                <td class="px-4 py-3">
                                    <input type="hidden" name="premiums[{{ $loop->index }}][id]" value="{{ $premium->id }}">
                                    <input type="text" name="premiums[{{ $loop->index }}][name]" value="{{ $premium->name }}" class="w-36 rounded border border-slate-200 px-2 py-1 text-sm" required>
                                    <input type="text" name="premiums[{{ $loop->index }}][description]" value="{{ $premium->description }}" class="mt-2 w-36 rounded border border-slate-200 px-2 py-1 text-xs" placeholder="Notes">
                                </td>
                                <td class="px-4 py-3">
                                    <select name="premiums[{{ $loop->index }}][calculation_type]" class="w-32 rounded border border-slate-200 px-2 py-1 text-sm">
                                        <option value="Fixed" @selected($premium->calculation_type === 'Fixed')>Fixed</option>
                                        <option value="Percentage" @selected($premium->calculation_type === 'Percentage')>Percentage</option>
                                    </select>
                                </td>
                                <td class="px-4 py-3">
                                    <select name="premiums[{{ $loop->index }}][basis]" class="w-32 rounded border border-slate-200 px-2 py-1 text-sm">
                                        <option value="Gross Pay" @selected($premium->basis === 'Gross Pay')>Gross Pay</option>
                                        <option value="Taxable Pay" @selected($premium->basis === 'Taxable Pay')>Taxable Pay</option>
                                    </select>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" name="premiums[{{ $loop->index }}][employee_value]" value="{{ number_format($premium->employee_value, 4, '.', '') }}" step="0.0001" min="0" class="w-28 rounded border border-slate-200 px-2 py-1 text-sm" required>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" name="premiums[{{ $loop->index }}][employer_value]" value="{{ number_format($premium->employer_value, 4, '.', '') }}" step="0.0001" min="0" class="w-28 rounded border border-slate-200 px-2 py-1 text-sm" required>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="checkbox" name="premiums[{{ $loop->index }}][is_taxable]" @checked($premium->is_taxable) class="rounded">
                                </td>
                                <td class="px-4 py-3">
                                    <input type="checkbox" name="premiums[{{ $loop->index }}][is_active]" @checked($premium->is_active) class="rounded">
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button type="button" class="remove-premium-row-btn text-rose-600 hover:text-rose-900" title="Remove">
                                        <svg class="pointer-events-none mx-auto h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-6 text-center text-slate-500">No government premiums configured yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                    Save Government Premiums
                </button>
            </div>
        </form>
    </div>

    <script>
        (function () {
            const inputCls = 'rounded border border-slate-200 px-2 py-1 text-sm';
            const addBtn = document.getElementById('add-premium-btn');
            const form = document.getElementById('government-premiums-form');
            const tbody = form?.querySelector('tbody');

            addBtn?.addEventListener('click', () => {
                if (!tbody) return;

                const empty = tbody.querySelector('tr td[colspan]');
                if (empty) empty.closest('tr').remove();

                const i = tbody.querySelectorAll('tr').length;
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="px-4 py-3">
                        <input type="text" name="premiums[${i}][name]" class="w-36 ${inputCls}" placeholder="Premium name" required>
                        <input type="text" name="premiums[${i}][description]" class="mt-2 w-36 rounded border border-slate-200 px-2 py-1 text-xs" placeholder="Notes">
                    </td>
                    <td class="px-4 py-3">
                        <select name="premiums[${i}][calculation_type]" class="w-32 ${inputCls}">
                            <option value="Fixed">Fixed</option>
                            <option value="Percentage">Percentage</option>
                        </select>
                    </td>
                    <td class="px-4 py-3">
                        <select name="premiums[${i}][basis]" class="w-32 ${inputCls}">
                            <option value="Gross Pay">Gross Pay</option>
                            <option value="Taxable Pay">Taxable Pay</option>
                        </select>
                    </td>
                    <td class="px-4 py-3"><input type="number" name="premiums[${i}][employee_value]" value="0.0000" step="0.0001" min="0" class="w-28 ${inputCls}" required></td>
                    <td class="px-4 py-3"><input type="number" name="premiums[${i}][employer_value]" value="0.0000" step="0.0001" min="0" class="w-28 ${inputCls}" required></td>
                    <td class="px-4 py-3"><input type="checkbox" name="premiums[${i}][is_taxable]" class="rounded"></td>
                    <td class="px-4 py-3"><input type="checkbox" name="premiums[${i}][is_active]" checked class="rounded"></td>
                    <td class="px-4 py-3 text-center">
                        <button type="button" class="remove-premium-row-btn text-rose-600 hover:text-rose-900" title="Remove">
                            <svg class="pointer-events-none mx-auto h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
                tr.querySelector('input')?.focus();
            });

            tbody?.addEventListener('click', (event) => {
                const btn = event.target.closest('.remove-premium-row-btn');
                if (btn) btn.closest('tr')?.remove();
            });
        })();
    </script>

    @if(auth()->check() && !auth()->user()->hasPermission('payroll.edit'))
        <style>
            button[type="submit"],
            #add-premium-btn,
            .remove-premium-row-btn {
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
