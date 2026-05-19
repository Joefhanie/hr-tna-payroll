<x-app-layout>
     <x-slot:title>Plotting of Payments</x-slot:title>
    <x-slot:header>Plotting of Payments</x-slot:header>

    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-start justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Date: {{ $dateFormatted }}</h1>
                <p class="mt-1 text-sm text-slate-600">All employees assigned on this date</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200">
            <table class="min-w-full border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="border-b border-slate-200 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Employees
                        </th>
                        <th class="border-b border-slate-200 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Work Assignment
                        </th>
                        <th class="border-b border-slate-200 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Supervisor
                        </th>
                        <th class="border-b border-slate-200 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Amount
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($employeeData as $employee)
                        <tr class="bg-white hover:bg-slate-50 transition">
                            <td class="border-b border-slate-200 px-4 py-3 font-medium text-slate-900">
                                {{ $employee['name'] }}
                            </td>
                            <td class="border-b border-slate-200 px-4 py-3 text-slate-700">
                                {{ $employee['workplace'] }}
                            </td>
                            <td class="border-b border-slate-200 px-4 py-3 text-slate-700">
                                {{ $employee['supervisor'] }}
                            </td>
                            <td class="border-b border-slate-200 px-4 py-3">
                                <input
                                    type="text"
                                    inputmode="decimal"
                                    placeholder="0"
                                    name="amounts[{{ $employee['name'] }}]"
                                    oninput="this.value = this.value.replace(/[^\d,.']/g, '').slice(0, 10)"
                                    class="w-24 rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 outline-none ring-blue-200 focus:ring"
                                >
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex items-center justify-between gap-3">
            <p class="text-xs text-slate-500">Enter the amount for each employee assigned on this date.</p>
            <div class="flex gap-3">
                <a href="{{ route('payroll.plotting-payment') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Back
                </a>
                <button type="button" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                    Save Details
                </button>
            </div>
        </div>
    </div>
</x-app-layout>
