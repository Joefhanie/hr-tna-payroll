<x-app-layout>
    <x-slot:title>Payroll</x-slot:title>
    <x-slot:header>Payroll</x-slot:header>

    <div class="flex items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Payroll</h1>
            <p class="text-sm text-slate-500">Payroll runs, payslips, and statutory contributions.</p>
        </div>
        <button onclick="alert('Run Payroll feature coming soon')" class="btn-primary inline-flex items-center gap-2">
            <span>▶ Run Payroll</span>
        </button>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="card p-5">
            <p class="text-sm text-slate-500">YTD Gross</p>
            <p class="mt-1 text-2xl font-semibold">₱{{ number_format($ytdGross, 0) }}</p>
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
                            $grossTotal = $payRun->payslips()->sum('gross_pay');
                            $netTotal = $payRun->payslips()->sum('net_pay');
                            $employeeCount = $payRun->payslips()->count();
                            $statusColor = match($payRun->status) {
                                'Paid', 'Completed' => 'badge-green',
                                'Processing' => 'badge-blue',
                                'Draft' => 'badge-gray',
                                'Cancelled' => 'badge-red',
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
                                    {{ ucfirst($payRun->status) }}
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
