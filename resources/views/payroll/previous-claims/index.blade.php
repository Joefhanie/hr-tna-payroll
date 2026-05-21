<x-app-layout>
    <x-slot:title>Previous Claims</x-slot:title>
    <x-slot:header>Previous Claims</x-slot:header>

    @php $user = auth()->user(); $isHR = $user?->role === 4; @endphp

    {{-- Page Header --}}
    <div class="mb-6 flex items-center justify-between pb-6 border-b border-slate-200">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Previous Claims</h1>
            <p class="mt-1 text-sm text-slate-600">File claims for pay periods that have already passed. HR reviews and includes approved claims in the next pay run.</p>
        </div>
        <button type="button" id="openClaimModal"
            class="bg-blue-600 text-white px-5 py-2.5 rounded-lg hover:bg-blue-700 transition flex items-center gap-2 font-medium text-sm">
            <i class="ti ti-plus text-base"></i> File a Claim
        </button>
    </div>

    {{-- Stats (HR only) --}}
    @if($isHR)
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="card p-5">
            <p class="text-xs text-slate-500 uppercase tracking-wide">Pending</p>
            <p class="mt-1 text-2xl font-bold text-amber-600">{{ $totalPending }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs text-slate-500 uppercase tracking-wide">Approved</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600">{{ $totalApproved }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs text-slate-500 uppercase tracking-wide">Declined</p>
            <p class="mt-1 text-2xl font-bold text-rose-600">{{ $totalDeclined }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs text-slate-500 uppercase tracking-wide">Total Approved Amount</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">₱{{ number_format($totalAmount, 2) }}</p>
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
                    <input type="text" id="filterSearch" placeholder="Employee, type, description…"
                        class="w-full rounded-lg border border-slate-200 pl-8 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div class="min-w-[140px]">
                <label class="block text-xs font-medium text-slate-600 mb-1">Status</label>
                <select id="filterStatus" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="declined">Declined</option>
                </select>
            </div>
            <div class="min-w-[160px]">
                <label class="block text-xs font-medium text-slate-600 mb-1">Claim Type</label>
                <select id="filterType" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Types</option>
                    @foreach($claimTypes as $ct)
                        <option value="{{ strtolower($ct) }}">{{ $ct }}</option>
                    @endforeach
                </select>
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
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        @if($isHR)<th class="px-4 py-3">Employee</th>@endif
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Claim Date</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                        <th class="px-4 py-3">Description</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Pay Run</th>
                        <th class="px-4 py-3">HR Notes</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody id="claimsTableBody" class="divide-y divide-slate-100">
                    @forelse($claims as $claim)
                    <tr class="hover:bg-slate-50 transition claim-row"
                        data-employee="{{ strtolower($claim->employee?->full_name ?? '') }}"
                        data-type="{{ strtolower($claim->claim_type) }}"
                        data-status="{{ strtolower($claim->status_label) }}"
                        data-description="{{ strtolower($claim->description ?? '') }}">
                        @if($isHR)
                        <td class="px-4 py-3 font-medium text-slate-900 whitespace-nowrap">
                            {{ $claim->employee?->full_name ?? '—' }}
                        </td>
                        @endif
                        <td class="px-4 py-3 text-slate-700 whitespace-nowrap">
                            <span class="inline-flex items-center gap-1.5">
                                <i class="ti ti-tag text-slate-400"></i>
                                {{ $claim->claim_type }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-600 whitespace-nowrap">
                            {{ $claim->claim_date->format('M d, Y') }}
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-900 whitespace-nowrap">
                            ₱{{ number_format($claim->amount, 2) }}
                        </td>
                        <td class="px-4 py-3 text-slate-600 max-w-[200px] truncate" title="{{ $claim->description }}">
                            {{ $claim->description ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="badge {{ $claim->status_color }}">{{ $claim->status_label }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-600 whitespace-nowrap text-xs">
                            @if($claim->payRun)
                                <a href="{{ route('payroll.show', $claim->payRun) }}" class="text-blue-600 hover:underline">
                                    {{ $claim->payRun->period_start->format('M d') }} – {{ $claim->payRun->period_end->format('M d, Y') }}
                                </a>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-600 max-w-[160px] truncate text-xs" title="{{ $claim->hr_notes }}">
                            {{ $claim->hr_notes ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                @if($isHR && $claim->status === 1)
                                    <button type="button"
                                        class="btn-approve text-emerald-600 hover:text-emerald-800 transition"
                                        title="Approve"
                                        data-id="{{ $claim->id }}"
                                        data-employee="{{ $claim->employee?->full_name }}"
                                        data-amount="₱{{ number_format($claim->amount, 2) }}">
                                        <i class="ti ti-check text-lg"></i>
                                    </button>
                                    <button type="button"
                                        class="btn-decline text-rose-500 hover:text-rose-700 transition"
                                        title="Decline"
                                        data-id="{{ $claim->id }}"
                                        data-employee="{{ $claim->employee?->full_name }}">
                                        <i class="ti ti-x text-lg"></i>
                                    </button>
                                @endif
                                @if($claim->supporting_document)
                                    <a href="{{ asset('storage/' . $claim->supporting_document) }}"
                                        target="_blank" class="text-slate-500 hover:text-slate-700 transition" title="View Document">
                                        <i class="ti ti-paperclip text-lg"></i>
                                    </a>
                                @endif
                                @if($claim->status === 1 && ($isHR || $claim->submitted_by === $user?->id))
                                    <form method="POST" action="{{ route('payroll.previous-claims.destroy', $claim) }}">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                            class="text-slate-400 hover:text-rose-600 transition"
                                            title="Delete"
                                            data-confirm="Delete this pending claim?"
                                            data-confirm-title="Delete Claim">
                                            <i class="ti ti-trash text-lg"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr id="emptyRow">
                        <td colspan="{{ $isHR ? 9 : 8 }}" class="px-6 py-16 text-center text-slate-500">
                            <div class="flex flex-col items-center gap-3">
                                <i class="ti ti-file-invoice text-4xl text-slate-300"></i>
                                <p class="font-medium">No claims found.</p>
                                <p class="text-xs text-slate-400">Use "File a Claim" to submit a previous period claim.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== FILE CLAIM MODAL ===== --}}
    <div id="claimModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/40 p-4 backdrop-blur-sm">
        <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white shadow-xl overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">File a Previous Claim</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Submit a claim for a pay period that has already passed.</p>
                </div>
                <button type="button" id="closeClaimModal" class="text-slate-400 hover:text-slate-600">
                    <i class="ti ti-x text-xl"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('payroll.previous-claims.store') }}" enctype="multipart/form-data" class="px-6 py-5 space-y-4">
                @csrf

                @if($isHR)
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Employee <span class="text-rose-500">*</span></label>
                    <select name="employee_id" required
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select employee…</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                @else
                    <input type="hidden" name="employee_id" value="{{ $user?->employee_id }}">
                @endif

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Claim Type <span class="text-rose-500">*</span></label>
                        <select name="claim_type" required
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select type…</option>
                            @foreach($claimTypes as $ct)
                                <option value="{{ $ct }}">{{ $ct }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Claim Date <span class="text-rose-500">*</span></label>
                        <input type="date" name="claim_date" required max="{{ now()->subDay()->toDateString() }}"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Amount (₱) <span class="text-rose-500">*</span></label>
                    <input type="number" name="amount" required min="0.01" step="0.01" placeholder="0.00"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Description</label>
                    <textarea name="description" rows="3" placeholder="Briefly describe the claim…"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Supporting Document <span class="text-slate-400">(PDF, image, Word — max 5MB)</span></label>
                    <input type="file" name="supporting_document" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                        class="w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-medium hover:file:bg-slate-200 transition">
                </div>

                <div class="flex justify-end gap-3 pt-2 border-t border-slate-100">
                    <button type="button" id="cancelClaimModal"
                        class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-5 py-2 rounded-xl bg-blue-600 text-sm font-medium text-white hover:bg-blue-700 transition">
                        Submit Claim
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== APPROVE MODAL (HR) ===== --}}
    @if($isHR)
    <div id="approveModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/40 p-4 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white shadow-xl overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Approve Claim</h3>
                    <p id="approveSubtitle" class="text-xs text-slate-500 mt-0.5"></p>
                </div>
                <button type="button" id="closeApproveModal" class="text-slate-400 hover:text-slate-600">
                    <i class="ti ti-x text-xl"></i>
                </button>
            </div>
            <form id="approveForm" method="POST" class="px-6 py-5 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Assign to Pay Run <span class="text-slate-400">(optional)</span></label>
                    <select name="pay_run_id"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">— Will be included in the next pay run —</option>
                        @foreach($openPayRuns as $pr)
                            <option value="{{ $pr->id }}">
                                {{ $pr->name }} ({{ $pr->period_start->format('M d') }} – {{ $pr->period_end->format('M d, Y') }})
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-400">Leave blank to simply mark as approved without assigning to a specific run.</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">HR Notes <span class="text-slate-400">(optional)</span></label>
                    <textarea name="hr_notes" rows="3" placeholder="Internal notes for this approval…"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-2 border-t border-slate-100">
                    <button type="button" id="cancelApproveModal"
                        class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-5 py-2 rounded-xl bg-emerald-600 text-sm font-medium text-white hover:bg-emerald-700 transition flex items-center gap-2">
                        <i class="ti ti-check"></i> Approve Claim
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== DECLINE MODAL (HR) ===== --}}
    <div id="declineModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/40 p-4 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white shadow-xl overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Decline Claim</h3>
                    <p id="declineSubtitle" class="text-xs text-slate-500 mt-0.5"></p>
                </div>
                <button type="button" id="closeDeclineModal" class="text-slate-400 hover:text-slate-600">
                    <i class="ti ti-x text-xl"></i>
                </button>
            </div>
            <form id="declineForm" method="POST" class="px-6 py-5 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Reason for Declining <span class="text-rose-500">*</span></label>
                    <textarea name="hr_notes" rows="4" required placeholder="Explain why this claim is being declined…"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-2 border-t border-slate-100">
                    <button type="button" id="cancelDeclineModal"
                        class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-5 py-2 rounded-xl bg-rose-600 text-sm font-medium text-white hover:bg-rose-700 transition flex items-center gap-2">
                        <i class="ti ti-x"></i> Decline Claim
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
        const typeSelect   = document.getElementById('filterType');
        const clearBtn     = document.getElementById('clearFilters');
        const filterCount  = document.getElementById('filterCount');
        const rows         = document.querySelectorAll('.claim-row');
        const totalRows    = rows.length;

        // Dynamic empty-state row (injected when all rows are hidden)
        let noResultsRow = null;
        function getColspan() { return {{ $isHR ? 9 : 8 }}; }

        function applyFilters() {
            const q      = (searchInput?.value  || '').toLowerCase().trim();
            const status = (statusSelect?.value || '').toLowerCase();
            const type   = (typeSelect?.value   || '').toLowerCase();

            let visible = 0;

            rows.forEach(row => {
                const matchQ = !q ||
                    row.dataset.employee.includes(q) ||
                    row.dataset.type.includes(q) ||
                    row.dataset.description.includes(q);

                const matchStatus = !status || row.dataset.status === status;
                const matchType   = !type   || row.dataset.type === type;

                const show = matchQ && matchStatus && matchType;
                row.style.display = show ? '' : 'none';
                if (show) visible++;
            });

            // Show/hide dynamic no-results row
            const tbody = document.getElementById('claimsTableBody');
            if (visible === 0 && totalRows > 0) {
                if (!noResultsRow) {
                    noResultsRow = document.createElement('tr');
                    noResultsRow.id = 'noResultsRow';
                    noResultsRow.innerHTML = `<td colspan="${getColspan()}" class="px-6 py-12 text-center text-slate-400"><div class="flex flex-col items-center gap-2"><i class="ti ti-search-off text-3xl text-slate-300"></i><p class="text-sm font-medium">No claims match your filters.</p></div></td>`;
                    tbody.appendChild(noResultsRow);
                }
                noResultsRow.style.display = '';
            } else if (noResultsRow) {
                noResultsRow.style.display = 'none';
            }

            // Filter count hint
            const isFiltered = q || status || type;
            if (isFiltered && filterCount) {
                filterCount.textContent = `Showing ${visible} of ${totalRows} claim${totalRows !== 1 ? 's' : ''}`;
                filterCount.classList.remove('hidden');
            } else if (filterCount) {
                filterCount.classList.add('hidden');
            }
        }

        // Debounce helper for search input
        let searchTimer;
        searchInput?.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(applyFilters, 180);
        });

        // Dropdowns apply immediately
        statusSelect?.addEventListener('change', applyFilters);
        typeSelect?.addEventListener('change', applyFilters);

        // Clear all filters
        clearBtn?.addEventListener('click', () => {
            if (searchInput)  searchInput.value  = '';
            if (statusSelect) statusSelect.value = '';
            if (typeSelect)   typeSelect.value   = '';
            applyFilters();
        });

        // ===== MODALS =====
        function openModal(el) { el.classList.remove('hidden'); el.classList.add('flex'); }
        function closeModal(el) { el.classList.remove('flex'); el.classList.add('hidden'); }

        const claimModal = document.getElementById('claimModal');
        const openBtn    = document.getElementById('openClaimModal');
        const closeBtn   = document.getElementById('closeClaimModal');
        const cancelBtn  = document.getElementById('cancelClaimModal');

        openBtn?.addEventListener('click', () => openModal(claimModal));
        closeBtn?.addEventListener('click', () => closeModal(claimModal));
        cancelBtn?.addEventListener('click', () => closeModal(claimModal));
        claimModal?.addEventListener('click', e => { if (e.target === claimModal) closeModal(claimModal); });

        @if($isHR)
        const approveModal    = document.getElementById('approveModal');
        const approveForm     = document.getElementById('approveForm');
        const approveSubtitle = document.getElementById('approveSubtitle');

        document.querySelectorAll('.btn-approve').forEach(btn => {
            btn.addEventListener('click', function () {
                approveSubtitle.textContent = `${this.dataset.employee} — ${this.dataset.amount}`;
                approveForm.action = `/payroll/previous-claims/${this.dataset.id}/approve`;
                openModal(approveModal);
            });
        });
        document.getElementById('closeApproveModal')?.addEventListener('click', () => closeModal(approveModal));
        document.getElementById('cancelApproveModal')?.addEventListener('click', () => closeModal(approveModal));
        approveModal?.addEventListener('click', e => { if (e.target === approveModal) closeModal(approveModal); });

        const declineModal    = document.getElementById('declineModal');
        const declineForm     = document.getElementById('declineForm');
        const declineSubtitle = document.getElementById('declineSubtitle');

        document.querySelectorAll('.btn-decline').forEach(btn => {
            btn.addEventListener('click', function () {
                declineSubtitle.textContent = this.dataset.employee;
                declineForm.action = `/payroll/previous-claims/${this.dataset.id}/decline`;
                openModal(declineModal);
            });
        });
        document.getElementById('closeDeclineModal')?.addEventListener('click', () => closeModal(declineModal));
        document.getElementById('cancelDeclineModal')?.addEventListener('click', () => closeModal(declineModal));
        declineModal?.addEventListener('click', e => { if (e.target === declineModal) closeModal(declineModal); });
        @endif
    });
    </script>
    </x-slot:scripts>
</x-app-layout>
