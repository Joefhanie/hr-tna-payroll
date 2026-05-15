<x-app-layout>
    <x-slot:title>Payroll</x-slot:title>
    <x-slot:header>Payroll</x-slot:header>

    <div class="mb-6 flex items-center justify-between">
        <p class="text-sm text-slate-600">Payroll runs, payslips, and statutory contributions.</p>
        <form action="{{ route('payroll.run') }}" method="POST">
            @csrf
            <button type="submit" class="bg-blue-600 text-white px-6 py-2.5 rounded-lg hover:bg-blue-700 transition flex items-center gap-2 font-medium">
                <i class="fas fa-play"></i>
                Run Payroll
            </button>
        </form>
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
                                <a href="#" class="font-medium text-indigo-600 transition hover:text-indigo-800">View</a>
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
