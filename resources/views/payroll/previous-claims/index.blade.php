<x-app-layout>
    <x-slot:title>{{ $pageTitle }}</x-slot:title>
    <x-slot:header>{{ $pageTitle }}</x-slot:header>

    @php $user = auth()->user(); $isHR = $user?->role === 4; @endphp

    {{-- Page Header --}}
    <div class="mb-6 flex items-center justify-between pb-6 border-b border-slate-200">
        <div>
            @if($isOvertimePage)
                <div class="flex items-center gap-2 mb-1">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">
                        <i class="ti ti-clock-bolt text-sm"></i> Overtime
                    </span>
                </div>
                <h1 class="text-3xl font-bold text-slate-900">Overtime Requests</h1>
                <p class="mt-1 text-sm text-slate-600">File and review overtime pay claims for periods that have already passed.</p>
            @elseif($isNightDifferentialPage)
                <div class="flex items-center gap-2 mb-1">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                        <i class="ti ti-moon text-sm"></i> Night Differential
                    </span>
                </div>
                <h1 class="text-3xl font-bold text-slate-900">Night Differential Requests</h1>
                <p class="mt-1 text-sm text-slate-600">File and review night differential pay claims for periods that have already passed.</p>
            @else
                <h1 class="text-3xl font-bold text-slate-900">Previous Claims</h1>
                <p class="mt-1 text-sm text-slate-600">File claims for pay periods that have already passed. HR reviews and includes approved claims in the next pay run.</p>
            @endif
        </div>
        <div class="flex items-center gap-2">
            @if($isHR)
            <a href="{{ route('payroll.previous-claims.export') }}" id="btnExport"
                class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2.5 rounded-lg transition border border-slate-200 flex items-center gap-2 font-medium text-sm">
                <i class="ti ti-download text-base"></i> Export CSV
            </a>
            @endif
            <button type="button" id="openClaimModal"
                class="{{ $isOvertimePage ? 'bg-amber-500 hover:bg-amber-600' : ($isNightDifferentialPage ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-blue-600 hover:bg-blue-700') }} text-white px-5 py-2.5 rounded-lg transition flex items-center gap-2 font-medium text-sm whitespace-nowrap">
                <i class="ti ti-plus text-base"></i>
                @if($isOvertimePage) File Overtime Request
                @elseif($isNightDifferentialPage) File Night Differential Request
                @else File a Claim
                @endif
            </button>
        </div>
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
                    <input type="text" id="filterSearch" placeholder="Employee, type, description…" value="{{ $filters['q'] ?? '' }}"
                        class="w-full rounded-lg border border-slate-200 pl-8 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div class="min-w-[140px]">
                <label class="block text-xs font-medium text-slate-600 mb-1">Status</label>
                <select id="filterStatus" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All</option>
                    <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ ($filters['status'] ?? '') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="declined" {{ ($filters['status'] ?? '') === 'declined' ? 'selected' : '' }}>Declined</option>
                </select>
            </div>
            @if($isOvertimePage || $isNightDifferentialPage)
                {{-- On dedicated pages the type is locked; show a read-only badge instead of the dropdown --}}
                <input type="hidden" id="filterType" value="{{ strtolower($filters['type'] ?? '') }}">
                <div class="min-w-[160px]">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Claim Type</label>
                    <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500 cursor-not-allowed">
                        <i class="ti ti-lock text-slate-400 text-xs"></i>
                        <span>{{ $isOvertimePage ? 'Overtime' : 'Night Differential' }}</span>
                    </div>
                </div>
            @else
                <div class="min-w-[160px]">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Claim Type</label>
                    <select id="filterType" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Types</option>
                        @foreach($claimTypes as $ct)
                            <option value="{{ strtolower($ct) }}" {{ ($filters['type'] ?? '') === strtolower($ct) ? 'selected' : '' }}>{{ $ct }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
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
            <table id="claimsTable" class="w-full text-sm">
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
                        data-date="{{ $claim->claim_date->toDateString() }}"
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
                            <div class="font-medium text-slate-800">{{ $claim->claim_date->format('M d, Y') }}</div>
                            @if($claim->start_time && $claim->end_time)
                                <div class="text-[0.7rem] text-slate-500 mt-0.5">
                                    {{ $claim->start_time->format('h:i A') }} – {{ $claim->end_time->format('h:i A') }}
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-900 whitespace-nowrap">
                            @if($claim->start_time && $claim->end_time && $claim->amount == 0)
                                @php
                                    $s = \Carbon\Carbon::parse($claim->start_time);
                                    $e = \Carbon\Carbon::parse($claim->end_time);
                                    if ($e->lt($s)) $e->addDay();
                                    $hrs = $s->diffInMinutes($e) / 60;
                                @endphp
                                <span class="text-slate-600 font-medium">{{ number_format($hrs, 2) }} hrs</span>
                            @else
                                ₱{{ number_format($claim->amount, 2) }}
                            @endif
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
                                        class="action-icon btn-approve text-emerald-600 hover:text-emerald-800 transition"
                                        title="Approve"
                                        data-id="{{ $claim->id }}"
                                        data-employee="{{ $claim->employee?->full_name }}"
                                        data-amount="₱{{ number_format($claim->amount, 2) }}">
                                        <i class="ti ti-check text-lg"></i>
                                    </button>
                                    <button type="button"
                                        class="action-icon btn-decline text-rose-500 hover:text-rose-700 transition"
                                        title="Decline"
                                        data-id="{{ $claim->id }}"
                                        data-employee="{{ $claim->employee?->full_name }}">
                                        <i class="ti ti-x text-lg"></i>
                                    </button>
                                @endif
                                @if($claim->supporting_document)
                                    <a href="{{ route('media.file', ['path' => ltrim($claim->supporting_document, '/')]) }}"
                                        target="_blank" class="action-icon text-slate-500 hover:text-slate-700 transition" title="View Document">
                                        <i class="ti ti-paperclip text-lg"></i>
                                    </a>
                                @endif
                                @if($claim->status === 1 && ($isHR || $claim->submitted_by === $user?->id))
                                    <form method="POST" action="{{ route('payroll.previous-claims.destroy', $claim) }}">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                            class="action-icon text-slate-400 hover:text-rose-600 transition"
                                            title="Delete"
                                            data-confirm="Delete this pending claim?"
                                            data-confirm-title="Delete Claim"
                                            data-confirm-type="danger"
                                            data-confirm-text="Delete">
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

        <x-table-pagination target="claimsTable" itemsPerPage="10" />
    </div>

    {{-- ===== FILE CLAIM MODAL ===== --}}
    <div id="claimModal" class="fixed inset-0 z-50 hidden justify-center items-start sm:items-center bg-slate-950/40 p-4 backdrop-blur-sm overflow-y-auto">
        <div class="my-auto w-full max-w-lg rounded-2xl border border-slate-200 bg-white shadow-xl max-h-[calc(100vh-2rem)] sm:max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <div>
                    @if($isOvertimePage)
                        <div class="flex items-center gap-2 mb-1">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">
                                <i class="ti ti-clock-bolt"></i> Overtime
                            </span>
                        </div>
                        <h3 class="text-base font-semibold text-slate-900">File an Overtime Request</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Submit an overtime pay claim for a period that has already passed.</p>
                    @elseif($isNightDifferentialPage)
                        <div class="flex items-center gap-2 mb-1">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-semibold text-indigo-700">
                                <i class="ti ti-moon"></i> Night Differential
                            </span>
                        </div>
                        <h3 class="text-base font-semibold text-slate-900">File a Night Differential Request</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Submit a night differential pay claim for a period that has already passed.</p>
                    @else
                        <h3 class="text-base font-semibold text-slate-900">File a Previous Claim</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Submit a claim for a pay period that has already passed.</p>
                    @endif
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
                        @if($isOvertimePage || $isNightDifferentialPage)
                            {{-- Lock the claim type on dedicated pages --}}
                            <input type="hidden" name="claim_type" value="{{ $isOvertimePage ? 'Overtime' : 'Night Differential' }}">
                            <div class="flex items-center gap-2 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600 cursor-not-allowed">
                                <i class="ti ti-lock text-slate-400 text-xs"></i>
                                <span class="font-medium {{ $isOvertimePage ? 'text-amber-700' : 'text-indigo-700' }}">
                                    {{ $isOvertimePage ? 'Overtime' : 'Night Differential' }}
                                </span>
                            </div>
                        @else
                            <select name="claim_type" id="claimTypeSelect" required
                                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select type…</option>
                                @foreach($claimTypes as $ct)
                                    <option value="{{ $ct }}">{{ $ct }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Claim Date <span class="text-rose-500">*</span></label>
                        <input type="date" name="claim_date" required max="{{ now()->subDay()->toDateString() }}"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div id="timeRangeContainer" class="grid grid-cols-2 gap-4 {{ ($isOvertimePage || $isNightDifferentialPage) ? '' : 'hidden' }}">
                    <div class="col-span-2 text-xs font-medium text-slate-500 hidden" id="hoursCalculatedText">
                        Total requested: <span class="font-bold text-slate-700" id="hoursValue">0</span> hours
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Start Time</label>
                        <input type="time" name="start_time" id="startTimeInput"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">End Time</label>
                        <input type="time" name="end_time" id="endTimeInput"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div id="amountContainer" class="{{ ($isOvertimePage || $isNightDifferentialPage) ? 'hidden' : '' }}">
                    <label class="block text-xs font-medium text-slate-700 mb-1">Amount (₱) <span class="text-rose-500">*</span></label>
                    <input type="number" name="amount" id="amountInput" {{ ($isOvertimePage || $isNightDifferentialPage) ? '' : 'required' }} min="0.01" step="0.01" placeholder="0.00"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Description</label>
                    <textarea name="description" rows="3" placeholder="Briefly describe the claim…"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Supporting Document <span class="text-slate-400">(PDF, image, Word — max 5MB)</span></label>
                    <label for="claimDocumentFileInput" id="claimDocumentDropZone"
                        class="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-slate-300 px-4 py-5 text-center transition hover:border-blue-400 hover:bg-blue-50/40">
                        <svg class="h-8 w-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        <p class="mt-1.5 text-sm text-slate-700">
                            <span class="font-medium text-blue-600">Click to upload</span> or drag and drop
                        </p>
                        <p class="text-xs text-slate-400">PDF, JPG, PNG, DOC, DOCX</p>
                    </label>
                    <input type="file" id="claimDocumentFileInput" name="supporting_document"
                        accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="sr-only">
                    {{-- Selected file pill --}}
                    <div id="claimDocumentFilePill" class="hidden mt-2 flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm">
                        <svg class="h-4 w-4 shrink-0 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                        </svg>
                        <span id="claimDocumentFileName" class="flex-1 truncate font-medium text-blue-700 text-xs"></span>
                        <button type="button" id="claimDocumentFileRemove"
                            class="ml-1 rounded p-0.5 text-blue-400 hover:bg-blue-100 hover:text-blue-700 transition" title="Remove file">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <p id="claimDocumentFileError" class="hidden mt-1.5 text-xs text-red-600 font-medium"></p>
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
    <div id="approveModal" class="fixed inset-0 z-50 hidden justify-center items-start sm:items-center bg-slate-950/40 p-4 backdrop-blur-sm overflow-y-auto">
        <div class="my-auto w-full max-w-md rounded-2xl border border-slate-200 bg-white shadow-xl max-h-[calc(100vh-2rem)] sm:max-h-[90vh] overflow-y-auto">
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
    <div id="declineModal" class="fixed inset-0 z-50 hidden justify-center items-start sm:items-center bg-slate-950/40 p-4 backdrop-blur-sm overflow-y-auto">
        <div class="my-auto w-full max-w-md rounded-2xl border border-slate-200 bg-white shadow-xl max-h-[calc(100vh-2rem)] sm:max-h-[90vh] overflow-y-auto">
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
        const previousClaimApproveUrlTemplate = @json(route('payroll.previous-claims.approve', ['previousClaim' => '__CLAIM__']));
        const previousClaimDeclineUrlTemplate = @json(route('payroll.previous-claims.decline', ['previousClaim' => '__CLAIM__']));

        function buildPreviousClaimActionUrl(template, claimId) {
            return template.replace('__CLAIM__', claimId);
        }

        // ===== LIVE FILTERING =====
        const searchInput  = document.getElementById('filterSearch');
        const statusSelect = document.getElementById('filterStatus');
        const typeSelect   = document.getElementById('filterType');
        const filterStartDate = document.getElementById('filterStartDate');
        const filterEndDate   = document.getElementById('filterEndDate');
        const clearBtn     = document.getElementById('clearFilters');
        const filterCount  = document.getElementById('filterCount');
        const rows         = document.querySelectorAll('.claim-row');
        const totalRows    = rows.length;

        // Dynamic empty-state row (injected when all rows are hidden)
        let noResultsRow = null;
        function getColspan() { return {{ $isHR ? 9 : 8 }}; }

        function applyFilters() {
            const q         = (searchInput?.value  || '').toLowerCase().trim();
            const status    = (statusSelect?.value || '').toLowerCase();
            const type      = (typeSelect?.value   || '').toLowerCase();
            const startDate = filterStartDate?.value || '';
            const endDate   = filterEndDate?.value || '';

            let visible = 0;

            rows.forEach(row => {
                const matchQ = !q ||
                    row.dataset.employee.includes(q) ||
                    row.dataset.type.includes(q) ||
                    row.dataset.description.includes(q);

                const matchStatus = !status || row.dataset.status === status;
                const matchType   = !type   || row.dataset.type === type;

                const rowDate = row.dataset.date;
                const matchDate = (!startDate || rowDate >= startDate) && (!endDate || rowDate <= endDate);

                const show = matchQ && matchStatus && matchType && matchDate;
                row.setAttribute('data-filter-hidden', show ? 'false' : 'true');
                row.style.display = show ? '' : 'none';
                if (show) visible++;
            });

            // Update export button URL dynamically
            const exportBtn = document.getElementById('btnExport');
            if (exportBtn) {
                let url = "{{ route('payroll.previous-claims.export') }}";
                const params = [];
                if (q) params.push(`q=${encodeURIComponent(q)}`);
                if (status) params.push(`status=${encodeURIComponent(status)}`);
                if (type) params.push(`type=${encodeURIComponent(type)}`);
                if (startDate) params.push(`start_date=${encodeURIComponent(startDate)}`);
                if (endDate) params.push(`end_date=${encodeURIComponent(endDate)}`);
                if (params.length > 0) {
                    url += `?${params.join('&')}`;
                }
                exportBtn.href = url;
            }

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
            const isFiltered = q || status || type || startDate || endDate;
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

        // Dropdowns and date inputs apply immediately
        statusSelect?.addEventListener('change', applyFilters);
        typeSelect?.addEventListener('change', applyFilters);
        filterStartDate?.addEventListener('change', applyFilters);
        filterEndDate?.addEventListener('change', applyFilters);

        // Clear all filters
        clearBtn?.addEventListener('click', () => {
            if (searchInput)     searchInput.value     = '';
            if (statusSelect)    statusSelect.value    = '';
            if (typeSelect)      typeSelect.value      = '';
            if (filterStartDate) filterStartDate.value = '';
            if (filterEndDate)   filterEndDate.value   = '';
            applyFilters();
        });

        // On dedicated pages (Overtime / Night Differential), auto-apply the locked type filter
        @if($isOvertimePage || $isNightDifferentialPage)
        const lockedTypeInput = document.getElementById('filterType');
        if (lockedTypeInput) {
            // The hidden input already has the value; we just need to trigger the filter
        }
        @endif

        // Apply filters once on DOM load to sync UI state
        applyFilters();

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

        // ===== Claim Document File Picker =====
        (() => {
            const fileInput  = document.getElementById('claimDocumentFileInput');
            const dropZone   = document.getElementById('claimDocumentDropZone');
            const filePill   = document.getElementById('claimDocumentFilePill');
            const fileName   = document.getElementById('claimDocumentFileName');
            const fileRemove = document.getElementById('claimDocumentFileRemove');
            const fileError  = document.getElementById('claimDocumentFileError');

            if (!fileInput) return;

            const ACCEPTED_EXTS = ['.pdf', '.jpg', '.jpeg', '.png', '.doc', '.docx'];

            const getExt = (name) => {
                const match = name.toLowerCase().match(/\.[^.]+$/);
                return match ? match[0] : '';
            };

            const showError = (msg) => {
                if (!fileError) return;
                fileError.textContent = msg;
                fileError.classList.remove('hidden');
            };

            const clearError = () => {
                if (!fileError) return;
                fileError.textContent = '';
                fileError.classList.add('hidden');
            };

            const showPill = (file) => {
                if (fileName) fileName.textContent = file.name;
                filePill?.classList.remove('hidden');
                filePill?.classList.add('flex');
                dropZone?.classList.add('border-blue-400', 'bg-blue-50/60');
            };

            const clearPill = () => {
                if (fileName) fileName.textContent = '';
                filePill?.classList.add('hidden');
                filePill?.classList.remove('flex');
                dropZone?.classList.remove('border-blue-400', 'bg-blue-50/60');
            };

            const applyFile = (file) => {
                clearError();
                const ext = getExt(file.name);
                if (!ACCEPTED_EXTS.includes(ext)) {
                    showError(`Invalid file type "${ext || file.name}". Accepted: ${ACCEPTED_EXTS.join(', ')}`);
                    fileInput.value = '';
                    clearPill();
                    return;
                }
                showPill(file);
            };

            if (dropZone) {
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach((ev) => {
                    dropZone.addEventListener(ev, (e) => { e.preventDefault(); e.stopPropagation(); });
                });
                ['dragenter', 'dragover'].forEach((ev) => {
                    dropZone.addEventListener(ev, () => dropZone.classList.add('border-blue-400', 'bg-blue-50'));
                });
                ['dragleave', 'drop'].forEach((ev) => {
                    dropZone.addEventListener(ev, () => {
                        if (!fileInput.files?.length) dropZone.classList.remove('border-blue-400', 'bg-blue-50');
                    });
                });
                dropZone.addEventListener('drop', (e) => {
                    const files = e.dataTransfer?.files;
                    if (!files || files.length === 0) return;
                    fileInput.files = files;
                    applyFile(files[0]);
                });
            }

            fileInput.addEventListener('change', () => {
                const file = fileInput.files && fileInput.files[0];
                if (file) applyFile(file);
                else { clearPill(); clearError(); }
            });

            fileRemove?.addEventListener('click', (e) => {
                e.preventDefault();
                fileInput.value = '';
                clearPill();
                clearError();
            });

            // Toggle time range inputs based on claim type
            const claimTypeSelect = document.getElementById('claimTypeSelect');
            const timeRangeContainer = document.getElementById('timeRangeContainer');
            const startTimeInput = document.getElementById('startTimeInput');
            const endTimeInput = document.getElementById('endTimeInput');
            const amountContainer = document.getElementById('amountContainer');
            const amountInput = document.getElementById('amountInput');
            const hoursCalculatedText = document.getElementById('hoursCalculatedText');
            const hoursValue = document.getElementById('hoursValue');

            function calculateHours() {
                if (startTimeInput.value && endTimeInput.value) {
                    const start = new Date(`2000-01-01T${startTimeInput.value}`);
                    let end = new Date(`2000-01-01T${endTimeInput.value}`);
                    if (end < start) end.setDate(end.getDate() + 1); // Crosses midnight
                    const diffHours = (end - start) / (1000 * 60 * 60);
                    hoursValue.textContent = diffHours.toFixed(2);
                    hoursCalculatedText.classList.remove('hidden');
                } else {
                    hoursCalculatedText.classList.add('hidden');
                    hoursValue.textContent = '0';
                }
            }

            if (startTimeInput && endTimeInput) {
                startTimeInput.addEventListener('change', calculateHours);
                endTimeInput.addEventListener('change', calculateHours);
            }

            if (claimTypeSelect && timeRangeContainer) {
                claimTypeSelect.addEventListener('change', (e) => {
                    const isTimeBased = e.target.value === 'Overtime' || e.target.value === 'Night Differential';
                    if (isTimeBased) {
                        timeRangeContainer.classList.remove('hidden');
                        amountContainer.classList.add('hidden');
                        if (startTimeInput) startTimeInput.required = true;
                        if (endTimeInput) endTimeInput.required = true;
                        if (amountInput) { amountInput.required = false; amountInput.value = ''; }
                    } else {
                        timeRangeContainer.classList.add('hidden');
                        amountContainer.classList.remove('hidden');
                        if (startTimeInput) { startTimeInput.required = false; startTimeInput.value = ''; }
                        if (endTimeInput) { endTimeInput.required = false; endTimeInput.value = ''; }
                        if (amountInput) amountInput.required = true;
                        hoursCalculatedText.classList.add('hidden');
                        hoursValue.textContent = '0';
                    }
                });
            }

            // Also clear the file picker when the modal is closed/re-opened
            [closeBtn, cancelBtn].forEach((btn) => {
                btn?.addEventListener('click', () => {
                    fileInput.value = '';
                    clearPill();
                    clearError();
                });
            });

            // AJAX Form Submission to retain form inputs & selected file on validation error
            const form = fileInput?.closest('form');
            form?.addEventListener('submit', async (e) => {
                e.preventDefault();

                const submitBtn = form.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn ? submitBtn.innerHTML : '';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = `
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Uploading...
                    `;
                }

                clearError();

                try {
                    const formData = new FormData(form);
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (response.ok) {
                        window.location.reload();
                    } else {
                        const data = await response.json();
                        const errMsg = data.message || (data.errors ? Object.values(data.errors).flat().join(', ') : 'Upload failed. Please check form values.');
                        showError(errMsg);
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalBtnText;
                        }
                    }
                } catch (error) {
                    showError('An unexpected network error occurred.');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;
                    }
                }
            });
        })();

        @if($isHR)
        const approveModal    = document.getElementById('approveModal');
        const approveForm     = document.getElementById('approveForm');
        const approveSubtitle = document.getElementById('approveSubtitle');

        document.querySelectorAll('.btn-approve').forEach(btn => {
            btn.addEventListener('click', function () {
                approveSubtitle.textContent = `${this.dataset.employee} — ${this.dataset.amount}`;
                approveForm.action = buildPreviousClaimActionUrl(previousClaimApproveUrlTemplate, this.dataset.id);
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
                declineForm.action = buildPreviousClaimActionUrl(previousClaimDeclineUrlTemplate, this.dataset.id);
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
