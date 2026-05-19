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
                            $grossTotal = $payRun->payslips()->sum('gross_pay');
                            $netTotal = $payRun->payslips()->sum('net_pay');
                            $employeeCount = $payRun->payslips()->count();
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
                                    <form method="POST" action="{{ route('payroll.destroy', $payRun) }}" class="inline" onsubmit="return confirm('Are you sure? This will soft delete the payroll run.');">
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
    </div>
</x-app-layout>
