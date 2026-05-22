<x-app-layout>
    <x-slot:title>Leave</x-slot:title>
    <x-slot:header>Leave</x-slot:header>

    {{-- Page Header --}}
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-[1.65rem] font-bold text-[#06112e]">Leave</h1>
            <p class="mt-1 text-sm text-slate-500">Leave requests, approvals, and balances.</p>
        </div>
        <div class="flex items-center gap-2">
            @if(auth()->user()->role === 4)
            <a href="{{ route('leave.export') }}" id="btnExport"
                class="inline-flex items-center gap-1.5 rounded-[0.5rem] border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export CSV
            </a>
            @endif
            @if(auth()->user()->hasPermission('leaves.create'))
            <button type="button" id="openRequestLeaveModal"
                class="inline-flex items-center gap-2 rounded-[0.5rem] bg-[#1a56db] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1e40af]">
                <i class="ti ti-plus text-base"></i>
                Request Leave
            </button>
            @endif
        </div>
    </div>

    <div class="mb-4 h-px w-full bg-slate-200"></div>

    {{-- Leave Balance Cards --}}
    @if($balances->isNotEmpty())
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        @foreach ($balances as $balance)
            @php
                $entitled = (float) $balance->entitled_days;
                $used     = (float) $balance->used_days;
                $remaining = max(0, $entitled - $used);
                $pct = $entitled > 0 ? min(100, ($used / $entitled) * 100) : 0;
                $barColor = $pct >= 80 ? '#ef4444' : ($pct >= 50 ? '#f59e0b' : '#1a56db');
            @endphp
            <div class="rounded-[0.8rem] border border-slate-200 bg-white p-4 shadow-[0_1px_2px_rgba(0,0,0,0.03)]">
                <p class="text-[0.8rem] text-slate-500">{{ $balance->name }}</p>
                <p class="mt-1 text-xl font-bold text-[#06112e]">
                    {{ number_format($remaining, $remaining == floor($remaining) ? 0 : 1) }}
                    <span class="text-[0.9rem] font-normal text-slate-400">/ {{ (int) $entitled }}</span>
                </p>
                <div class="mt-3 h-[0.35rem] w-full overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full transition-all duration-500"
                         style="width: {{ $pct }}%; background-color: {{ $barColor }}"></div>
                </div>
                <p class="mt-2 text-[0.7rem] text-slate-500">{{ number_format($used, $used == floor($used) ? 0 : 1) }} used</p>
            </div>
        @endforeach
    </div>
    @endif

    {{-- Filters --}}
    <form id="filterForm" method="GET" action="{{ route('leave.index') }}" class="mb-4">
        {{--
            Mobile  : flex-col → each row stacks; grids give 2-col layout for dropdowns/dates
            Desktop : flex-row flex-wrap → everything inline in one row (sm:contents dissolves grid wrappers)
        --}}
        <div class="flex flex-col sm:flex-row sm:flex-wrap sm:items-center gap-2">

            {{-- Search: full-width on mobile, auto-growing on desktop --}}
            <div class="relative sm:flex-1 sm:min-w-[180px]">
                <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"></i>
                <input type="text" name="q" id="filterSearch" value="{{ $filters['q'] ?? '' }}"
                    placeholder="Search employee or type…"
                    class="w-full rounded-[0.5rem] border border-slate-200 bg-white pl-8 pr-3 py-2 text-sm text-slate-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-[#1a56db]/30">
            </div>

            {{-- Status + Type: 2-col on mobile, inline on desktop (sm:contents) --}}
            <div class="grid grid-cols-2 gap-2 sm:contents">
                <select name="status" id="filterStatus"
                    class="w-full sm:w-auto rounded-[0.5rem] border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-[#1a56db]/30">
                    <option value="">All Statuses</option>
                    <option value="pending"  {{ ($filters['status'] ?? '') === 'pending'  ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ ($filters['status'] ?? '') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="cancelled" {{ ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>

                <select name="type" id="filterType"
                    class="w-full sm:w-auto rounded-[0.5rem] border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-[#1a56db]/30">
                    <option value="">All Types</option>
                    @foreach($leaveTypes as $lt)
                        <option value="{{ $lt->id }}" {{ ($filters['type'] ?? '') == $lt->id ? 'selected' : '' }}>{{ $lt->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- From + To: 2-col on mobile, inline on desktop (sm:contents) --}}
            <div class="grid grid-cols-2 gap-2 sm:contents">
                <div class="flex items-center gap-1.5 sm:w-auto">
                    <label class="shrink-0 text-xs font-medium text-slate-500">From</label>
                    <input type="date" name="start_date" id="filterStartDate" value="{{ $filters['start_date'] ?? '' }}"
                        class="flex-1 min-w-0 sm:w-32 rounded-[0.5rem] border border-slate-200 bg-white px-2 py-1.5 text-sm text-slate-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-[#1a56db]/30">
                </div>
                <div class="flex items-center gap-1.5 sm:w-auto">
                    <label class="shrink-0 text-xs font-medium text-slate-500">To</label>
                    <input type="date" name="end_date" id="filterEndDate" value="{{ $filters['end_date'] ?? '' }}"
                        class="flex-1 min-w-0 sm:w-32 rounded-[0.5rem] border border-slate-200 bg-white px-2 py-1.5 text-sm text-slate-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-[#1a56db]/30">
                </div>
            </div>

            {{-- Clear (conditional) --}}
            @if(($filters['q'] ?? '') || ($filters['status'] ?? '') || ($filters['type'] ?? '') || ($filters['start_date'] ?? '') || ($filters['end_date'] ?? ''))
            <a href="{{ route('leave.index') }}"
                class="inline-flex items-center justify-center rounded-[0.5rem] border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
                Clear
            </a>
            @endif
        </div>
    </form>

    {{-- Leave Requests Table --}}
    <div class="rounded-[0.8rem] border border-slate-200 bg-white shadow-[0_1px_2px_rgba(0,0,0,0.03)] overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-[0.75rem] font-bold text-slate-500 border-b border-slate-100">
                <tr>
                    <th class="px-5 py-4 font-bold">Employee</th>
                    <th class="px-5 py-4 font-bold">Type</th>
                    <th class="px-5 py-4 font-bold">From</th>
                    <th class="px-5 py-4 font-bold">To</th>
                    <th class="px-5 py-4 font-bold">Days</th>
                    <th class="pl-5 pr-2 py-4 font-bold">Status</th>
                    <th class="pl-2 pr-5 py-4 font-bold text-center">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($leaveRequests as $req)
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-5 py-4">
                            <p class="font-bold text-[#06112e]">{{ $req['employee'] }}</p>
                            @if($req['reason'])
                                <p class="text-xs text-slate-400 mt-0.5 truncate max-w-[200px]" title="{{ $req['reason'] }}">{{ $req['reason'] }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-slate-600">{{ $req['type'] }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $req['from'] }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $req['to'] }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $req['days'] == floor($req['days']) ? (int)$req['days'] : $req['days'] }}</td>
                        <td class="pl-5 pr-2 py-4">
                            @php
                                $statusClass = match($req['status']) {
                                    'Approved'  => 'bg-[#dcfce7] text-[#166534]',
                                    'Rejected'  => 'bg-[#fee2e2] text-[#991b1b]',
                                    'Cancelled' => 'bg-slate-100 text-slate-700 border border-slate-200',
                                    default     => 'bg-[#fef3c7] text-[#92400e]',
                                };
                            @endphp
                            <span class="rounded-full px-2.5 py-1 text-[0.7rem] font-bold {{ $statusClass }}">
                                {{ $req['status'] }}
                            </span>
                        </td>
                        <td class="pl-2 pr-5 py-4 text-center">
                            @if($req['status_code'] === 1 && auth()->user()->hasPermission('leaves.edit'))
                                <div class="flex items-center justify-center gap-2">
                                    {{-- Approve --}}
                                    <form method="POST" action="{{ route('leave.approve', $req['id']) }}">
                                        @csrf
                                        <button type="submit" title="Approve" aria-label="Approve"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 shadow-sm transition hover:bg-emerald-100">
                                            <i class="ti ti-check text-lg"></i>
                                        </button>
                                    </form>
                                    {{-- Decline --}}
                                    <button type="button" title="Decline" aria-label="Decline"
                                        class="open-decline-modal inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-700 shadow-sm transition hover:bg-rose-100"
                                        data-id="{{ $req['id'] }}"
                                        data-employee="{{ $req['employee'] }}">
                                        <i class="ti ti-x text-lg"></i>
                                    </button>
                                </div>
                            @elseif($req['status_code'] === 2 && auth()->user()->hasPermission('leaves.edit'))
                                {{-- Cancel Approved --}}
                                <div class="flex items-center justify-center">
                                    <button type="button"
                                        class="open-cancel-modal inline-flex h-8 w-8 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-600 shadow-sm transition hover:bg-rose-100 hover:text-rose-700 hover:scale-[1.05] active:scale-[0.95]"
                                        data-id="{{ $req['id'] }}"
                                        data-employee="{{ $req['employee'] }}"
                                        title="Cancel Leave Request">
                                        <i class="ti ti-ban text-base"></i>
                                    </button>
                                </div>
                            @elseif(($req['status_code'] === 3 || $req['status_code'] === 4) && $req['rejection_note'])
                                <span class="text-xs text-slate-400 italic" title="{{ $req['rejection_note'] }}">
                                    Note: {{ Str::limit($req['rejection_note'], 30) }}
                                </span>
                            @else
                                <span class="text-xs text-slate-300">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-12 text-center text-slate-400">
                            <i class="ti ti-calendar-off text-3xl block mb-2"></i>
                            No leave requests found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>{{-- /overflow-x-auto --}}
        @if ($leaveRequestsPaginated->hasPages())
            <div class="bg-white px-6 py-4 border-t border-slate-200">
                {{ $leaveRequestsPaginated->links() }}
            </div>
        @endif
    </div>

    {{-- =========================================================
         REQUEST LEAVE MODAL
    ========================================================= --}}
    @if(auth()->user()->hasPermission('leaves.create'))
    <div id="requestLeaveModal"
        class="fixed inset-0 z-50 hidden bg-slate-950/40 p-4 backdrop-blur-sm overflow-y-auto justify-center items-start sm:items-center">
        <div class="my-auto w-full max-w-lg rounded-2xl border border-slate-200 bg-white shadow-2xl max-h-[calc(100vh-2rem)] sm:max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Request Leave</h3>
                    <p class="mt-0.5 text-xs text-slate-500">Fill in the leave details below.</p>
                </div>
                <button type="button" id="closeRequestLeaveModal"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50">
                    <i class="ti ti-x text-sm"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('leave.store') }}" class="px-6 py-5 space-y-4">
                @csrf

                {{-- Employee selector (HR/SV only) --}}
                @if(auth()->user()->role !== 1 && $employees->isNotEmpty())
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Employee</label>
                    <select name="employee_id" id="leave_employee_id" required
                        class="w-full rounded-[0.5rem] border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#1a56db]/30">
                        <option value="">— Select Employee —</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                @else
                    <input type="hidden" name="employee_id" value="{{ auth()->user()->employee_id }}">
                @endif

                {{-- Leave Type --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Leave Type</label>
                    <select name="leave_type_id" id="leave_type_id" required
                        class="w-full rounded-[0.5rem] border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#1a56db]/30">
                        <option value="">— Select Type —</option>
                        @foreach($leaveTypes as $lt)
                            <option value="{{ $lt->id }}">{{ $lt->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Date Range --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">From</label>
                        <input type="date" name="start_date" id="leave_start_date" required
                            min="{{ now()->toDateString() }}"
                            class="w-full rounded-[0.5rem] border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#1a56db]/30">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">To</label>
                        <input type="date" name="end_date" id="leave_end_date" required
                            min="{{ now()->toDateString() }}"
                            class="w-full rounded-[0.5rem] border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#1a56db]/30">
                    </div>
                </div>

                {{-- Days preview --}}
                <div id="days_preview" class="hidden rounded-lg bg-blue-50 border border-blue-100 px-4 py-2.5 text-sm text-blue-700 font-medium">
                    <i class="ti ti-calendar-stats mr-1"></i>
                    <span id="days_preview_text"></span>
                </div>

                {{-- Reason --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Reason <span class="font-normal text-slate-400">(optional)</span></label>
                    <textarea name="reason" rows="3" placeholder="Briefly describe the reason…"
                        class="w-full rounded-[0.5rem] border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 resize-none focus:outline-none focus:ring-2 focus:ring-[#1a56db]/30"></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-1">
                    <button type="button" id="cancelRequestLeave"
                        class="rounded-[0.5rem] border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit"
                        class="rounded-[0.5rem] bg-[#1a56db] px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1e40af]">
                        Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- =========================================================
         DECLINE MODAL
    ========================================================= --}}
    @if(auth()->user()->hasPermission('leaves.edit'))
    <div id="declineModal"
        class="fixed inset-0 z-50 hidden bg-slate-950/40 p-4 backdrop-blur-sm overflow-y-auto justify-center items-start sm:items-center">
        <div class="my-auto w-full max-w-md rounded-2xl border border-slate-200 bg-white shadow-2xl max-h-[calc(100vh-2rem)] sm:max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Decline Leave Request</h3>
                    <p class="mt-0.5 text-xs text-slate-500" id="decline_employee_label">Provide an optional reason.</p>
                </div>
                <button type="button" id="closeDeclineModal"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50">
                    <i class="ti ti-x text-sm"></i>
                </button>
            </div>
            <form id="declineForm" method="POST" action="" class="px-6 py-5 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Rejection Note <span class="font-normal text-slate-400">(optional)</span></label>
                    <textarea name="rejection_note" rows="3" placeholder="e.g. Insufficient leave balance…"
                        class="w-full rounded-[0.5rem] border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 resize-none focus:outline-none focus:ring-2 focus:ring-rose-300"></textarea>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" id="cancelDecline"
                        class="rounded-[0.5rem] border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit"
                        class="rounded-[0.5rem] bg-rose-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700">
                        Decline Request
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- =========================================================
         CANCEL MODAL
    ========================================================= --}}
    @if(auth()->user()->hasPermission('leaves.edit'))
    <div id="cancelModal"
        class="fixed inset-0 z-50 hidden bg-slate-950/40 p-4 backdrop-blur-sm overflow-y-auto justify-center items-start sm:items-center">
        <div class="my-auto w-full max-w-md rounded-2xl border border-slate-200 bg-white shadow-2xl max-h-[calc(100vh-2rem)] sm:max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Cancel Approved Leave</h3>
                    <p class="mt-0.5 text-xs text-slate-500" id="cancel_employee_label">Provide a reason for cancellation.</p>
                </div>
                <button type="button" id="closeCancelModal"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50">
                    <i class="ti ti-x text-sm"></i>
                </button>
            </div>
            <form id="cancelForm" method="POST" action="" class="px-6 py-5 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Reason for Cancellation</label>
                    <textarea name="cancellation_reason" rows="3" required placeholder="e.g. Schedule conflict, client request…"
                        class="w-full rounded-[0.5rem] border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 resize-none focus:outline-none focus:ring-2 focus:ring-rose-300"></textarea>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" id="cancelBtnDismiss"
                        class="rounded-[0.5rem] border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        Close
                    </button>
                    <button type="submit"
                        class="rounded-[0.5rem] bg-rose-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700">
                        Confirm Cancellation
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <x-slot:scripts>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // ── Request Leave Modal ──
        const openBtn   = document.getElementById('openRequestLeaveModal');
        const closeBtn  = document.getElementById('closeRequestLeaveModal');
        const cancelBtn = document.getElementById('cancelRequestLeave');
        const modal     = document.getElementById('requestLeaveModal');

        function openModal()  { modal?.classList.remove('hidden'); modal?.classList.add('flex'); }
        function closeModal() { modal?.classList.remove('flex');   modal?.classList.add('hidden'); }

        openBtn?.addEventListener('click', openModal);
        closeBtn?.addEventListener('click', closeModal);
        cancelBtn?.addEventListener('click', closeModal);
        modal?.addEventListener('click', e => { if (e.target === modal) closeModal(); });

        // ── Days Preview ──
        const startInput   = document.getElementById('leave_start_date');
        const endInput     = document.getElementById('leave_end_date');
        const daysPreview  = document.getElementById('days_preview');
        const daysText     = document.getElementById('days_preview_text');

        function updateDays() {
            const start = startInput?.value;
            const end   = endInput?.value;
            if (start && end && end >= start) {
                const diff = Math.round((new Date(end) - new Date(start)) / 86400000) + 1;
                daysText.textContent  = diff + ' day' + (diff !== 1 ? 's' : '') + ' requested';
                daysPreview.classList.remove('hidden');
                // Keep end_date min in sync
                endInput.min = start;
            } else {
                daysPreview.classList.add('hidden');
            }
        }

        startInput?.addEventListener('change', function () {
            if (endInput && endInput.value && endInput.value < this.value) {
                endInput.value = this.value;
            }
            if (endInput) endInput.min = this.value;
            updateDays();
        });
        endInput?.addEventListener('change', updateDays);

        // ── Decline Modal ──
        const declineModal  = document.getElementById('declineModal');
        const declineForm   = document.getElementById('declineForm');
        const closeDecline  = document.getElementById('closeDeclineModal');
        const cancelDecline = document.getElementById('cancelDecline');
        const declineLabel  = document.getElementById('decline_employee_label');

        function openDecline()  { declineModal?.classList.remove('hidden'); declineModal?.classList.add('flex'); }
        function closeDeclineM(){ declineModal?.classList.remove('flex');   declineModal?.classList.add('hidden'); }

        document.querySelectorAll('.open-decline-modal').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const leaveId  = this.dataset.id;
                const employee = this.dataset.employee;
                if (declineForm) {
                    declineForm.action = '/leave/' + leaveId + '/decline';
                }
                if (declineLabel) {
                    declineLabel.textContent = 'Declining leave for ' + employee + '.';
                }
                openDecline();
            });
        });

        closeDecline?.addEventListener('click', closeDeclineM);
        cancelDecline?.addEventListener('click', closeDeclineM);
        declineModal?.addEventListener('click', e => { if (e.target === declineModal) closeDeclineM(); });

        // ── Cancel Modal ──
        const cancelModal  = document.getElementById('cancelModal');
        const cancelForm   = document.getElementById('cancelForm');
        const closeCancel  = document.getElementById('closeCancelModal');
        const cancelBtnDismiss = document.getElementById('cancelBtnDismiss');
        const cancelLabel  = document.getElementById('cancel_employee_label');

        function openCancel()  { cancelModal?.classList.remove('hidden'); cancelModal?.classList.add('flex'); }
        function closeCancelM(){ cancelModal?.classList.remove('flex');   cancelModal?.classList.add('hidden'); }

        document.querySelectorAll('.open-cancel-modal').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const leaveId  = this.dataset.id;
                const employee = this.dataset.employee;
                if (cancelForm) {
                    cancelForm.action = '/leave/' + leaveId + '/cancel';
                }
                if (cancelLabel) {
                    cancelLabel.textContent = 'Cancelling approved leave for ' + employee + '.';
                }
                openCancel();
            });
        });

        closeCancel?.addEventListener('click', closeCancelM);
        cancelBtnDismiss?.addEventListener('click', closeCancelM);
        cancelModal?.addEventListener('click', e => { if (e.target === cancelModal) closeCancelM(); });

        // ── ESC to close any modal ──
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            closeModal();
            closeDeclineM();
            closeCancelM();
        });

        // ── Auto-submit filters + keep Export URL in sync ──
        const filterForm    = document.getElementById('filterForm');
        const filterSearch  = document.getElementById('filterSearch');
        const filterStatus  = document.getElementById('filterStatus');
        const filterType    = document.getElementById('filterType');
        const filterStart   = document.getElementById('filterStartDate');
        const filterEnd     = document.getElementById('filterEndDate');
        const exportBtn     = document.getElementById('btnExport');

        function buildExportUrl() {
            const q         = filterSearch?.value || '';
            const status    = filterStatus?.value || '';
            const type      = filterType?.value || '';
            const startDate = filterStart?.value || '';
            const endDate   = filterEnd?.value || '';
            let url = "{{ route('leave.export') }}";
            const params = [];
            if (q)         params.push(`q=${encodeURIComponent(q)}`);
            if (status)    params.push(`status=${encodeURIComponent(status)}`);
            if (type)      params.push(`type=${encodeURIComponent(type)}`);
            if (startDate) params.push(`start_date=${encodeURIComponent(startDate)}`);
            if (endDate)   params.push(`end_date=${encodeURIComponent(endDate)}`);
            if (params.length) url += `?${params.join('&')}`;
            if (exportBtn) exportBtn.href = url;
        }

        // Selects & dates → submit immediately
        [filterStatus, filterType, filterStart, filterEnd].forEach(el => {
            el?.addEventListener('change', () => { buildExportUrl(); filterForm?.submit(); });
        });

        // Search → debounce 500 ms
        let searchTimer;
        filterSearch?.addEventListener('input', () => {
            buildExportUrl();
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => filterForm?.submit(), 500);
        });

        // Sync export URL on load
        buildExportUrl();
    });
    </script>
    </x-slot:scripts>
</x-app-layout>
