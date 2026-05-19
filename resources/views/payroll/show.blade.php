<x-app-layout>
    <x-slot:title>Payroll Details - {{ $payRun->name }}</x-slot:title>
    <x-slot:header>Payroll Details</x-slot:header>

    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold">{{ $payRun->name }}</h1>
            <p class="text-sm text-slate-500">Pay period: {{ $payRun->period_start->format('M d, Y') }} – {{ $payRun->period_end->format('M d, Y') }}</p>
        </div>
        <div class="flex items-center gap-3">
            @if ($payRun->status == 1 || $payRun->status == 2)
                <form action="{{ route('payroll.finalize', $payRun) }}" method="POST">
                    @csrf
                    <button type="submit" onclick="return confirm('Are you sure you want to finalize this pay run? This will approve all payslips.')" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                        Finalize Pay Run
                    </button>
                </form>
            @endif
            <a href="{{ route('payroll.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">Back</a>
        </div>
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
                        <th class="px-6 py-3 text-right">Net</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="w-12"></th>
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
                            
                            $payRunEnd = \Carbon\Carbon::parse($payRun->period_end)->toDateString();
                            $salaryRecord = $payslip->employee->salaryRecords
                                ->filter(function ($record) use ($payRunEnd) {
                                    $eff = \Carbon\Carbon::parse($record->effective_date)->toDateString();
                                    $end = $record->end_date ? \Carbon\Carbon::parse($record->end_date)->toDateString() : null;
                                    return $eff <= $payRunEnd && (!$end || $end >= $payRunEnd);
                                })
                                ->sortByDesc('effective_date')
                                ->first();
                                
                            $payFrequencyLabels = [1 => 'Hourly', 2 => 'Daily', 3 => 'Weekly', 4 => 'Bi-weekly', 5 => 'Monthly', 6 => 'Annual'];
                            $frequency = $salaryRecord ? ($payFrequencyLabels[$salaryRecord->pay_frequency] ?? 'Unknown') : 'Unknown';
                            $baseSalary = $salaryRecord ? $salaryRecord->amount : 0;
                        @endphp
                        <tr>
                            <td class="px-6 py-4 font-medium text-slate-900">{{ $payslip->employee->full_name }}</td>
                            <td class="px-6 py-4 text-right text-slate-600">₱{{ number_format($payslip->gross_pay, 2) }}</td>
                            <td class="px-6 py-4 text-right text-slate-600">₱{{ number_format($payslip->total_deductions, 2) }}</td>
                            <td class="px-6 py-4 text-right font-semibold text-slate-900">₱{{ number_format($payslip->net_pay, 2) }}</td>
                            <td class="px-6 py-4"><span class="badge {{ $payslipStatusColor }}">{{ $payslipStatusLabels[$payslip->status] ?? 'Unknown' }}</span></td>
                            <td class="px-4 py-4 text-center">
                                <button type="button" class="text-indigo-500 hover:text-indigo-700 transition flex items-center justify-center w-full" title="View Breakdown"
                                        onclick="viewPayslip('{{ addslashes($payslip->employee->full_name) }}', {{ $baseSalary }}, '{{ $frequency }}', {{ $payslip->gross_pay }}, {{ $payslip->total_deductions }}, {{ $payslip->net_pay }}, {{ json_encode($payslip->lineItems) }})">
                                    <i class="ti ti-eye text-[1.15rem]"></i>
                                </button>
                            </td>
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

    <!-- Payslip Breakdown Modal -->
    <div id="payslipModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 p-4 transition-opacity" style="padding-left: var(--sidebar-width);">
        <div class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-[0_25px_50px_-12px_rgba(0,0,0,0.45)] ring-4 ring-black/5">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 bg-slate-50/50">
                <div class="flex items-center gap-6">
                    <div>
                        <h3 id="psModalTitle" class="text-lg font-bold text-[#06112e]">Payslip Breakdown</h3>
                        <p id="psModalSubtitle" class="mt-0.5 text-sm font-medium text-slate-600"></p>
                    </div>
                    <div class="h-8 w-px bg-slate-200 hidden sm:block"></div>
                    <div class="hidden sm:block">
                        <p class="text-[0.65rem] font-bold uppercase tracking-wider text-slate-400">Base Salary</p>
                        <p class="mt-0.5 text-sm font-semibold text-slate-700"><span id="psBaseSalary"></span> <span class="text-slate-400 font-normal">/</span> <span id="psFrequency" class="text-slate-500"></span></p>
                    </div>
                </div>
                <button type="button" onclick="closePayslipModal()" class="text-slate-400 transition hover:text-slate-600">
                    <i class="ti ti-x text-xl"></i>
                </button>
            </div>
            <div class="overflow-y-auto px-6 py-5">
                <div class="grid gap-6 sm:grid-cols-2">
                    <!-- Earnings -->
                    <div class="rounded-lg border border-slate-200 bg-white p-4">
                        <h4 class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-500">Earnings</h4>
                        <div id="psEarningsList" class="space-y-2 text-sm text-slate-700"></div>
                        <div class="mt-3 flex justify-between border-t border-slate-100 pt-2 font-bold text-slate-900">
                            <span>Gross Pay</span>
                            <span id="psGrossPay"></span>
                        </div>
                    </div>
                    <!-- Deductions -->
                    <div class="rounded-lg border border-slate-200 bg-white p-4">
                        <h4 class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-500">Deductions & Taxes</h4>
                        <div id="psDeductionsList" class="space-y-2 text-sm text-slate-700"></div>
                        <div class="mt-3 flex justify-between border-t border-slate-100 pt-2 font-bold text-red-600">
                            <span>Total Deductions</span>
                            <span id="psTotalDeductions"></span>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 flex items-center justify-between rounded-lg border border-indigo-100 bg-indigo-50 p-4">
                    <span class="font-bold text-indigo-900">Net Take-Home Pay</span>
                    <span id="psNetPay" class="text-xl font-black text-indigo-700"></span>
                </div>
            </div>
            <div class="flex justify-end border-t border-slate-100 px-6 py-4">
                <button type="button" onclick="closePayslipModal()" class="rounded-[0.5rem] border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-[#06112e] shadow-sm transition hover:bg-slate-50">Close</button>
            </div>
        </div>
    </div>

    <script>
        function viewPayslip(employeeName, baseSalary, frequency, gross, deductions, net, lineItems) {
            document.getElementById('psModalSubtitle').textContent = employeeName;
            document.getElementById('psBaseSalary').textContent = '₱' + parseFloat(baseSalary).toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('psFrequency').textContent = frequency;
            
            document.getElementById('psGrossPay').textContent = '₱' + parseFloat(gross).toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('psTotalDeductions').textContent = '₱' + parseFloat(deductions).toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('psNetPay').textContent = '₱' + parseFloat(net).toLocaleString('en-US', {minimumFractionDigits: 2});
            
            let earningsHtml = '';
            let deductionsHtml = '';
            
            if (Array.isArray(lineItems)) {
                lineItems.forEach(item => {
                    const amount = parseFloat(item.amount).toLocaleString('en-US', {minimumFractionDigits: 2});
                    const html = `<div class="flex justify-between"><span>${item.description}</span><span>₱${amount}</span></div>`;
                    
                    if (item.component_type === 1) { // Earning
                        earningsHtml += html;
                    } else { // Deduction, Tax, Gov
                        deductionsHtml += html;
                    }
                });
            }
            
            document.getElementById('psEarningsList').innerHTML = earningsHtml || '<div class="text-slate-400 italic">None</div>';
            document.getElementById('psDeductionsList').innerHTML = deductionsHtml || '<div class="text-slate-400 italic">None</div>';
            
            document.getElementById('payslipModal').classList.replace('hidden', 'flex');
        }

        function closePayslipModal() {
            document.getElementById('payslipModal').classList.replace('flex', 'hidden');
        }
    </script>
</x-app-layout>
