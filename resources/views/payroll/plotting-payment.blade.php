<x-app-layout>
    <x-slot:title>Plotting of Payments</x-slot:title>
    <x-slot:header>Plotting of Payments</x-slot:header>

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

        @if (session('status'))
            <div class="mt-4 rounded-md bg-green-50 p-4 border border-green-200">
                <div class="flex">
                    <div class="shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
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

        <form action="{{ route('payroll.plotting-payment.save') }}" method="POST" class="mt-6">
            @csrf
            <div class="overflow-hidden rounded-lg border border-slate-200 relative">
                <table class="min-w-full border-separate border-spacing-0 text-sm">
                    <thead>
                        <tr>
                            <th class="sticky left-0 z-10 w-48 border-b border-r border-slate-200 bg-slate-50 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Name
                            </th>
                            @foreach ($dates as $dateString => $dateLabel)
                                <th class="border-b border-r border-slate-200 bg-slate-50 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500 last:border-r-0">
                                    {{ $dateLabel }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($gridData as $row)
                            @php
                                $emp = $row['employee'];
                            @endphp
                            <tr class="bg-white hover:bg-slate-50/50">
                                <td class="sticky left-0 z-10 border-b border-r border-slate-200 bg-white px-4 py-3 font-medium text-slate-900">
                                    <a href="{{ route('payroll.plotting-payment.employee', ['employee' => $emp->id]) }}" class="text-blue-600 hover:text-blue-800 hover:underline flex flex-col">
                                        <span>{{ $emp->first_name }} {{ $emp->last_name }}</span>
                                        <span class="text-xxs font-normal text-slate-500 mt-0.5">ID: {{ $emp->employee_code }}</span>
                                    </a>
                                </td>
                                @foreach ($dates as $dateString => $dateLabel)
                                    @php
                                        $dayData = $row['days'][$dateString];
                                    @endphp
                                    <td class="border-b border-r border-slate-200 px-2 py-2 text-center last:border-r-0 relative">
                                        <div class="relative group">
                                            <input
                                                type="text"
                                                inputmode="text"
                                                maxlength="10"
                                                name="entries[{{ $emp->id }}][{{ $dateString }}]"
                                                placeholder="0.00"
                                                value="{{ $dayData['amount'] > 0 ? number_format($dayData['amount'], 2, '.', '') : '' }}"
                                                data-workplace="{{ $dayData['location'] }}"
                                                data-employee="{{ $emp->first_name }} {{ $emp->last_name }}"
                                                oninput="this.value = this.value.replace(/[^\d,.']/g, '').slice(0, 10)"
                                                class="w-full min-w-0 rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 outline-none ring-blue-200 focus:ring overflow-hidden text-right font-mono"
                                            >
                                            <!-- Comment indicator triangle -->
                                            <div class="absolute top-1 right-1 w-0 h-0 border-l-3 border-b-3 border-l-transparent border-b-gray-400 pointer-events-none"></div>

                                            <!-- Comment bubble (Excel/Sheets style) -->
                                            <div class="workplace-comment hidden group-focus-within:block absolute left-full top-0 ml-2 bg-gray-50 border border-gray-300 rounded px-3 py-2 shadow-lg z-20 w-48 text-left">
                                                <div class="space-y-1">
                                                    <div class="text-xs text-slate-700">
                                                        <span class="font-semibold text-slate-900">Work Assignment:</span>
                                                        <a href="{{ route('payroll.work-location-details', ['date' => $dateString, 'workplace' => urlencode($dayData['location'])]) }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                                                            {{ $dayData['location'] }}
                                                        </a>
                                                    </div>
                                                     <div class="text-xs text-slate-700">
                                                        <span class="font-semibold text-slate-900">Supervisor:</span>
                                                        <span class="text-slate-600">
                                                            {{ $dayData['supervisor_name'] }}
                                                        </span>
                                                    </div>
                                                    <div class="text-xs text-slate-700">
                                                        <span class="font-semibold text-slate-900">Note:</span>
                                                        <span class="text-slate-600 italic">No description</span>
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
                <p class="text-xs text-slate-500">Enter each employee's amount per date. Hover/focus cells to see supervisor-inherited locations.</p>
                <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 shadow-sm">
                    Save Plotting
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
