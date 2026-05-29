<x-app-layout>
    <x-slot:title>Plotting for {{ $employee->first_name }} {{ $employee->last_name }}</x-slot:title>
    <x-slot:header>Plotting of Payments</x-slot:header>

    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Name: {{ $employee->first_name }}
                    {{ $employee->last_name }}</h1>
                <p class="mt-1 text-sm text-slate-600">Per-employee plotting details. Edit amounts as needed.</p>
            </div>
            <span
                class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                ID: {{ $employee->employee_code }}
            </span>
        </div>



        @php
            $hasEditableFields = false;
            foreach ($weekData as $day) {
                if (empty($day['posted'])) {
                    $hasEditableFields = true;
                    break;
                }
            }
        @endphp

        <form action="{{ route('payroll.plotting-payment.employee.save', ['employee' => $employee->id, 'from_date' => request('from_date'), 'to_date' => request('to_date')]) }}"
            method="POST">
            @csrf
            <div class="mt-6 overflow-hidden rounded-lg border border-slate-200">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500">
                            <th
                                class="border-b border-slate-200 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">
                                Date</th>
                            <th
                                class="border-b border-slate-200 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">
                                Location</th>
                            <th
                                class="border-b border-slate-200 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">
                                Supervisor</th>
                            <th
                                class="border-b border-slate-200 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">
                                Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach($weekData as $day)
                            <tr class="bg-white hover:bg-slate-50/50">
                                <td class="px-4 py-3 text-sm text-slate-700 font-medium">{{ $day['date'] }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    <span
                                        class="inline-flex items-center rounded bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-800">
                                        {{ $day['workplace'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $day['supervisor'] }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    <input type="text" inputmode="text" maxlength="10"
                                        name="entries[{{ $day['date_string'] }}][{{ $day['workplace'] }}]" placeholder="0.00"
                                        value="{{ $day['amount'] > 0 ? number_format($day['amount'], 2, '.', '') : '' }}"
                                        oninput="this.value = this.value.replace(/[^\d,.']/g, '').slice(0, 10)"
                                        @if(!empty($day['posted'])) readonly @endif
                                        class="w-40 rounded-md border border-slate-300 px-3 py-2 text-sm outline-none ring-blue-200 focus:ring font-mono text-right @if(!empty($day['posted'])) bg-slate-100 text-slate-500 cursor-not-allowed @else text-slate-900 @endif">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex items-center justify-end gap-3">
                <a href="{{ route('payroll.plotting-payment', ['from_date' => request('from_date'), 'to_date' => request('to_date')]) }}"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    Back
                </a>
                <button type="submit"
                    data-confirm="Are you sure you want to submit and save the plotting payments for this employee?"
                    data-confirm-title="Submit Employee Plotting"
                    data-confirm-type="info"
                    data-confirm-text="Submit"
                    @if(!$hasEditableFields) disabled @endif
                    class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    Submit
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
