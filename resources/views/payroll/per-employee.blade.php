<x-app-layout>
    <x-slot:title>Plotting for {{ $employeeName }}</x-slot:title>
    <x-slot:header>Plotting of Payments</x-slot:header>

    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Name: {{ $employeeName }}</h1>
                <p class="mt-1 text-sm text-slate-600">Per-employee plotting details. Edit amounts as needed.</p>
            </div>
        </div>

        <div class="mt-6 overflow-hidden rounded-lg border border-slate-200">
            <table class="min-w-full text-sm">
                <thead>
                    <tr>
                        <th class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date</th>
                        <th class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Work location</th>
                        <th class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Supervisor</th>
                        <th class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($weekData as $index => $day)
                        <tr class="bg-white">
                            <td class="border-b border-slate-200 px-4 py-3 text-sm text-slate-700">{{ $day['date'] }}</td>
                            <td class="border-b border-slate-200 px-4 py-3 text-sm text-slate-700">{{ $day['workplace'] }}</td>
                            <td class="border-b border-slate-200 px-4 py-3 text-sm text-slate-700">&mdash;</td>
                            <td class="border-b border-slate-200 px-4 py-3 text-sm text-slate-700">
                                <input
                                    type="text"
                                    inputmode="text"
                                    maxlength="10"
                                    name="entries[{{ $index }}]"
                                    placeholder="0"
                                    oninput="this.value = this.value.replace(/[^\d,.']/g, '').slice(0, 10)"
                                    class="w-40 rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 outline-none ring-blue-200 focus:ring"
                                >
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex items-center justify-end gap-3">
            <a href="{{ route('payroll.plotting-payment') }}" class="text-sm text-slate-600 hover:underline">Back</a>
            <button type="button" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">Save</button>
        </div>
    </div>
</x-app-layout>
