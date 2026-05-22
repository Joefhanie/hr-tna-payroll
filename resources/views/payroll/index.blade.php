<x-app-layout>
    <x-slot:title>Payroll List</x-slot:title>
    <x-slot:header>Payroll Run</x-slot:header>

     <div class="mb-6 flex items-center justify-between pb-6 border-b border-slate-200">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Payroll</h1>
            <p class="mt-1 text-sm text-slate-600">Payroll runs, payslips, and statutory contributions.</p>
        </div>
       <a href="{{ route('payroll.create') }}" class="bg-blue-600 text-white px-6 py-2.5 rounded-lg hover:bg-blue-700 transition flex items-center gap-2 font-medium">
            <i class="fas fa-play"></i>
            Run Payroll
        </a>
    </div>

    @if(session('status'))
        <div class="mb-4 p-3 rounded bg-green-50 text-green-800">{{ session('status') }}</div>
    @endif

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- YTD Gross Card -->
        <div class="bg-white rounded-lg border border-slate-200 p-6 shadow-sm hover:shadow-md transition">
            <h3 class="text-sm font-medium text-slate-600 mb-2">YTD Gross</h3>
            <p class="text-3xl font-bold text-slate-900">
                ₱{{ number_format($ytdGross, 0) }}
            </p>
        </div>

        <div class="card p-5">
            <p class="text-sm text-slate-500">YTD Net</p>
            <p class="mt-1 text-2xl font-semibold">₱{{ number_format($ytdNet, 0) }}</p>
        </div>

        <div class="card p-5">
            <p class="text-sm text-slate-500">Statutory ({{ now()->format('M') }})</p>
            <p class="mt-1 text-2xl font-semibold">₱{{ number_format($statutoryAmount, 0) }}</p>
        </div>
    </div>

    {{-- Live Filters --}}
    <form id="filterForm" method="GET" action="{{ route('payroll.index') }}" class="card p-4 mb-6">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="min-w-[140px]">
                <label class="block text-xs font-medium text-slate-600 mb-1">Period Start From</label>
                <input type="date" name="start_date" id="filterStartDate" value="{{ $filters['start_date'] ?? '' }}"
                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
            </div>
            <div class="min-w-[140px]">
                <label class="block text-xs font-medium text-slate-600 mb-1">Period End To</label>
                <input type="date" name="end_date" id="filterEndDate" value="{{ $filters['end_date'] ?? '' }}"
                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
            </div>
            <div class="min-w-[140px]">
                <label class="block text-xs font-medium text-slate-600 mb-1">Status</label>
                <select name="status" id="filterStatus" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                    <option value="">All Statuses</option>
                    <option value="1" {{ ($filters['status'] ?? '') == '1' ? 'selected' : '' }}>Draft</option>
                    <option value="2" {{ ($filters['status'] ?? '') == '2' ? 'selected' : '' }}>Processing</option>
                    <option value="3" {{ ($filters['status'] ?? '') == '3' ? 'selected' : '' }}>Completed</option>
                    <option value="4" {{ ($filters['status'] ?? '') == '4' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-1.5">
                Apply
            </button>
            <a href="{{ route('payroll.index') }}" class="px-4 py-2 rounded-lg border border-slate-200 text-sm text-slate-600 hover:bg-slate-50 transition flex items-center gap-1.5">
                Clear
            </a>
            <a href="{{ route('payroll.export') }}" id="btnExport" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-lg border border-slate-200 text-sm font-medium transition flex items-center gap-1.5 ml-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export CSV
            </a>
        </div>
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Pay Period</th>
                        <th class="px-4 py-3">Employees</th>
                        <th class="px-4 py-3 text-right">Gross</th>
                        <th class="px-4 py-3 text-right">Net</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($payRuns as $payRun)
                        @php
                            // PayRun Status: 1=Draft, 2=Processing, 3=Completed, 4=Cancelled
                            // Status 13 = Deleted (soft delete equivalent)
                            if ($payRun->status == 13) continue;
                            $grossTotal = $payRun->payslips->sum('gross_pay');
                            $netTotal = $payRun->payslips->sum('net_pay');
                            $employeeCount = $payRun->payslips->count();
                            $statusLabels = [1 => 'Draft', 2 => 'Processing', 3 => 'Completed', 4 => 'Cancelled'];
                            $statusLabel = $statusLabels[$payRun->status] ?? 'Unknown';
                            $statusColor = match((int) $payRun->status) {
                                3 => 'badge-green',      // Completed
                                2 => 'badge-blue',       // Processing
                                1 => 'badge-gray',       // Draft
                                4 => 'badge-red',        // Cancelled
                                default => 'badge-gray'
                            };
                        @endphp
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">
                                {{ $payRun->period_start->format('M d') }} – {{ $payRun->period_end->format('M d, Y') }}
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $employeeCount }}
                            </td>
                            <td class="px-4 py-3 text-right text-slate-600">
                                ₱{{ number_format($grossTotal, 0) }}
                            </td>
                            <td class="px-4 py-3 text-right text-slate-600">
                                ₱{{ number_format($netTotal, 0) }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge {{ $statusColor }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('payroll.show', $payRun) }}" class="text-indigo-600 hover:text-indigo-800 transition" title="View">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                    @if ($payRun->status == 1)
                                        <a href="{{ route('payroll.edit', $payRun) }}" class="text-slate-600 hover:text-slate-900 transition" title="Edit">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </a>
                                    @endif
                                    <form method="POST" action="{{ route('payroll.destroy', $payRun) }}" class="inline" data-confirm="Are you sure? This will soft delete the payroll run.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 transition" title="Delete">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                No payroll runs yet. Click "Run Payroll" to create your first payroll run.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($payRuns->hasPages())
            <div class="border-t border-slate-100 px-6 py-4">
                {{ $payRuns->links() }}
            </div>
        @endif
    </div>

    <script>
        // Update export button URL dynamically based on form inputs
        function updateExportUrl() {
            const startDate = document.getElementById('filterStartDate')?.value || '';
            const endDate = document.getElementById('filterEndDate')?.value || '';
            const status = document.getElementById('filterStatus')?.value || '';

            let url = "{{ route('payroll.export') }}";
            const params = [];
            if (startDate) params.push(`start_date=${encodeURIComponent(startDate)}`);
            if (endDate) params.push(`end_date=${encodeURIComponent(endDate)}`);
            if (status) params.push(`status=${encodeURIComponent(status)}`);
            if (params.length > 0) {
                url += `?${params.join('&')}`;
            }
            const exportBtn = document.getElementById('btnExport');
            if (exportBtn) exportBtn.href = url;
        }

        document.getElementById('filterStartDate')?.addEventListener('change', updateExportUrl);
        document.getElementById('filterEndDate')?.addEventListener('change', updateExportUrl);
        document.getElementById('filterStatus')?.addEventListener('change', updateExportUrl);

        // Run once on load
        updateExportUrl();
    </script>
</x-app-layout>
