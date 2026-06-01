<x-app-layout>
    <x-slot:title>Payslip Disputes</x-slot:title>
    <x-slot:header>Payslip Disputes</x-slot:header>

    @php $user = auth()->user(); $isHR = $user?->role === 4; @endphp

    {{-- Page Header --}}
    <div class="mb-6 flex items-center justify-between pb-6 border-b border-slate-200">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Payslip Disputes</h1>
            <p class="mt-1 text-sm text-slate-600">Review and resolve disputes raised by employees regarding their processed payslips.</p>
        </div>
        <div class="flex items-center gap-2">
            @if($isHR)
            <a href="{{ route('payroll.disputes.export') }}" id="btnExport"
                class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2.5 rounded-lg transition border border-slate-200 flex items-center gap-2 font-medium text-sm">
                <i class="ti ti-download text-base"></i> Export CSV
            </a>
            @endif
            <button type="button" id="openDisputeModal"
                class="bg-blue-600 text-white px-5 py-2.5 rounded-lg hover:bg-blue-700 transition flex items-center gap-2 font-medium text-sm whitespace-nowrap">
                <i class="ti ti-plus text-base"></i> File a Dispute
            </button>
        </div>
    </div>

    {{-- Stats (HR only) --}}
    @if($isHR)
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
        <div class="card p-5">
            <p class="text-xs text-slate-500 uppercase tracking-wide">Pending</p>
            <p class="mt-1 text-2xl font-bold text-amber-600">{{ $totalPending }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs text-slate-500 uppercase tracking-wide">Resolved</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600">{{ $totalResolved }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs text-slate-500 uppercase tracking-wide">Rejected</p>
            <p class="mt-1 text-2xl font-bold text-rose-600">{{ $totalRejected }}</p>
        </div>
    </div>
    @endif

    {{-- Live Filters --}}
    <div class="card p-4 mb-4">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-medium text-slate-600 mb-1">Search</label>
                <div class="relative">
                    <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="text" id="filterSearch" placeholder="Employee, reason…" value="{{ $filters['q'] ?? '' }}"
                        class="w-full rounded-lg border border-slate-200 pl-8 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div class="min-w-[140px]">
                <label class="block text-xs font-medium text-slate-600 mb-1">Status</label>
                <select id="filterStatus" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All</option>
                    <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="resolved" {{ ($filters['status'] ?? '') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                    <option value="rejected" {{ ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="min-w-[140px]">
                <label class="block text-xs font-medium text-slate-600 mb-1">Start Date</label>
                <input type="date" id="filterStartDate" value="{{ $filters['start_date'] ?? '' }}"
                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="min-w-[140px]">
                <label class="block text-xs font-medium text-slate-600 mb-1">End Date</label>
                <input type="date" id="filterEndDate" value="{{ $filters['end_date'] ?? '' }}"
                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="button" id="clearFilters" class="px-4 py-2 rounded-lg border border-slate-200 text-sm text-slate-600 hover:bg-slate-50 transition flex items-center gap-1.5">
                <i class="ti ti-x text-xs"></i> Clear
            </button>
        </div>
        <p id="filterCount" class="mt-2 text-xs text-slate-400 hidden"></p>
    </div>

    {{-- Claims Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table id="disputesTable" class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        @if($isHR)<th class="px-4 py-3">Filed By</th>@endif
                        <th class="px-4 py-3">Pay Run</th>
                        <th class="px-4 py-3 text-right">Dispute Amount</th>
                        <th class="px-4 py-3">Reason</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Resolved By</th>
                        <th class="px-4 py-3">Resolved At</th>
                        <th class="px-4 py-3">HR Notes</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody id="disputesTableBody" class="divide-y divide-slate-100">
                    @forelse($disputes as $dispute)
                    @php
                        $statusLabels = [1 => 'Pending', 2 => 'Resolved', 3 => 'Rejected'];
                        $statusColors = [1 => 'badge-amber', 2 => 'badge-emerald', 3 => 'badge-rose'];
                        $statusLabel = $statusLabels[$dispute->status] ?? 'Unknown';
                        $statusColor = $statusColors[$dispute->status] ?? 'badge-gray';
                    @endphp
                    <tr class="hover:bg-slate-50 transition dispute-row"
                        data-employee="{{ strtolower($dispute->employee?->full_name ?? '') }}"
                        data-status="{{ strtolower($statusLabel) }}"
                        data-date="{{ $dispute->created_at->toDateString() }}"
                        data-reason="{{ strtolower($dispute->dispute_reason ?? '') }}">
                        
                        @if($isHR)
                        <td class="px-4 py-3 font-medium text-slate-900 whitespace-nowrap">
                            {{ $dispute->employee?->full_name ?? '—' }}
                        </td>
                        @endif
                        <td class="px-4 py-3 text-slate-600 whitespace-nowrap text-xs">
                            @if($dispute->payslip && $dispute->payslip->payRun)
                                <a href="{{ route('payroll.show', $dispute->payslip->payRun) }}" class="text-blue-600 hover:underline font-medium text-sm">
                                    {{ \Carbon\Carbon::parse($dispute->payslip->payRun->period_start)->format('M d') }} – {{ \Carbon\Carbon::parse($dispute->payslip->payRun->period_end)->format('M d, Y') }}
                                </a>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-900 whitespace-nowrap">
                            ₱{{ number_format($dispute->dispute_amount, 2) }}
                            @if($dispute->lineItem)
                                <div class="text-[10px] font-normal text-slate-500 max-w-[120px] truncate" title="{{ $dispute->lineItem->description }}">
                                    Item: {{ $dispute->lineItem->description }}
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-600 max-w-[200px] truncate" title="{{ $dispute->dispute_reason }}">
                            {{ $dispute->dispute_reason ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="badge {{ $statusColor }}">{{ $statusLabel }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-600 whitespace-nowrap">
                            {{ $dispute->resolver ? $dispute->resolver->name : '—' }}
                        </td>
                        <td class="px-4 py-3 text-slate-600 whitespace-nowrap text-xs">
                            {{ $dispute->resolved_at ? $dispute->resolved_at->format('M d, Y h:i A') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-slate-600 text-xs max-w-[150px] truncate" title="{{ $dispute->hr_notes }}">
                            {{ $dispute->hr_notes ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                @if($isHR && $dispute->status === 1)
                                    <button type="button"
                                        class="action-icon btn-resolve text-emerald-600 hover:text-emerald-800 transition"
                                        title="Resolve"
                                        data-id="{{ $dispute->id }}"
                                        data-employee="{{ $dispute->employee?->full_name }}">
                                        <i class="ti ti-check text-lg"></i>
                                    </button>
                                    <button type="button"
                                        class="action-icon btn-reject text-rose-500 hover:text-rose-700 transition"
                                        title="Reject"
                                        data-id="{{ $dispute->id }}"
                                        data-employee="{{ $dispute->employee?->full_name }}">
                                        <i class="ti ti-x text-lg"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr id="emptyRow">
                        <td colspan="{{ $isHR ? 9 : 8 }}" class="px-6 py-16 text-center text-slate-500">
                            <div class="flex flex-col items-center gap-3">
                                <i class="ti ti-alert-circle text-4xl text-slate-300"></i>
                                <p class="font-medium">No disputes found.</p>
                                <p class="text-xs text-slate-400">Use "File a Dispute" to report an issue with a payslip.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-table-pagination target="disputesTable" />

    {{-- ===== FILE DISPUTE MODAL ===== --}}
    <div id="disputeModal" class="fixed inset-0 z-50 hidden justify-center items-start sm:items-center bg-slate-950/40 p-4 backdrop-blur-sm overflow-y-auto">
        <div class="my-auto w-full max-w-lg rounded-2xl border border-slate-200 bg-white shadow-xl max-h-[calc(100vh-2rem)] sm:max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">File a Payslip Dispute</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Report an issue with a processed payslip.</p>
                </div>
                <button type="button" id="closeDisputeModal" class="text-slate-400 hover:text-slate-600">
                    <i class="ti ti-x text-xl"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('payroll.disputes.store') }}" class="px-6 py-5 space-y-4">
                @csrf
                
                @if($isHR)
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Employee <span class="text-rose-500">*</span></label>
                    <select name="employee_id" id="disputeEmployeeSelect" required
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select employee…</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Select Payslip <span class="text-rose-500">*</span></label>
                    <select name="payslip_id" id="disputePayslipSelect" required
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-slate-100 disabled:text-slate-400"
                        @if($isHR) disabled @endif>
                        <option value="">{{ $isHR ? 'Select an employee first…' : 'Select payslip…' }}</option>
                        @if(!$isHR)
                            @foreach($payslips as $ps)
                                <option value="{{ $ps->id }}">
                                    {{ $ps->payRun ? $ps->payRun->name . ' (' . \Carbon\Carbon::parse($ps->payRun->period_start)->format('M d') . ' - ' . \Carbon\Carbon::parse($ps->payRun->period_end)->format('M d, Y') . ')' : 'Unknown Pay Run' }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Item in Question <span class="text-slate-400">(optional)</span></label>
                    <select name="payslip_line_item_id" id="disputeItemSelect"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-slate-100 disabled:text-slate-400" disabled>
                        <option value="">Entire Payslip / Missing Computation</option>
                    </select>
                    <p class="text-[10px] text-slate-400 mt-1">Select a specific deduction/earning to pre-fill the amount.</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Dispute Amount (₱) <span class="text-rose-500">*</span></label>
                    <input type="number" name="dispute_amount" id="disputeAmountInput" required min="0" step="0.01" placeholder="0.00"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-[10px] text-slate-400 mt-1">Enter the exact amount you are disputing (can be a partial amount).</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Reason / Explanation <span class="text-rose-500">*</span></label>
                    <textarea name="dispute_reason" rows="3" required placeholder="Describe the issue in detail…"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-2 border-t border-slate-100">
                    <button type="button" id="cancelDisputeModal"
                        class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-5 py-2 rounded-xl bg-blue-600 text-sm font-medium text-white hover:bg-blue-700 transition">
                        Submit Dispute
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($isHR)
    {{-- ===== RESOLVE MODAL ===== --}}
    <div id="resolveModal" class="fixed inset-0 z-50 hidden justify-center items-start sm:items-center bg-slate-950/40 p-4 backdrop-blur-sm overflow-y-auto">
        <div class="my-auto w-full max-w-md rounded-2xl border border-slate-200 bg-white shadow-xl max-h-[calc(100vh-2rem)] sm:max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Resolve Dispute</h3>
                    <p id="resolveSubtitle" class="text-xs text-slate-500 mt-0.5"></p>
                </div>
                <button type="button" id="closeResolveModal" class="text-slate-400 hover:text-slate-600">
                    <i class="ti ti-x text-xl"></i>
                </button>
            </div>
            <form id="resolveForm" method="POST" class="px-6 py-5 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">HR Notes <span class="text-slate-400">(optional)</span></label>
                    <textarea name="hr_notes" rows="3" placeholder="Notes on how this was resolved (e.g. adjustments made in next payroll)…"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-2 border-t border-slate-100">
                    <button type="button" id="cancelResolveModal"
                        class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-5 py-2 rounded-xl bg-emerald-600 text-sm font-medium text-white hover:bg-emerald-700 transition flex items-center gap-2">
                        <i class="ti ti-check"></i> Mark Resolved
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== REJECT MODAL ===== --}}
    <div id="rejectModal" class="fixed inset-0 z-50 hidden justify-center items-start sm:items-center bg-slate-950/40 p-4 backdrop-blur-sm overflow-y-auto">
        <div class="my-auto w-full max-w-md rounded-2xl border border-slate-200 bg-white shadow-xl max-h-[calc(100vh-2rem)] sm:max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Reject Dispute</h3>
                    <p id="rejectSubtitle" class="text-xs text-slate-500 mt-0.5"></p>
                </div>
                <button type="button" id="closeRejectModal" class="text-slate-400 hover:text-slate-600">
                    <i class="ti ti-x text-xl"></i>
                </button>
            </div>
            <form id="rejectForm" method="POST" class="px-6 py-5 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Reason for Rejection <span class="text-rose-500">*</span></label>
                    <textarea name="hr_notes" rows="3" required placeholder="Explain why this dispute is rejected…"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-2 border-t border-slate-100">
                    <button type="button" id="cancelRejectModal"
                        class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-5 py-2 rounded-xl bg-rose-600 text-sm font-medium text-white hover:bg-rose-700 transition flex items-center gap-2">
                        <i class="ti ti-x"></i> Reject Dispute
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <x-slot:scripts>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // ===== LIVE FILTERING =====
        const searchInput  = document.getElementById('filterSearch');
        const statusSelect = document.getElementById('filterStatus');
        const filterStartDate = document.getElementById('filterStartDate');
        const filterEndDate   = document.getElementById('filterEndDate');
        const clearBtn     = document.getElementById('clearFilters');
        const filterCount  = document.getElementById('filterCount');
        const rows         = document.querySelectorAll('.dispute-row');
        const totalRows    = rows.length;

        let noResultsRow = null;
        function getColspan() { return {{ $isHR ? 9 : 8 }}; }

        function applyFilters() {
            const q         = (searchInput?.value  || '').toLowerCase().trim();
            const status    = (statusSelect?.value || '').toLowerCase();
            const startDate = filterStartDate?.value || '';
            const endDate   = filterEndDate?.value || '';

            let visible = 0;

            rows.forEach(row => {
                const matchQ = !q ||
                    row.dataset.employee.includes(q) ||
                    row.dataset.reason.includes(q);

                const matchStatus = !status || row.dataset.status === status;
                
                const rowDate = row.dataset.date;
                const matchDate = (!startDate || rowDate >= startDate) && (!endDate || rowDate <= endDate);

                const show = matchQ && matchStatus && matchDate;
                row.setAttribute('data-filter-hidden', show ? 'false' : 'true');
                row.style.display = show ? '' : 'none';
                if (show) visible++;
            });

            // Update export button URL dynamically
            const exportBtn = document.getElementById('btnExport');
            if (exportBtn) {
                let url = "{{ route('payroll.disputes.export') }}";
                const params = [];
                if (q) params.push(`q=${encodeURIComponent(q)}`);
                if (status) params.push(`status=${encodeURIComponent(status)}`);
                if (startDate) params.push(`start_date=${encodeURIComponent(startDate)}`);
                if (endDate) params.push(`end_date=${encodeURIComponent(endDate)}`);
                if (params.length > 0) {
                    url += `?${params.join('&')}`;
                }
                exportBtn.href = url;
            }

            const tbody = document.getElementById('disputesTableBody');
            if (visible === 0 && totalRows > 0) {
                if (!noResultsRow) {
                    noResultsRow = document.createElement('tr');
                    noResultsRow.id = 'noResultsRow';
                    noResultsRow.innerHTML = `<td colspan="${getColspan()}" class="px-6 py-12 text-center text-slate-400"><div class="flex flex-col items-center gap-2"><i class="ti ti-search-off text-3xl text-slate-300"></i><p class="text-sm font-medium">No disputes match your filters.</p></div></td>`;
                    tbody.appendChild(noResultsRow);
                }
                noResultsRow.style.display = '';
            } else if (noResultsRow) {
                noResultsRow.style.display = 'none';
            }

            const isFiltered = q || status || startDate || endDate;
            if (isFiltered && filterCount) {
                filterCount.textContent = `Showing ${visible} of ${totalRows} dispute${totalRows !== 1 ? 's' : ''}`;
                filterCount.classList.remove('hidden');
            } else if (filterCount) {
                filterCount.classList.add('hidden');
            }
        }

        let searchTimer;
        searchInput?.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(applyFilters, 180);
        });

        statusSelect?.addEventListener('change', applyFilters);
        filterStartDate?.addEventListener('change', applyFilters);
        filterEndDate?.addEventListener('change', applyFilters);

        clearBtn?.addEventListener('click', () => {
            if (searchInput)     searchInput.value     = '';
            if (statusSelect)    statusSelect.value    = '';
            if (filterStartDate) filterStartDate.value = '';
            if (filterEndDate)   filterEndDate.value   = '';
            applyFilters();
        });

        // Apply filters on load to sync initial query state
        applyFilters();
        // ===== MODALS =====
        function openModal(el) { el.classList.remove('hidden'); el.classList.add('flex'); }
        function closeModal(el) { el.classList.remove('flex'); el.classList.add('hidden'); }

        const disputeModal = document.getElementById('disputeModal');
        const openBtn      = document.getElementById('openDisputeModal');
        const closeBtn     = document.getElementById('closeDisputeModal');
        const cancelBtn    = document.getElementById('cancelDisputeModal');

        openBtn?.addEventListener('click', () => openModal(disputeModal));
        closeBtn?.addEventListener('click', () => closeModal(disputeModal));
        cancelBtn?.addEventListener('click', () => closeModal(disputeModal));
        disputeModal?.addEventListener('click', e => { if (e.target === disputeModal) closeModal(disputeModal); });

        // AJAX for HR fetching payslips when employee changes
        const empSelect = document.getElementById('disputeEmployeeSelect');
        const psSelect  = document.getElementById('disputePayslipSelect');
        const itemSelect = document.getElementById('disputeItemSelect');
        const amountInput = document.getElementById('disputeAmountInput');

        // Store fetched items to map IDs to amounts
        let currentPayslipItems = {};

        if (empSelect && psSelect) {
            empSelect.addEventListener('change', async function() {
                const empId = this.value;
                psSelect.innerHTML = '<option value="">Loading payslips…</option>';
                psSelect.disabled = true;
                itemSelect.innerHTML = '<option value="">Entire Payslip / Missing Computation</option>';
                itemSelect.disabled = true;

                if (!empId) {
                    psSelect.innerHTML = '<option value="">Select an employee first…</option>';
                    return;
                }

                try {
                    const res = await fetch(`{{ url('/payroll/disputes/api/payslips') }}/${empId}`);
                    const data = await res.json();
                    
                    if (data.length === 0) {
                        psSelect.innerHTML = '<option value="">No recent payslips found.</option>';
                    } else {
                        psSelect.innerHTML = '<option value="">Select payslip…</option>';
                        data.forEach(ps => {
                            const opt = document.createElement('option');
                            opt.value = ps.id;
                            opt.textContent = ps.name;
                            psSelect.appendChild(opt);
                        });
                        psSelect.disabled = false;
                    }
                } catch (e) {
                    console.error(e);
                    psSelect.innerHTML = '<option value="">Error loading payslips.</option>';
                }
            });
        }

        // Fetch line items when a payslip is selected
        psSelect?.addEventListener('change', async function() {
            const payslipId = this.value;
            itemSelect.innerHTML = '<option value="">Entire Payslip / Missing Computation</option>';
            currentPayslipItems = {};
            
            if (!payslipId) {
                itemSelect.disabled = true;
                return;
            }

            itemSelect.disabled = true;
            try {
                const res = await fetch(`{{ url('/payroll/disputes/api/payslip-items') }}/${payslipId}`);
                const data = await res.json();
                
                if (data.items && data.items.length > 0) {
                    data.items.forEach(item => {
                        currentPayslipItems[item.id] = item.amount;
                        const opt = document.createElement('option');
                        opt.value = item.id;
                        opt.textContent = `${item.type}: ${item.description} (₱${parseFloat(item.amount).toFixed(2)})`;
                        itemSelect.appendChild(opt);
                    });
                }
                itemSelect.disabled = false;
            } catch (e) {
                console.error('Failed to load payslip items', e);
            }
        });

        // Pre-fill amount when a specific line item is selected
        itemSelect?.addEventListener('change', function() {
            const itemId = this.value;
            if (itemId && currentPayslipItems[itemId] !== undefined) {
                amountInput.value = parseFloat(currentPayslipItems[itemId]).toFixed(2);
            } else {
                amountInput.value = '';
            }
        });

        @if($isHR)
        const resolveModal    = document.getElementById('resolveModal');
        const resolveForm     = document.getElementById('resolveForm');
        const resolveSubtitle = document.getElementById('resolveSubtitle');

        document.querySelectorAll('.btn-resolve').forEach(btn => {
            btn.addEventListener('click', function () {
                resolveSubtitle.textContent = this.dataset.employee;
                resolveForm.action = `/payroll/disputes/${this.dataset.id}/resolve`;
                openModal(resolveModal);
            });
        });
        document.getElementById('closeResolveModal')?.addEventListener('click', () => closeModal(resolveModal));
        document.getElementById('cancelResolveModal')?.addEventListener('click', () => closeModal(resolveModal));
        resolveModal?.addEventListener('click', e => { if (e.target === resolveModal) closeModal(resolveModal); });

        const rejectModal    = document.getElementById('rejectModal');
        const rejectForm     = document.getElementById('rejectForm');
        const rejectSubtitle = document.getElementById('rejectSubtitle');

        document.querySelectorAll('.btn-reject').forEach(btn => {
            btn.addEventListener('click', function () {
                rejectSubtitle.textContent = this.dataset.employee;
                rejectForm.action = `/payroll/disputes/${this.dataset.id}/reject`;
                openModal(rejectModal);
            });
        });
        document.getElementById('closeRejectModal')?.addEventListener('click', () => closeModal(rejectModal));
        document.getElementById('cancelRejectModal')?.addEventListener('click', () => closeModal(rejectModal));
        rejectModal?.addEventListener('click', e => { if (e.target === rejectModal) closeModal(rejectModal); });
        @endif
    });
    </script>
    </x-slot:scripts>
</x-app-layout>
