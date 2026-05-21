<x-app-layout>
    <x-slot:title>Contribution Tables</x-slot:title>
    <x-slot:header>Contribution Tables</x-slot:header>

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">Contribution Tables</h1>
            <p class="mt-1 text-sm text-slate-500">Official SSS and PhilHealth rows used by payroll for government premium calculations.</p>
        </div>

        <a href="{{ route('salary.government-premiums') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Goverment Premiums</a>
    </div>

    <div class="card overflow-hidden shadow-sm">
        <div class="border-b border-slate-200 bg-slate-50 px-4 pt-3">
            <nav class="flex gap-8 overflow-x-auto" aria-label="Contribution table tabs">
                @foreach ($governmentPremiums as $premium)
                    <button
                        type="button"
                        class="contribution-tab whitespace-nowrap border-b-2 px-1 pb-3 pt-2 text-sm font-semibold transition {{ $loop->first ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}"
                        data-target="contribution-panel-{{ $premium->id }}">
                        {{ str_replace(' Premium', '', $premium->name) }}
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
                        <p class="mt-1 text-sm text-slate-500">{{ $premium->description }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-semibold {{ $premium->is_active ? 'text-emerald-600' : 'text-slate-500' }}">
                            {{ $premium->is_active ? 'Active' : 'Inactive' }}
                        </span>
                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                            Save {{ $premium->name }}
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
                                        <input type="number" name="brackets[{{ $loop->index }}][employee_value]" value="{{ old('brackets.' . $loop->index . '.employee_value', number_format($bracket->employee_value, 4, '.', '')) }}" step="0.0001" min="0" class="w-28 rounded border border-slate-200 px-2 py-1 text-sm">
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">
                                        <input type="number" name="brackets[{{ $loop->index }}][employer_value]" value="{{ old('brackets.' . $loop->index . '.employer_value', number_format($bracket->employer_value, 4, '.', '')) }}" step="0.0001" min="0" class="w-28 rounded border border-slate-200 px-2 py-1 text-sm">
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">
                                        <input type="number" name="brackets[{{ $loop->index }}][employer_extra_value]" value="{{ old('brackets.' . $loop->index . '.employer_extra_value', number_format($bracket->employer_extra_value, 4, '.', '')) }}" step="0.0001" min="0" class="w-28 rounded border border-slate-200 px-2 py-1 text-sm">
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-slate-500">No table rows loaded for this premium.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>
        @endforeach
    </div>

    @if(auth()->check() && !auth()->user()->hasPermission('payroll.edit'))
        <style>
            button[type="submit"] {
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
        })();
    </script>
</x-app-layout>
