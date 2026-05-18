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

        @if (session('status'))
            <div class="mt-4 rounded-md bg-green-50 p-4 border border-green-200">
                <div class="flex">
                    <div class="shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">
                            {{ session('status') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <form action="{{ route('payroll.plotting-payment.employee.save', ['employee' => $employee->id]) }}"
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
                                Work location</th>
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
                                        name="entries[{{ $day['date_string'] }}]" placeholder="0.00"
                                        value="{{ $day['amount'] > 0 ? number_format($day['amount'], 2, '.', '') : '' }}"
                                        oninput="this.value = this.value.replace(/[^\d,.']/g, '').slice(0, 10)"
                                        class="w-40 rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 outline-none ring-blue-200 focus:ring font-mono text-right">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex items-center justify-end gap-3">
                <a href="{{ route('payroll.plotting-payment') }}"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    Back
                </a>
                <button type="submit"
                    class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                    Save
                </button>
            </div>
        </form>
    </div>
</x-app-layout>