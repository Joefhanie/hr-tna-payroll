<x-app-layout>
    <x-slot:title>Plotting of Payments</x-slot:title>
    <x-slot:header>Plotting of Payments</x-slot:header>

    @php
        $weekData = [
            ['date' => 'May 18', 'workplace' => 'Manila Zoo'],
            ['date' => 'May 19', 'workplace' => 'Manila Zoo'],
            ['date' => 'May 20', 'workplace' => 'SM'],
            ['date' => 'May 21', 'workplace' => 'Manila Zoo'],
            ['date' => 'May 22', 'workplace' => 'Manila Zoo'],
        ];
        $employees = [
            ['name' => 'Kenneth', 'group' => 1],
            ['name' => 'Alfren', 'group' => 1],
            ['name' => 'Jano', 'group' => 2],
            ['name' => 'KJ', 'group' => 2],
            ['name' => 'Jim', 'group' => 3],
            ['name' => 'Andrei', 'group' => 3],
        ];
    @endphp

    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Plotting of Payments</h1>
                <p class="mt-1 text-sm text-slate-600">Spreadsheet-style payroll plotting with names on the left and weekly date headers across the top.</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                Payroll
            </span>
        </div>

        <div class="mt-6 overflow-hidden rounded-lg border border-slate-200 relative">
            <table class="min-w-full border-separate border-spacing-0 text-sm">
                <thead>
                    <tr>
                        <th class="sticky left-0 z-10 w-44 border-b border-r border-slate-200 bg-slate-50 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 text-center">
                            Name
                        </th>
                        @foreach ($weekData as $day)
                            <th class="border-b border-r border-slate-200 bg-slate-50 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500 last:border-r-0">
                                {{ $day['date'] }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($employees as $employeeIndex => $employee)
                        <tr class="bg-white">
                            <td class="sticky left-0 z-10 border-b border-r border-slate-200 bg-white px-4 py-3 font-medium text-slate-900">
                                <a href="{{ route('payroll.plotting-payment.employee', ['employee' => urlencode($employee['name'])]) }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                                    {{ $employee['name'] }}
                                </a>
                            </td>
                            @foreach ($weekData as $dayIndex => $day)
                                <td class="border-b border-r border-slate-200 px-2 py-2 text-center last:border-r-0 relative">
                                    <div class="relative group">
                                        <input
                                            type="text"
                                            inputmode="text"
                                            maxlength="10"
                                            name="entries[{{ $employee['name'] }}][{{ $dayIndex }}]"
                                            placeholder="0"
                                            data-workplace="{{ $day['workplace'] }}"
                                            data-employee="{{ $employee['name'] }}"
                                            oninput="this.value = this.value.replace(/[^\d,.']/g, '').slice(0, 10)"
                                            class="w-full min-w-0 rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 outline-none ring-blue-200 focus:ring overflow-hidden"
                                        >
                                        <!-- Comment indicator triangle -->
                                        <div class="absolute top-1 right-1 w-0 h-0 border-l-3 border-b-3 border-l-transparent border-b-gray-400 pointer-events-none"></div>

                                        <!-- Comment bubble (Excel/Sheets style) -->
                                        <div class="workplace-comment hidden group-focus-within:block absolute left-full top-0 ml-2 bg-gray-50 border border-gray-300 rounded px-3 py-2 shadow-lg z-20 w-48 text-left">
                                            <div class="space-y-2">
                                                <div class="text-xs text-slate-700">
                                                    <span class="font-semibold">Work location:</span>
                                                    <a href="{{ route('payroll.work-location-details', ['date' => $day['date'], 'workplace' => urlencode($day['workplace'])]) }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                                                        {{ $day['workplace'] }}
                                                    </a>
                                                </div>
                                                <div class="text-xs text-slate-700">
                                                    <span class="font-semibold">Supervisor:</span> <span class="text-slate-600">TBD</span>
                                                </div>
                                                <div class="text-xs text-slate-700">
                                                    <span class="font-semibold">Note:</span> <span class="text-slate-600 italic">No description</span>
                                                </div>
                                            </div>
                                            <!-- Comment pointer -->
                                            <div class="absolute right-full top-1 -mr-1 w-0 h-0 border-r-4 border-t-4 border-t-transparent border-r-gray-50"></div>
                                        </div>
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex items-center justify-between gap-3">
            <p class="text-xs text-slate-500">Enter each employee's amount per date. The grid stays fixed like a spreadsheet.</p>
            <button type="button" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                Save Plotting
            </button>
        </div>
    </div>
</x-app-layout>
