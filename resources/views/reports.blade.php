<x-app-layout>
    <x-slot:title>Reports</x-slot:title>
    <x-slot:header>Reports</x-slot:header>

    <div class="mb-6 flex items-center justify-between pb-6 border-b border-slate-200">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Reports</h1>
            <p class="mt-1 text-sm text-slate-600">Operational summaries and exportable reports across all modules.</p>
        </div>
    </div>

    <!-- Stats Cards Grid -->
    <div class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Card: Active Headcount -->
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Active Headcount</p>
                    <h3 class="mt-1 text-2xl font-bold text-slate-900">{{ $activeEmployeesCount }}</h3>
                </div>
                <div class="rounded-lg bg-blue-50 p-3 text-blue-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
            <p class="mt-2 text-xs text-slate-500">Currently active profiles</p>
        </div>

        <!-- Card: Today's Clock-ins -->
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Today's Attendance</p>
                    <h3 class="mt-1 text-2xl font-bold text-slate-900">{{ $todayAttendanceCount }}</h3>
                </div>
                <div class="rounded-lg bg-emerald-50 p-3 text-emerald-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <p class="mt-2 text-xs text-slate-500">Time clock entries today</p>
        </div>

        <!-- Card: Approved Leaves -->
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Approved Leaves</p>
                    <h3 class="mt-1 text-2xl font-bold text-slate-900">{{ $approvedLeavesCount }}</h3>
                </div>
                <div class="rounded-lg bg-amber-50 p-3 text-amber-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
            <p class="mt-2 text-xs text-slate-500">Approved requests this month</p>
        </div>

        <!-- Card: Completed Pay Runs -->
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Finalized Payrolls</p>
                    <h3 class="mt-1 text-2xl font-bold text-slate-900">{{ $completedPayRunsCount }}</h3>
                </div>
                <div class="rounded-lg bg-indigo-50 p-3 text-indigo-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
            <p class="mt-2 text-xs text-slate-500">Completed pay runs YTD</p>
        </div>
    </div>

    <!-- Optional Filters Panel -->
    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="text-base font-semibold text-slate-900 mb-3 flex items-center gap-2">
            <svg class="h-5 w-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
            </svg>
            Report Configurations
        </h3>
        <form id="report-filter-form" method="GET" action="" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Start Date (For Attendance & Leaves)</label>
                <input type="date" name="start_date" id="start_date" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-700 bg-white">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">End Date (For Attendance & Leaves)</label>
                <input type="date" name="end_date" id="end_date" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-700 bg-white">
            </div>
            <div class="flex items-end">
                <button type="button" onclick="clearDates()" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                    Reset Date Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Reports Table -->
    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-6 py-4">Report</th>
                    <th class="px-6 py-4">Category</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($reports as $report)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="font-semibold text-slate-900 text-base">{{ $report['name'] }}</span>
                                <span class="text-xs text-slate-500 mt-0.5">{{ $report['description'] }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-800">
                                {{ $report['category'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="badge {{ $report['status'] === 'Ready' ? 'badge-green' : 'badge-amber' }}">
                                {{ $report['status'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button type="button" onclick="downloadReport('{{ $report['id'] }}')" 
                                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                <span>Download CSV</span>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <script>
        function downloadReport(reportId) {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            
            let url = `/reports/download/${reportId}`;
            const params = [];
            
            if (startDate) {
                params.push(`start_date=${encodeURIComponent(startDate)}`);
            }
            if (endDate) {
                params.push(`end_date=${encodeURIComponent(endDate)}`);
            }
            
            if (params.length > 0) {
                url += `?${params.join('&')}`;
            }
            
            window.location.href = url;
        }

        function clearDates() {
            document.getElementById('start_date').value = '';
            document.getElementById('end_date').value = '';
        }
    </script>
</x-app-layout>
