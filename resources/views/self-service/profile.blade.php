<x-app-layout>
    <x-slot:title>Employee Profile</x-slot:title>
    <x-slot:header>Employee Profile</x-slot:header>

    @php
        $initials = collect([$employee->first_name, $employee->last_name])
            ->filter()
            ->map(fn($value) => strtoupper(substr($value, 0, 1)))
            ->join('');

        $badge = fn($status) => match($status) {
            'Pending' => 'badge-amber',
            'Approved', 'Released', 'Present', 'Completed' => 'badge-green',
            'Late' => 'badge-amber',
            'Rejected', 'Absent' => 'badge-red',
            'On Leave' => 'badge-gray',
            default => 'badge-gray',
        };

        $requestModalToOpen = null;
        $leaveRequestTarget = $canSubmitRequests ? 'leaveRequestModal' : '';
        $profileUpdateTarget = $canSubmitRequests ? 'profileUpdateModal' : '';
        $documentUploadTarget = $canSubmitRequests ? 'documentUploadModal' : '';

        if ($canSubmitRequests ?? false) {
            if (old('leave_type_id') !== null || old('start_date') !== null || old('end_date') !== null || old('reason') !== null) {
                $requestModalToOpen = 'leaveRequestModal';
            } elseif (
                old('first_name') !== null || old('last_name') !== null || old('middle_name') !== null ||
                old('email') !== null || old('phone') !== null || old('address_line1') !== null ||
                old('address_line2') !== null || old('city') !== null || old('province') !== null ||
                old('postal_code') !== null || old('country') !== null || old('notes') !== null
            ) {
                $requestModalToOpen = 'profileUpdateModal';
            } elseif (old('document_type') !== null || old('expiry_date') !== null || old('description') !== null) {
                $requestModalToOpen = 'documentUploadModal';
            }
        }
    @endphp

    <style>
        /* Hide scrollbar for Chrome, Safari and Opera */
        .scrollbar-none::-webkit-scrollbar {
            display: none;
        }
        /* Hide scrollbar for IE, Edge and Firefox */
        .scrollbar-none {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }
    </style>

    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('self-service') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Self-Service
        </a>
    </div>

    <div class="mb-4 rounded-lg bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="group relative">
                    <form id="profilePictureForm" method="POST" action="{{ route('self-service.profile-picture.store', $employee) }}" enctype="multipart/form-data" class="m-0 p-0">
                        @csrf
                        <input type="file" name="profile_picture" id="profilePictureInput" accept="image/*" class="sr-only">
                    </form>

                    <div class="flex h-14 w-14 items-center justify-center overflow-hidden rounded-full bg-blue-600 text-lg font-bold text-white">
                        @if ($employee->profile_picture)
                            <img id="profilePicturePreview" src="{{ asset('storage/' . $employee->profile_picture) }}" alt="profile" class="h-full w-full object-cover">
                        @else
                            <div id="profileInitials" class="h-full w-full flex items-center justify-center">{{ $initials }}</div>
                        @endif
                    </div>

                    @if($canSubmitRequests)
                        <button type="button" onclick="document.getElementById('profilePictureInput').click()" class="absolute inset-0 flex items-center justify-center rounded-full bg-black/40 opacity-0 transition group-hover:opacity-100">
                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </button>
                    @endif
                </div>
                <div>
                    <h1 class="text-lg font-semibold text-slate-900">{{ $employee->full_name_with_middle_name }}</h1>
                    <p class="text-xs text-slate-600">
                        {{ $employee->position?->title ?? 'No position assigned' }}
                        | {{ $employee->department?->name ?? 'No department assigned' }}
                        | {{ $employee->employee_code ?? 'No employee code' }}
                    </p>
                </div>
            </div>
            <div class="text-xs text-slate-500">
                <p>{{ $employee->email }}</p>
                <p>{{ $employee->phone ?: 'No phone number' }}</p>
            </div>
        </div>
    </div>

    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <button type="button" onclick="switchProfileTab('leaves')" class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white p-3 text-left shadow-sm transition hover:bg-slate-50" data-request-modal="{{ $leaveRequestTarget }}">
            <div class="flex items-center gap-2">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10m-3 5h6m2 5H7a2 2 0 01-2-2V7a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-900">Leave Requests</p>
                    <p class="text-xs text-slate-500">{{ $leaveRequests->count() }} record(s)</p>
                </div>
            </div>
            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">{{ $canSubmitRequests ? 'Submit' : 'View' }}</span>
        </button>

        <button type="button" onclick="switchProfileTab('updates')" class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white p-3 text-left shadow-sm transition hover:bg-slate-50" data-request-modal="{{ $profileUpdateTarget }}">
            <div class="flex items-center gap-2">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5h2m-1 0v14m7-7H5" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-900">Profile Updates</p>
                    <p class="text-xs text-slate-500">{{ $profileUpdateRequests->count() }} request(s)</p>
                </div>
            </div>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $canSubmitRequests ? 'Request' : 'View' }}</span>
        </button>

        <button type="button" onclick="switchProfileTab('payslips')" class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white p-3 text-left shadow-sm transition hover:bg-slate-50">
            <div class="flex items-center gap-2">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-900">Payslips</p>
                    <p class="text-xs text-slate-500">{{ $payslips->count() }} record(s)</p>
                </div>
            </div>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">View</span>
        </button>

        <button type="button" onclick="switchProfileTab('documents')" class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white p-3 text-left shadow-sm transition hover:bg-slate-50" data-request-modal="{{ $documentUploadTarget }}">
            <div class="flex items-center gap-2">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-900">Documents</p>
                    <p class="text-xs text-slate-500">{{ $documents->count() }} file(s)</p>
                </div>
            </div>
            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{{ $canSubmitRequests ? 'Upload' : 'View' }}</span>
        </button>
    </div>

    <div class="mb-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-lg bg-white p-4 shadow-sm lg:col-span-2">
            <h2 class="mb-4 text-base font-semibold text-slate-900">Profile Details</h2>
            <div class="grid grid-cols-1 gap-4 text-sm text-slate-600 sm:grid-cols-2">
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Manager</p>
                    <p class="mt-1 font-medium text-slate-900">{{ $employee->manager?->full_name ?? 'Not assigned' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Hire Date</p>
                    <p class="mt-1 font-medium text-slate-900">{{ $employee->hire_date?->format('M d, Y') ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Birth Date</p>
                    <p class="mt-1 font-medium text-slate-900">{{ $employee->birth_date?->format('M d, Y') ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Employment Type</p>
                    <p class="mt-1 font-medium text-slate-900">{{ match((int) $employee->employment_type) {1 => 'Full-time', 2 => 'Part-time', 3 => 'Contract', 4 => 'Temporary', default => 'N/A'} }}</p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs uppercase tracking-wide text-slate-400">Address</p>
                    <p class="mt-1 font-medium text-slate-900">
                        {{ collect([$employee->address_line1, $employee->address_line2, $employee->city, $employee->province, $employee->postal_code, $employee->country])->filter()->join(', ') ?: 'No address on file' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="rounded-lg bg-white p-4 shadow-sm">
            <h2 class="mb-4 text-base font-semibold text-slate-900">Account Summary</h2>
            <div class="space-y-3 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Linked User</span>
                    <span class="font-medium text-slate-900">{{ $employee->user?->username ?? 'None' }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Role</span>
                    <span class="font-medium text-slate-900">{{ match((int) ($employee->user?->role ?? 0)) {1 => 'Employee', 2 => 'Supervisor', 4 => 'HR', default => 'N/A'} }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Pending Leaves</span>
                    <span class="font-medium text-slate-900">{{ $leaveRequests->where('status', 'Pending')->count() }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Pending Profile Updates</span>
                    <span class="font-medium text-slate-900">{{ $profileUpdateRequests->where('status', 'Pending')->count() }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Latest Attendance</span>
                    <span class="font-medium text-slate-900">{{ $attendanceLogs->first()['status'] ?? 'N/A' }}</span>
                </div>
            </div>
        </div>
    </div>

    @if ($canSubmitRequests)
        <div id="leaveRequestModal" class="fixed inset-0 z-50 hidden bg-black/40 p-4 overflow-y-auto justify-center items-start sm:items-center" data-modal-backdrop="leaveRequestModal">
            <div class="my-auto w-full max-w-lg rounded-2xl border border-slate-200 bg-white shadow-2xl max-h-[calc(100vh-2rem)] sm:max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Request Leave</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Fill in the leave details below.</p>
                    </div>
                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50" data-close-modal="leaveRequestModal">
                        <i class="ti ti-x text-sm"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('self-service.leave-requests.store', $employee) }}" class="px-6 py-5 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Leave Type</label>
                        <select name="leave_type_id" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#1a56db]/30">
                            <option value="">- Select Type -</option>
                            @foreach ($leaveTypeChoices as $leaveType)
                                <option value="{{ $leaveType['id'] }}" @selected((string) old('leave_type_id') === (string) $leaveType['id'])>{{ $leaveType['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">From</label>
                            <input type="date" id="self_service_leave_start_date" name="start_date" min="{{ now()->toDateString() }}" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#1a56db]/30" value="{{ old('start_date') }}">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">To</label>
                            <input type="date" id="self_service_leave_end_date" name="end_date" min="{{ now()->toDateString() }}" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#1a56db]/30" value="{{ old('end_date') }}">
                        </div>
                    </div>

                    <div id="self_service_leave_days_preview" class="{{ old('start_date') && old('end_date') ? '' : 'hidden' }} rounded-lg border border-blue-100 bg-blue-50 px-4 py-2.5 text-sm font-medium text-blue-700">
                        <i class="ti ti-calendar-stats mr-1"></i>
                        <span id="self_service_leave_days_preview_text"></span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Reason <span class="font-normal text-slate-400">(optional)</span></label>
                        <textarea name="reason" rows="3" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 resize-none focus:outline-none focus:ring-2 focus:ring-[#1a56db]/30" placeholder="Briefly describe the reason...">{{ old('reason') }}</textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-1">
                        <button type="button" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50" data-close-modal="leaveRequestModal">Cancel</button>
                        <button type="submit" class="rounded-lg bg-[#1a56db] px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1e40af]">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="profileUpdateModal" class="fixed inset-0 z-50 hidden bg-black/40 p-4 overflow-y-auto justify-center items-start sm:items-center" data-modal-backdrop="profileUpdateModal">
            <div class="my-auto max-h-[calc(100vh-2rem)] sm:max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-5 shadow-2xl">
                <div class="mb-3 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Request Profile Update</h2>
                        <p class="text-sm text-slate-500">Submit the details you want HR to review and update.</p>
                    </div>
                    <button type="button" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" data-close-modal="profileUpdateModal">X</button>
                </div>
                <form method="POST" action="{{ route('self-service.profile-update-requests.store', $employee) }}" class="space-y-2.5">
                    @csrf
                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-3">
                        <input type="text" name="first_name" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="First name" value="{{ old('first_name') }}">
                        <input type="text" name="middle_name" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Middle name" value="{{ old('middle_name') }}">
                        <input type="text" name="last_name" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Last name" value="{{ old('last_name') }}">
                    </div>
                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                        <input type="email" name="email" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Email" value="{{ old('email') }}">
                        <input type="text" name="phone" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="09XXXXXXXXX" value="{{ old('phone') }}" inputmode="numeric" maxlength="11" pattern="^\d{11}$" title="Use 11 digits like 09XXXXXXXXX" data-phone-input="true">
                    </div>
                    <input type="text" name="address_line1" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Address line 1" value="{{ old('address_line1') }}">
                    <input type="text" name="address_line2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Address line 2" value="{{ old('address_line2') }}">
                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                        <input type="text" name="city" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="City" value="{{ old('city') }}">
                        <input type="text" name="province" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Province" value="{{ old('province') }}">
                    </div>
                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                        <input type="text" name="postal_code" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Postal code" value="{{ old('postal_code') }}">
                        <input type="text" name="country" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Country" value="{{ old('country') }}">
                    </div>
                    <textarea name="notes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Why is this update needed?">{{ old('notes') }}</textarea>
                    <div class="flex items-center justify-end gap-3">
                        <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50" data-close-modal="profileUpdateModal">Cancel</button>
                        <button type="submit" class="inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">Submit Profile Update</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="documentUploadModal" class="fixed inset-0 z-50 hidden bg-black/40 p-4 overflow-y-auto justify-center items-start sm:items-center" data-modal-backdrop="documentUploadModal">
            <div class="my-auto max-h-[calc(100vh-2rem)] sm:max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Upload Document</h2>
                        <p class="text-sm text-slate-500">Add a new document for HR review and recordkeeping.</p>
                    </div>
                    <button type="button" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" data-close-modal="documentUploadModal">X</button>
                </div>
                <form method="POST" action="{{ route('self-service.documents.store', $employee) }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Document Name</label>
                        <input type="text" name="display_name" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="e.g. My Passport, HMO Form" value="{{ old('display_name') }}">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Document Type</label>
                        <input type="text" name="document_type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Government ID, Contract, Tax Form" value="{{ old('document_type') }}">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">File</label>
                        <input type="file" name="document_file" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
                            class="w-full text-sm text-slate-600 border border-slate-300 rounded-lg cursor-pointer bg-white file:mr-3 file:rounded-md file:border-0 file:bg-emerald-50 file:text-emerald-700 file:px-3 file:py-2 file:text-xs file:font-medium hover:file:bg-emerald-100 transition focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Expiry Date</label>
                        <input type="date" name="expiry_date" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" value="{{ old('expiry_date') }}">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Description</label>
                        <textarea name="description" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Optional description">{{ old('description') }}</textarea>
                    </div>
                    <div class="flex items-center justify-end gap-3">
                        <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50" data-close-modal="documentUploadModal">Cancel</button>
                        <button type="submit" class="inline-flex rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700">Upload Document</button>
                    </div>
                </form>
            </div>
        </div>
    @endif    {{-- Tabs --}}
    <div class="mb-4">
        <div class="border-b border-slate-200">
            <div class="overflow-x-auto scrollbar-none">
                <nav class="-mb-px flex gap-6 min-w-max pb-px" aria-label="Tabs">
                    <button type="button" onclick="switchProfileTab('timelogs')" id="tab-timelogs" class="border-[#1a56db] text-[#1a56db] whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition">Time Logs</button>
                    <button type="button" onclick="switchProfileTab('leaves')" id="tab-leaves" class="border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition">Leave Requests</button>
                    <button type="button" onclick="switchProfileTab('updates')" id="tab-updates" class="border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition">Profile Updates</button>
                    <button type="button" onclick="switchProfileTab('payslips')" id="tab-payslips" class="border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition">Payslips</button>
                    <button type="button" onclick="switchProfileTab('documents')" id="tab-documents" class="border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition">Documents</button>
                </nav>
            </div>
        </div>
    </div>

    <div id="leave-requests" class="mb-4 rounded-lg bg-white p-4 shadow-sm hidden">
        <h2 class="mb-4 text-base font-semibold text-slate-900">Leave Requests</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Type</th>
                        <th class="px-3 py-2">From</th>
                        <th class="px-3 py-2">To</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2">Submitted</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($leaveRequests as $leave)
                        <tr>
                            <td class="px-3 py-2 text-slate-900">{{ $leave['type'] }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $leave['start_date'] }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $leave['end_date'] }}</td>
                            <td class="px-3 py-2"><span class="badge {{ $badge($leave['status']) }}">{{ $leave['status'] }}</span></td>
                            <td class="px-3 py-2 text-slate-500">{{ $leave['created_at'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-6 text-center text-slate-500">No leave requests found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="profile-update-requests" class="mb-4 rounded-lg bg-white p-4 shadow-sm hidden">
        <h2 class="mb-4 text-base font-semibold text-slate-900">Profile Update Requests</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Requested Fields</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2">Remarks</th>
                        <th class="px-3 py-2">Submitted</th>
                        @if (in_array((int) (auth()->user()?->role ?? 0), [3, 4], true))
                            <th class="px-3 py-2 text-center">Action</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($profileUpdateRequests as $profileRequest)
                        <tr>
                            <td class="px-3 py-2 text-slate-900">{{ $profileRequest['requested_fields'] ?: 'No fields listed' }}</td>
                            <td class="px-3 py-2"><span class="badge {{ $badge($profileRequest['status']) }}">{{ $profileRequest['status'] }}</span></td>
                            <td class="px-3 py-2 text-slate-600">{{ $profileRequest['remarks'] ?: 'No remarks' }}</td>
                            <td class="px-3 py-2 text-slate-500">{{ $profileRequest['created_at'] }}</td>
                            @if (in_array((int) (auth()->user()?->role ?? 0), [3, 4], true))
                                <td class="px-3 py-2">
                                    @if ($profileRequest['status'] === 'Pending')
                                        <div class="flex items-center justify-center gap-2">
                                            <form method="POST" action="{{ route('self-service.profile-update-requests.review', $profileRequest['id']) }}">
                                                @csrf
                                                <input type="hidden" name="decision" value="approve">
                                                <button type="submit" title="Approve" aria-label="Approve"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 shadow-sm transition hover:bg-emerald-100">
                                                    <i class="ti ti-check text-lg"></i>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('self-service.profile-update-requests.review', $profileRequest['id']) }}">
                                                @csrf
                                                <input type="hidden" name="decision" value="reject">
                                                <button type="submit" title="Reject" aria-label="Reject"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-700 shadow-sm transition hover:bg-rose-100">
                                                    <i class="ti ti-x text-lg"></i>
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400">Reviewed</span>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ in_array((int) (auth()->user()?->role ?? 0), [3, 4], true) ? 5 : 4 }}" class="px-3 py-6 text-center text-slate-500">No profile update requests found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="payslips" class="mb-4 rounded-lg bg-white p-4 shadow-sm hidden">
        <h2 class="mb-4 text-base font-semibold text-slate-900">Payslips</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Period</th>
                        <th class="px-3 py-2">Pay Date</th>
                        <th class="px-3 py-2">Gross</th>
                        <th class="px-3 py-2">Deductions</th>
                        <th class="px-3 py-2">Net</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($payslips as $payslip)
                        @php
                            $payslipBreakdown = [
                                'employee_name' => $employee->full_name_with_middle_name,
                                'base_salary' => $payslip['base_salary_value'],
                                'frequency' => $payslip['frequency_label'],
                                'gross' => $payslip['gross_pay_value'],
                                'deductions' => $payslip['total_deductions_value'],
                                'net' => $payslip['net_pay_value'],
                                'line_items' => $payslip['line_items'],
                            ];
                        @endphp
                        <tr>
                            <td class="px-3 py-2 text-slate-900">{{ $payslip['period'] }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $payslip['pay_date'] }}</td>
                            <td class="px-3 py-2 text-slate-600">PHP {{ $payslip['gross_pay'] }}</td>
                            <td class="px-3 py-2 text-slate-600">PHP {{ $payslip['total_deductions'] }}</td>
                            <td class="px-3 py-2 font-medium text-slate-900">PHP {{ $payslip['net_pay'] }}</td>
                            <td class="px-3 py-2"><span class="badge {{ $badge($payslip['status']) }}">{{ $payslip['status'] }}</span></td>
                            <td class="px-3 py-2 text-center">
                                <button type="button"
                                    class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                                    data-payslip='@json($payslipBreakdown)'
                                    onclick="viewPayslipBreakdown(this)">
                                    View
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-6 text-center text-slate-500">No payslips found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="payslipModal" class="fixed inset-0 z-50 hidden bg-black/40 p-4 overflow-y-auto justify-center items-start sm:items-center">
        <div class="my-auto flex max-h-[calc(100vh-2rem)] sm:max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-[0_25px_50px_-12px_rgba(0,0,0,0.45)] ring-4 ring-black/5">
            <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/50 px-6 py-4">
                <div class="flex items-center gap-6">
                    <div>
                        <h3 id="psModalTitle" class="text-lg font-bold text-[#06112e]">Payslip Breakdown</h3>
                        <p id="psModalSubtitle" class="mt-0.5 text-sm font-medium text-slate-600"></p>
                    </div>
                    <div class="hidden h-8 w-px bg-slate-200 sm:block"></div>
                    <div class="hidden sm:block">
                        <p class="text-[0.65rem] font-bold uppercase tracking-wider text-slate-400">Base Salary</p>
                        <p class="mt-0.5 text-sm font-semibold text-slate-700"><span id="psBaseSalary"></span> <span class="font-normal text-slate-400">/</span> <span id="psFrequency" class="text-slate-500"></span></p>
                    </div>
                </div>
                <button type="button" onclick="closePayslipModal()" class="text-slate-400 transition hover:text-slate-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-5">
                <div class="grid gap-6 sm:grid-cols-2">
                    <div class="rounded-lg border border-slate-200 bg-white p-4">
                        <h4 class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-500">Earnings</h4>
                        <div id="psEarningsList" class="space-y-2 text-sm text-slate-700"></div>
                        <div class="mt-3 flex items-center justify-between gap-4 border-t border-slate-100 pt-2 font-bold text-slate-900">
                            <span>Gross Pay</span>
                            <span id="psGrossPay" class="text-right tabular-nums"></span>
                        </div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-4">
                        <h4 class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-500">Deductions & Taxes</h4>
                        <div id="psDeductionsList" class="space-y-2 text-sm text-slate-700"></div>
                        <div class="mt-3 flex items-center justify-between gap-4 border-t border-slate-100 pt-2 font-bold text-red-600">
                            <span>Total Deductions</span>
                            <span id="psTotalDeductions" class="text-right tabular-nums"></span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-between rounded-lg border border-indigo-100 bg-indigo-50 p-4">
                    <span class="font-bold text-indigo-900">Net Take-Home Pay</span>
                    <span id="psNetPay" class="text-xl font-black text-indigo-700"></span>
                </div>
            </div>
            <div class="flex justify-end border-t border-slate-100 px-6 py-4">
                <button type="button" onclick="closePayslipModal()" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-[#06112e] shadow-sm transition hover:bg-slate-50">Close</button>
            </div>
        </div>
    </div>

    <div id="attendance-logs" class="mb-4 rounded-lg bg-white p-4 shadow-sm">
        <h2 class="mb-4 text-base font-semibold text-slate-900">Recent Time Logs</h2>
        <div class="space-y-2">
            @forelse ($attendanceLogs as $attendance)
                <div class="rounded-lg border border-slate-200 p-3">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-slate-900">{{ $attendance['date'] }}</p>
                            <p class="text-xs text-slate-500">IN {{ $attendance['check_in'] }} | OUT {{ $attendance['check_out'] }}</p>
                        </div>
                        <span class="badge {{ $badge($attendance['status']) }}">{{ $attendance['status'] }}</span>
                    </div>
                    @if ($attendance['notes'])
                        <p class="mt-2 text-xs text-slate-500">{{ $attendance['notes'] }}</p>
                    @endif
                </div>
            @empty
                <p class="text-sm text-slate-500">No attendance logs found.</p>
            @endforelse
        </div>
    </div>

    <div id="documents" class="mb-4 rounded-lg bg-white p-4 shadow-sm hidden">
        <h2 class="mb-4 text-base font-semibold text-slate-900">My Documents</h2>
        <div class="space-y-2">
            @forelse ($documents as $document)
                <div class="flex items-center justify-between rounded-lg border border-slate-200 p-3">
                    <div class="flex items-center gap-2">
                        <svg class="h-6 w-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-slate-900">{{ $document['name'] }}</p>
                            <p class="text-xs text-slate-500">{{ $document['type'] }} | {{ $document['date'] ?? 'N/A' }}{{ $document['file_size'] ? ' | ' . $document['file_size'] : '' }}</p>
                        </div>
                    </div>
                    @if ($document['file_path'])
                        <span class="text-xs font-medium text-slate-500">Stored</span>
                    @else
                        <span class="text-xs font-medium text-slate-400">No file path</span>
                    @endif
                </div>
            @empty
                <p class="text-sm text-slate-500">No documents found.</p>
            @endforelse
        </div>
    </div>

    <script>
        document.getElementById('profilePictureInput')?.addEventListener('change', function (event) {
            const file = event.target.files && event.target.files[0];
            if (!file) return;

            // Preview
            const url = URL.createObjectURL(file);
            const preview = document.getElementById('profilePicturePreview');
            const initials = document.getElementById('profileInitials');
            if (preview) {
                preview.src = url;
            } else if (initials) {
                initials.outerHTML = `<img id="profilePicturePreview" src="${url}" alt="profile" class="h-full w-full object-cover">`;
            }

            // Submit form
            const form = document.getElementById('profilePictureForm');
            if (form) form.submit();
        });

        function openRequestModal(modalId) {
            const modal = document.getElementById(modalId);

            if (!modal) {
                return;
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeRequestModal(modalId) {
            const modal = document.getElementById(modalId);

            if (!modal) {
                return;
            }

            modal.classList.remove('flex');
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        function closeRequestModalOnBackdrop(event, modalId) {
            if (event.target.id !== modalId) {
                return;
            }

            closeRequestModal(modalId);
        }

        document.querySelectorAll('[data-request-modal]').forEach((button) => {
            button.addEventListener('click', () => {
                const modalId = button.dataset.requestModal;

                if (!modalId) {
                    return;
                }

                openRequestModal(modalId);
            });
        });

        document.querySelectorAll('[data-close-modal]').forEach((button) => {
            button.addEventListener('click', () => {
                const modalId = button.dataset.closeModal;

                if (!modalId) {
                    return;
                }

                closeRequestModal(modalId);
            });
        });

        document.querySelectorAll('[data-modal-backdrop]').forEach((modal) => {
            modal.addEventListener('click', (event) => {
                closeRequestModalOnBackdrop(event, modal.dataset.modalBackdrop);
            });
        });

        document.querySelectorAll('[data-phone-input="true"]').forEach((field) => {
            field.addEventListener('input', () => {
                field.value = field.value.replace(/\D/g, '').slice(0, 11);
            });
        });

        const leaveStartInput = document.getElementById('self_service_leave_start_date');
        const leaveEndInput = document.getElementById('self_service_leave_end_date');
        const leaveDaysPreview = document.getElementById('self_service_leave_days_preview');
        const leaveDaysPreviewText = document.getElementById('self_service_leave_days_preview_text');

        function updateSelfServiceLeaveDays() {
            const start = leaveStartInput?.value;
            const end = leaveEndInput?.value;

            if (start && end && end >= start) {
                const diff = Math.round((new Date(end) - new Date(start)) / 86400000) + 1;
                leaveDaysPreviewText.textContent = diff + ' day' + (diff !== 1 ? 's' : '') + ' requested';
                leaveDaysPreview?.classList.remove('hidden');

                if (leaveEndInput) {
                    leaveEndInput.min = start;
                }

                return;
            }

            leaveDaysPreview?.classList.add('hidden');
        }

        leaveStartInput?.addEventListener('change', function () {
            if (leaveEndInput && leaveEndInput.value && leaveEndInput.value < this.value) {
                leaveEndInput.value = this.value;
            }

            if (leaveEndInput) {
                leaveEndInput.min = this.value;
            }

            updateSelfServiceLeaveDays();
        });

        leaveEndInput?.addEventListener('change', updateSelfServiceLeaveDays);
        updateSelfServiceLeaveDays();

        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function formatCurrency(value, negative = false) {
            const amount = Math.abs(parseFloat(value || 0)).toLocaleString('en-US', { minimumFractionDigits: 2 });
            return `${negative ? '-' : ''}PHP ${amount}`;
        }

        function renderEarningsLineItems(lineItems) {
            const groups        = [];
            const standaloneItems = [];
            const GROUP_PREFIXES = ['attendance', 'previous claim', 'disputes'];

            lineItems.forEach((item) => {
                const description = item.description || 'Item';
                const colonIdx    = description.indexOf(':');

                if (colonIdx > 0) {
                    const prefix = description.slice(0, colonIdx).trim().toLowerCase();
                    if (GROUP_PREFIXES.includes(prefix)) {
                        const groupName = description.slice(0, colonIdx).trim();
                        const itemName  = description.slice(colonIdx + 1).trim();
                        let group = groups.find((g) => g.name.toLowerCase() === groupName.toLowerCase());
                        if (!group) { group = { name: groupName, items: [] }; groups.push(group); }
                        group.items.push({ ...item, label: itemName || description });
                        return;
                    }
                }
                standaloneItems.push({ ...item, label: description });
            });

            const renderItem = (item, itemClass = 'flex justify-between pl-4') => {
                return `<div class="${itemClass}"><span>${escapeHtml(item.label)}</span><span class="text-slate-700">${formatCurrency(item.amount)}</span></div>`;
            };

            let html = '';
            const baseSalaryIndex = standaloneItems.findIndex((item) => String(item.description || '').toLowerCase() === 'base salary');
            if (baseSalaryIndex !== -1) {
                html += renderItem(standaloneItems.splice(baseSalaryIndex, 1)[0], 'flex justify-between');
            }

            groups.forEach((group) => {
                html += `<div class="space-y-1 pt-1">`;
                html += `<div class="text-[0.75rem] font-semibold uppercase tracking-wide text-slate-500">${escapeHtml(group.name)}:</div>`;
                group.items.forEach((item) => { html += renderItem(item); });
                html += `</div>`;
            });
            standaloneItems.forEach((item) => { html += renderItem(item); });
            return html;
        }

        function renderDeductionLineItems(lineItems) {
            const groups = [];

            const getGroupName = (item) => {
                if (item.component_type === 3) {
                    return 'Taxes';
                }

                if (item.component_type === 4) {
                    return 'Government Contributions';
                }

                if (String(item.description || '').toLowerCase().startsWith('attendance:')) {
                    return 'Attendance';
                }

                return 'Deductions';
            };

            lineItems.forEach((item) => {
                const groupName = getGroupName(item);
                let group = groups.find((entry) => entry.name === groupName);

                if (!group) {
                    group = { name: groupName, items: [] };
                    groups.push(group);
                }

                const description = item.description || 'Item';
                let label = description;

                if (description.toLowerCase().startsWith('attendance:')) {
                    label = description.split(':').slice(1).join(':').trim() || description;
                }

                group.items.push({ ...item, label });
            });

            let html = '';

            groups.forEach((group) => {
                html += `<div class="space-y-1 pt-1">`;
                html += `<div class="text-[0.75rem] font-semibold uppercase tracking-wide text-slate-500">${escapeHtml(group.name)}:</div>`;
                group.items.forEach((item) => {
                    html += `<div class="flex justify-between pl-4"><span>${escapeHtml(item.label)}</span><span class="text-red-600">${formatCurrency(item.amount, true)}</span></div>`;
                });
                html += `</div>`;
            });

            return html;
        }

        function viewPayslipBreakdown(button) {
            const payload = JSON.parse(button.dataset.payslip || '{}');
            const lineItemsData = Array.isArray(payload.line_items) ? payload.line_items : [];

            document.getElementById('psModalSubtitle').textContent = payload.employee_name || 'Unknown employee';
            document.getElementById('psBaseSalary').textContent = formatCurrency(payload.base_salary || 0);
            document.getElementById('psFrequency').textContent = payload.frequency || 'Unknown';
            document.getElementById('psGrossPay').textContent = formatCurrency(payload.gross || 0);
            document.getElementById('psTotalDeductions').textContent = formatCurrency(payload.deductions || 0, true);
            document.getElementById('psNetPay').textContent = formatCurrency(payload.net || 0);

            const earningItems = lineItemsData.filter((item) => item.component_type === 1);
            const deductionItems = lineItemsData.filter((item) => item.component_type !== 1);

            document.getElementById('psEarningsList').innerHTML = renderEarningsLineItems(earningItems) || '<div class="italic text-slate-400">None</div>';
            document.getElementById('psDeductionsList').innerHTML = renderDeductionLineItems(deductionItems) || '<div class="italic text-slate-400">None</div>';

            document.getElementById('payslipModal').classList.remove('hidden');
            document.getElementById('payslipModal').classList.add('flex');
        }

        function closePayslipModal() {
            document.getElementById('payslipModal').classList.remove('flex');
            document.getElementById('payslipModal').classList.add('hidden');
        }

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            closePayslipModal();
            closeRequestModal('leaveRequestModal');
            closeRequestModal('profileUpdateModal');
            closeRequestModal('documentUploadModal');
        });

        const initialRequestModal = @json($requestModalToOpen);

        if (initialRequestModal) {
            openRequestModal(initialRequestModal);
        }

        // Switch profile tabs client-side
        function switchProfileTab(tabName) {
            const tabs = ['leaves', 'updates', 'payslips', 'timelogs', 'documents'];
            const elements = {
                leaves: document.getElementById('leave-requests'),
                updates: document.getElementById('profile-update-requests'),
                payslips: document.getElementById('payslips'),
                timelogs: document.getElementById('attendance-logs'),
                documents: document.getElementById('documents')
            };
            const buttons = {
                leaves: document.getElementById('tab-leaves'),
                updates: document.getElementById('tab-updates'),
                payslips: document.getElementById('tab-payslips'),
                timelogs: document.getElementById('tab-timelogs'),
                documents: document.getElementById('tab-documents')
            };

            tabs.forEach(tab => {
                const el = elements[tab];
                const btn = buttons[tab];
                if (tab === tabName) {
                    el?.classList.remove('hidden');
                    btn?.classList.add('border-[#1a56db]', 'text-[#1a56db]');
                    btn?.classList.remove('border-transparent', 'text-slate-500', 'hover:border-slate-300', 'hover:text-slate-700');
                } else {
                    el?.classList.add('hidden');
                    btn?.classList.remove('border-[#1a56db]', 'text-[#1a56db]');
                    btn?.classList.add('border-transparent', 'text-slate-500', 'hover:border-slate-300', 'hover:text-slate-700');
                }
            });

            // Update URL hash without scrolling the page
            if (history.pushState) {
                history.pushState(null, null, '#' + tabName);
            } else {
                location.hash = tabName;
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            let initialTab = 'timelogs';
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            const validTabs = ['leaves', 'updates', 'payslips', 'timelogs', 'documents'];

            if (tabParam && validTabs.includes(tabParam)) {
                initialTab = tabParam;
            } else {
                const hash = window.location.hash.replace('#', '');
                if (hash && validTabs.includes(hash)) {
                    initialTab = hash;
                } else if (hash === 'leave-requests') {
                    initialTab = 'leaves';
                } else if (hash === 'profile-update-requests') {
                    initialTab = 'updates';
                } else if (hash === 'payslips') {
                    initialTab = 'payslips';
                } else if (hash === 'attendance-logs' || hash === 'recent-time-logs') {
                    initialTab = 'timelogs';
                } else if (hash === 'documents') {
                    initialTab = 'documents';
                }
            }

            switchProfileTab(initialTab);
        });
    </script>
</x-app-layout>
