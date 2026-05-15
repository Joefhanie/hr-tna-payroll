<x-app-layout>
    <x-slot:title>Payroll Details - {{ $payRun->name }}</x-slot:title>
    <x-slot:header>Payroll Details</x-slot:header>

    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold">{{ $payRun->name }}</h1>
            <p class="text-sm text-slate-500">Pay period: {{ $payRun->period_start->format('M d, Y') }} – {{ $payRun->period_end->format('M d, Y') }}</p>
        </div>
        <a href="{{ route('payroll.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">Back</a>
    </div>

    @php
        $grossTotal = $payRun->payslips()->sum('gross_pay');
        $netTotal = $payRun->payslips()->sum('net_pay');
        $statusLabels = [1 => 'Draft', 2 => 'Processing', 3 => 'Completed', 4 => 'Cancelled'];
        $statusColor = match((int) $payRun->status) {
            3 => 'badge-green',
            2 => 'badge-blue',
            1 => 'badge-gray',
            4 => 'badge-red',
            default => 'badge-gray'
        };
    @endphp

    <div class="mb-6 grid gap-4 sm:grid-cols-4">
        <div class="card p-5">
            <p class="text-xs font-semibold uppercase text-slate-500">Employees</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $payRun->payslips()->count() }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-semibold uppercase text-slate-500">Gross Total</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">₱{{ number_format($grossTotal, 2) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-semibold uppercase text-slate-500">Net Total</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">₱{{ number_format($netTotal, 2) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-semibold uppercase text-slate-500">Status</p>
            <p class="mt-2"><span class="badge {{ $statusColor }}">{{ $statusLabels[$payRun->status] ?? 'Unknown' }}</span></p>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="border-b border-slate-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-slate-900">Payslips</h2>
            <p class="mt-1 text-sm text-slate-600">Employee salary details for this pay period</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-6 py-3">Employee</th>
                        <th class="px-6 py-3 text-right">Gross</th>
                        <th class="px-6 py-3 text-right">Deductions</th>
                        <th class="px-6 py-3 text-right">Tax</th>
                        <th class="px-6 py-3 text-right">Net</th>
                        <th class="px-6 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($payRun->payslips as $payslip)
                        @php
                            $payslipStatusLabels = [1 => 'Draft', 2 => 'Approved', 3 => 'Released'];
                            $payslipStatusColor = match((int) $payslip->status) {
                                3 => 'badge-green',
                                2 => 'badge-blue',
                                1 => 'badge-gray',
                                default => 'badge-gray'
                            };
                        @endphp
                        <tr>
                            <td class="px-6 py-4 font-medium text-slate-900">{{ $payslip->employee->full_name }}</td>
                            <td class="px-6 py-4 text-right text-slate-600">₱{{ number_format($payslip->gross_pay, 2) }}</td>
                            <td class="px-6 py-4 text-right text-slate-600">₱{{ number_format($payslip->total_deductions, 2) }}</td>
                            <td class="px-6 py-4 text-right text-slate-600">₱{{ number_format($payslip->tax, 2) }}</td>
                            <td class="px-6 py-4 text-right font-semibold text-slate-900">₱{{ number_format($payslip->net_pay, 2) }}</td>
                            <td class="px-6 py-4"><span class="badge {{ $payslipStatusColor }}">{{ $payslipStatusLabels[$payslip->status] ?? 'Unknown' }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                No payslips generated for this pay run.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
