<x-app-layout>
    <x-slot:title>My Profile Settings</x-slot:title>
    <x-slot:header>My Profile Settings</x-slot:header>

    @php
        $initials = collect(preg_split('/\s+/', trim($user->name)))
            ->filter()
            ->take(2)
            ->map(fn($part) => strtoupper(mb_substr($part, 0, 1)))
            ->implode('');

        $badge = fn($status) => match($status) {
            'Pending', 'Draft' => 'badge-amber',
            'Approved', 'Released', 'Present', 'Completed', 'Stored' => 'badge-green',
            'Late' => 'badge-amber',
            'Rejected', 'Absent' => 'badge-red',
            'On Leave' => 'badge-gray',
            default => 'badge-gray',
        };

        // Employees (role 1) cannot self-edit; they must use Self-Service
        $isEmployee = auth()->user()?->role === 1;
    @endphp

    <div class="mx-auto max-w-5xl">
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- Header Profile card -->
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="h-32 bg-gradient-to-r from-blue-600 to-indigo-700"></div>
                <div class="px-6 pb-6">
                    <div class="relative -mt-16 mb-4 flex flex-col items-center sm:flex-row sm:items-end sm:gap-6">
                        <!-- Avatar selector -->
                        <div class="group relative h-32 w-32 shrink-0 rounded-full border-4 border-white bg-blue-600 text-white shadow-md overflow-hidden">
                            @if ($employee && $employee->profile_picture)
                                <img id="avatar-preview" src="{{ route('media.file', ['path' => ltrim($employee->profile_picture, '/')]) }}" alt="Profile Picture" class="h-full w-full object-cover">
                            @else
                                <div id="avatar-fallback" class="flex h-full w-full items-center justify-center text-3xl font-bold bg-indigo-600">
                                    {{ $initials }}
                                </div>
                                <img id="avatar-preview" src="" alt="Profile Picture" class="hidden h-full w-full object-cover">
                            @endif

                            @if ($employee && !$isEmployee)
                                <label for="profile_picture" class="absolute inset-0 flex cursor-pointer flex-col items-center justify-center bg-black/50 opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                                    <i class="ti ti-camera text-2xl text-white"></i>
                                    <span class="mt-1 text-xs font-semibold text-white">Change photo</span>
                                    <input type="file" id="profile_picture" name="profile_picture" accept="image/*" class="hidden">
                                </label>
                            @endif
                        </div>

                        <div class="mt-4 flex-1 text-center sm:mt-0 sm:text-left">
                            <h1 class="text-2xl font-bold text-slate-900">{{ $employee ? $employee->full_name_with_middle_name : $user->name }}</h1>
                            <p class="text-sm font-medium text-slate-500">
                                @if ($employee)
                                    {{ $employee->position?->title ?? 'No Position' }} • {{ $employee->department?->name ?? 'No Department' }}
                                @else
                                    System User
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Tabs -->
            <div class="bg-white p-2 rounded-2xl border border-slate-200 shadow-sm grid grid-cols-2 sm:flex sm:flex-wrap md:flex-nowrap sm:items-center gap-1.5 sm:gap-1">
                <button type="button" onclick="switchTab('profile-info')" id="tab-btn-profile-info" class="tab-btn w-full sm:w-auto inline-flex items-center justify-center sm:justify-start gap-2 px-3 py-2 sm:px-4 text-xs sm:text-sm font-semibold rounded-xl transition duration-150 bg-blue-50 text-blue-600 border border-blue-100" data-tab="profile-info">
                    <i class="ti ti-user text-lg"></i>
                    Profile Info
                </button>
                <button type="button" onclick="switchTab('leave')" id="tab-btn-leave" class="tab-btn w-full sm:w-auto inline-flex items-center justify-center sm:justify-start gap-2 px-3 py-2 sm:px-4 text-xs sm:text-sm font-medium rounded-xl transition duration-150 text-slate-600 hover:bg-slate-50 hover:text-slate-900 border border-transparent" data-tab="leave">
                    <i class="ti ti-calendar text-lg"></i>
                    Leave Requests
                </button>
                <button type="button" onclick="switchTab('payslips')" id="tab-btn-payslips" class="tab-btn w-full sm:w-auto inline-flex items-center justify-center sm:justify-start gap-2 px-3 py-2 sm:px-4 text-xs sm:text-sm font-medium rounded-xl transition duration-150 text-slate-600 hover:bg-slate-50 hover:text-slate-900 border border-transparent" data-tab="payslips">
                    <i class="ti ti-receipt text-lg"></i>
                    Payslips
                </button>
                <button type="button" onclick="switchTab('documents')" id="tab-btn-documents" class="tab-btn w-full sm:w-auto inline-flex items-center justify-center sm:justify-start gap-2 px-3 py-2 sm:px-4 text-xs sm:text-sm font-medium rounded-xl transition duration-150 text-slate-600 hover:bg-slate-50 hover:text-slate-900 border border-transparent" data-tab="documents">
                    <i class="ti ti-files text-lg"></i>
                    Documents
                </button>
                <button type="button" onclick="switchTab('attendance')" id="tab-btn-attendance" class="tab-btn col-span-2 sm:col-span-1 w-full sm:w-auto inline-flex items-center justify-center sm:justify-start gap-2 px-3 py-2 sm:px-4 text-xs sm:text-sm font-medium rounded-xl transition duration-150 text-slate-600 hover:bg-slate-50 hover:text-slate-900 border border-transparent" data-tab="attendance">
                    <i class="ti ti-clock text-lg"></i>
                    Attendance Logs
                </button>
            </div>

            <!-- Tab Contents -->

            <!-- Tab 1: Profile Info -->
            <div id="tab-content-profile-info" class="tab-content block">

                @if ($isEmployee)
                {{-- ===== READ-ONLY VIEW FOR EMPLOYEES ===== --}}
                <div class="mb-5 flex items-start gap-4 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                    <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                        <i class="ti ti-info-circle text-lg"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-amber-900">Your profile is managed by HR</p>
                        <p class="mt-0.5 text-xs text-amber-700">To update your personal information, file a <strong>Profile Update Request</strong> through Self-Service. HR will review and apply the changes.</p>
                        <a href="{{ route('self-service.profile', $employee) }}" class="mt-2 inline-flex items-center gap-1.5 rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700 transition">
                            <i class="ti ti-external-link"></i> Go to Self-Service
                        </a>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div class="space-y-6 lg:col-span-2">
                        <!-- Personal Info (Read-Only) -->
                        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 class="mb-4 text-lg font-bold text-slate-900 flex items-center gap-2">
                                <i class="ti ti-user text-blue-600 text-xl"></i>
                                Personal Information
                            </h2>
                            @if ($employee)
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">First Name</p><p class="mt-1 text-sm font-medium text-slate-800">{{ $employee->first_name ?? '—' }}</p></div>
                                <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Last Name</p><p class="mt-1 text-sm font-medium text-slate-800">{{ $employee->last_name ?? '—' }}</p></div>
                                <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Middle Name</p><p class="mt-1 text-sm font-medium text-slate-800">{{ $employee->middle_name ?: '—' }}</p></div>
                                <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Birth Date</p><p class="mt-1 text-sm font-medium text-slate-800">{{ $employee->birth_date?->format('M d, Y') ?? '—' }}</p></div>
                                <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Gender</p><p class="mt-1 text-sm font-medium text-slate-800">{{ $employee->gender ?: '—' }}</p></div>
                                <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Marital Status</p><p class="mt-1 text-sm font-medium text-slate-800">{{ $employee->marital_status ?: '—' }}</p></div>
                            </div>
                            @endif
                        </div>
                        <!-- Contact Info (Read-Only) -->
                        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 class="mb-4 text-lg font-bold text-slate-900 flex items-center gap-2">
                                <i class="ti ti-mail text-blue-600 text-xl"></i>
                                Contact Information
                            </h2>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Email</p><p class="mt-1 text-sm font-medium text-slate-800">{{ $user->email }}</p></div>
                                @if ($employee)
                                <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Phone</p><p class="mt-1 text-sm font-medium text-slate-800">{{ $employee->phone ?: '—' }}</p></div>
                                @endif
                            </div>
                        </div>
                        <!-- Address (Read-Only) -->
                        @if ($employee)
                        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 class="mb-4 text-lg font-bold text-slate-900 flex items-center gap-2">
                                <i class="ti ti-map-pin text-blue-600 text-xl"></i>
                                Address
                            </h2>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="sm:col-span-2"><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Address Line 1</p><p class="mt-1 text-sm font-medium text-slate-800">{{ $employee->address_line1 ?: '—' }}</p></div>
                                <div class="sm:col-span-2"><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Address Line 2</p><p class="mt-1 text-sm font-medium text-slate-800">{{ $employee->address_line2 ?: '—' }}</p></div>
                                <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">City</p><p class="mt-1 text-sm font-medium text-slate-800">{{ $employee->city ?: '—' }}</p></div>
                                <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Province</p><p class="mt-1 text-sm font-medium text-slate-800">{{ $employee->province ?: '—' }}</p></div>
                                <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Postal Code</p><p class="mt-1 text-sm font-medium text-slate-800">{{ $employee->postal_code ?: '—' }}</p></div>
                                <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Country</p><p class="mt-1 text-sm font-medium text-slate-800">{{ $employee->country ?: '—' }}</p></div>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Sidebar: Job Details (Read-Only) -->
                    <div class="space-y-6">
                        @if ($employee)
                        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">Job Details</h3>
                            <div class="space-y-3.5 text-sm">
                                <div class="flex flex-col gap-0.5"><span class="text-xs text-slate-400 uppercase tracking-wide">Employee Code</span><span class="font-semibold text-slate-800">{{ $employee->employee_code ?? 'N/A' }}</span></div>
                                <div class="flex flex-col gap-0.5"><span class="text-xs text-slate-400 uppercase tracking-wide">Employment Type</span><span class="font-semibold text-slate-800">{{ match((int) $employee->employment_type) {1 => 'Full-time', 2 => 'Part-time', 3 => 'Contract', 4 => 'Temporary', default => 'N/A'} }}</span></div>
                                <div class="flex flex-col gap-0.5"><span class="text-xs text-slate-400 uppercase tracking-wide">Hire Date</span><span class="font-semibold text-slate-800">{{ $employee->hire_date?->format('M d, Y') ?? 'N/A' }}</span></div>
                                <div class="flex flex-col gap-0.5"><span class="text-xs text-slate-400 uppercase tracking-wide">Manager</span><span class="font-semibold text-slate-800">{{ $employee->manager?->full_name ?? 'Not Assigned' }}</span></div>
                                <div class="flex flex-col gap-0.5"><span class="text-xs text-slate-400 uppercase tracking-wide">Status</span><span class="badge {{ match((int) $employee->status) {1 => 'badge-green', 2 => 'badge-amber', 3 => 'badge-blue', 4 => 'badge-gray', 5 => 'badge-red', default => 'badge-gray'} }}">{{ match((int) $employee->status) {1 => 'Active', 2 => 'Probationary', 3 => 'On Leave', 4 => 'Resigned', 5 => 'Terminated', default => 'Unknown'} }}</span></div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                @else
                {{-- ===== EDITABLE VIEW FOR HR / SUPERVISORS ===== --}}
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                    <!-- Main details (2 columns) -->
                    <div class="space-y-6 lg:col-span-2">

                        <!-- Account & Personal Info -->
                        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 class="mb-4 text-lg font-bold text-slate-900 flex items-center gap-2">
                                <i class="ti ti-user text-blue-600 text-xl"></i>
                                Personal Information
                            </h2>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                @if ($employee)
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">First Name</label>
                                        <input type="text" name="first_name" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 @error('first_name') border-red-500 @enderror" value="{{ old('first_name', $employee->first_name) }}" required>
                                        @error('first_name')
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Last Name</label>
                                        <input type="text" name="last_name" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 @error('last_name') border-red-500 @enderror" value="{{ old('last_name', $employee->last_name) }}" required>
                                        @error('last_name')
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Middle Name</label>
                                        <input type="text" name="middle_name" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" value="{{ old('middle_name', $employee->middle_name) }}">
                                    </div>
                                @endif

                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Display Name / Nickname</label>
                                    <input type="text" name="name" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 @error('name') border-red-500 @enderror" value="{{ old('name', $user->name) }}" required>
                                    @error('name')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                @if ($employee)
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Birth Date</label>
                                        <input type="date" name="birth_date" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" value="{{ old('birth_date', $employee->birth_date?->format('Y-m-d')) }}">
                                    </div>

                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Gender</label>
                                        <select name="gender" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500">
                                            <option value="">Select Gender</option>
                                            <option value="Male" @selected(old('gender', $employee->gender) === 'Male')>Male</option>
                                            <option value="Female" @selected(old('gender', $employee->gender) === 'Female')>Female</option>
                                            <option value="Non-binary" @selected(old('gender', $employee->gender) === 'Non-binary')>Non-binary</option>
                                            <option value="Prefer not to say" @selected(old('gender', $employee->gender) === 'Prefer not to say')>Prefer not to say</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Marital Status</label>
                                        <select name="marital_status" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500">
                                            <option value="">Select Status</option>
                                            <option value="Single" @selected(old('marital_status', $employee->marital_status) === 'Single')>Single</option>
                                            <option value="Married" @selected(old('marital_status', $employee->marital_status) === 'Married')>Married</option>
                                            <option value="Widowed" @selected(old('marital_status', $employee->marital_status) === 'Widowed')>Widowed</option>
                                            <option value="Divorced" @selected(old('marital_status', $employee->marital_status) === 'Divorced')>Divorced</option>
                                            <option value="Separated" @selected(old('marital_status', $employee->marital_status) === 'Separated')>Separated</option>
                                        </select>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Contact Details -->
                        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 class="mb-4 text-lg font-bold text-slate-900 flex items-center gap-2">
                                <i class="ti ti-mail text-blue-600 text-xl"></i>
                                Contact Information
                            </h2>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Email Address</label>
                                    <input type="email" name="email" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 @error('email') border-red-500 @enderror" value="{{ old('email', $user->email) }}" required>
                                    @error('email')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                @if ($employee)
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Phone Number</label>
                                        <input type="text" name="phone" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" value="{{ old('phone', $employee->phone) }}">
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Address details -->
                        @if ($employee)
                            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                                <h2 class="mb-4 text-lg font-bold text-slate-900 flex items-center gap-2">
                                    <i class="ti ti-map-pin text-blue-600 text-xl"></i>
                                    Address details
                                </h2>

                                <div class="space-y-4">
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div class="sm:col-span-2">
                                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Address Line 1</label>
                                            <input type="text" name="address_line1" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" value="{{ old('address_line1', $employee->address_line1) }}">
                                        </div>

                                        <div class="sm:col-span-2">
                                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Address Line 2 (Optional)</label>
                                            <input type="text" name="address_line2" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" value="{{ old('address_line2', $employee->address_line2) }}">
                                        </div>

                                        <div>
                                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">City</label>
                                            <input type="text" name="city" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" value="{{ old('city', $employee->city) }}">
                                        </div>

                                        <div>
                                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Province / State</label>
                                            <input type="text" name="province" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" value="{{ old('province', $employee->province) }}">
                                        </div>

                                        <div>
                                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Postal / Zip Code</label>
                                            <input type="text" name="postal_code" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" value="{{ old('postal_code', $employee->postal_code) }}">
                                        </div>

                                        <div>
                                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Country</label>
                                            <input type="text" name="country" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" value="{{ old('country', $employee->country) }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Sidebar Details / Read-Only details (1 column) -->
                    <div class="space-y-6">

                        <!-- Save profile settings button -->
                        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-3">
                            <button type="submit" class="w-full inline-flex justify-center items-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 hover:scale-[1.02] active:scale-[0.98]">
                                <i class="ti ti-device-floppy text-base"></i>
                                Save Changes
                            </button>
                            <a href="{{ route('dashboard') }}" class="w-full inline-flex justify-center items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                                Cancel
                            </a>
                        </div>

                        <!-- Read-Only Job Information -->
                        @if ($employee)
                            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">Job details</h3>

                                <div class="space-y-3.5 text-sm">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-xs text-slate-400 uppercase tracking-wide">Employee Code</span>
                                        <span class="font-semibold text-slate-800">{{ $employee->employee_code ?? 'N/A' }}</span>
                                    </div>
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-xs text-slate-400 uppercase tracking-wide">Employment Type</span>
                                        <span class="font-semibold text-slate-800">{{ match((int) $employee->employment_type) {1 => 'Full-time', 2 => 'Part-time', 3 => 'Contract', 4 => 'Temporary', default => 'N/A'} }}</span>
                                    </div>
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-xs text-slate-400 uppercase tracking-wide">Hire Date</span>
                                        <span class="font-semibold text-slate-800">{{ $employee->hire_date?->format('M d, Y') ?? 'N/A' }}</span>
                                    </div>
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-xs text-slate-400 uppercase tracking-wide">Manager</span>
                                        <span class="font-semibold text-slate-800">{{ $employee->manager?->full_name ?? 'Not Assigned' }}</span>
                                    </div>
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-xs text-slate-400 uppercase tracking-wide">Status</span>
                                        <div>
                                            <span class="badge {{ match((int) $employee->status) {1 => 'badge-green', 2 => 'badge-amber', 3 => 'badge-blue', 4 => 'badge-gray', 5 => 'badge-red', default => 'badge-gray'} }}">
                                                {{ match((int) $employee->status) {1 => 'Active', 2 => 'Probationary', 3 => 'On Leave', 4 => 'Resigned', 5 => 'Terminated', default => 'Unknown'} }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                </div>
                @endif
            </div>

            <!-- Tab 2: Leave Requests -->
            <div id="tab-content-leave" class="tab-content hidden">
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                                <i class="ti ti-calendar text-blue-600 text-xl"></i>
                                Leave Requests
                            </h2>
                            <p class="text-xs text-slate-500 mt-1">History of all leave requests and their statuses</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-slate-100">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100">
                                <tr>
                                    <th class="px-4 py-3.5">Type</th>
                                    <th class="px-4 py-3.5">From</th>
                                    <th class="px-4 py-3.5">To</th>
                                    <th class="px-4 py-3.5">Status</th>
                                    <th class="px-4 py-3.5">Submitted</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @forelse ($leaveRequests as $leave)
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $leave['type'] }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $leave['start_date'] }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $leave['end_date'] }}</td>
                                        <td class="px-4 py-3">
                                            <span class="badge {{ $badge($leave['status']) }}">
                                                {{ $leave['status'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-slate-500 text-xs">{{ $leave['created_at'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-slate-400 italic">No leave requests found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Payslips -->
            <div id="tab-content-payslips" class="tab-content hidden">
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                                <i class="ti ti-receipt text-blue-600 text-xl"></i>
                                Payslips
                            </h2>
                            <p class="text-xs text-slate-500 mt-1">History of payroll releases and interactive breakdowns</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-slate-100">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100">
                                <tr>
                                    <th class="px-4 py-3.5">Period</th>
                                    <th class="px-4 py-3.5">Pay Date</th>
                                    <th class="px-4 py-3.5">Gross Pay</th>
                                    <th class="px-4 py-3.5">Deductions</th>
                                    <th class="px-4 py-3.5">Net Take-Home</th>
                                    <th class="px-4 py-3.5">Status</th>
                                    <th class="px-4 py-3.5 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @forelse ($payslips as $payslip)
                                    @php
                                        $payslipBreakdown = [
                                            'employee_name' => $employee ? $employee->full_name_with_middle_name : $user->name,
                                            'base_salary' => $payslip['base_salary_value'],
                                            'frequency' => $payslip['frequency_label'],
                                            'gross' => $payslip['gross_pay_value'],
                                            'deductions' => $payslip['total_deductions_value'],
                                            'net' => $payslip['net_pay_value'],
                                            'line_items' => $payslip['line_items'],
                                        ];
                                    @endphp
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $payslip['period'] }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $payslip['pay_date'] }}</td>
                                        <td class="px-4 py-3 text-slate-600">PHP {{ $payslip['gross_pay'] }}</td>
                                        <td class="px-4 py-3 text-red-500">-PHP {{ $payslip['total_deductions'] }}</td>
                                        <td class="px-4 py-3 font-bold text-slate-900">PHP {{ $payslip['net_pay'] }}</td>
                                        <td class="px-4 py-3">
                                            <span class="badge {{ $badge($payslip['status']) }}">
                                                {{ $payslip['status'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <button type="button"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-blue-600"
                                                data-payslip='@json($payslipBreakdown)'
                                                onclick="viewPayslipBreakdown(this)">
                                                <i class="ti ti-eye"></i>
                                                View
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-8 text-center text-slate-400 italic">No payslips found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab 4: Documents -->
            <div id="tab-content-documents" class="tab-content hidden">
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                                <i class="ti ti-files text-blue-600 text-xl"></i>
                                Documents
                            </h2>
                            <p class="text-xs text-slate-500 mt-1">Uploaded employment documents and forms</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-slate-100">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100">
                                <tr>
                                    <th class="px-4 py-3.5">Name</th>
                                    <th class="px-4 py-3.5">Type</th>
                                    <th class="px-4 py-3.5">Date</th>
                                    <th class="px-4 py-3.5">Size</th>
                                    <th class="px-4 py-3.5">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @forelse ($documents as $document)
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-4 py-3 font-semibold text-slate-900">
                                            <div class="flex items-center gap-2">
                                                <i class="ti ti-file-text text-slate-400 text-lg"></i>
                                                {{ $document['name'] }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-slate-600 text-xs">{{ $document['type'] }}</td>
                                        <td class="px-4 py-3 text-slate-500 text-xs">{{ $document['date'] }}</td>
                                        <td class="px-4 py-3 text-slate-500 text-xs">{{ $document['file_size'] ?: 'N/A' }}</td>
                                        <td class="px-4 py-3">
                                            @if ($document['file_path'])
                                                <span class="badge badge-green">
                                                    Stored
                                                </span>
                                            @else
                                                <span class="badge badge-gray">
                                                    No file
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-slate-400 italic">No documents found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab 5: Attendance Logs -->
            <div id="tab-content-attendance" class="tab-content hidden">
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                                <i class="ti ti-clock text-blue-600 text-xl"></i>
                                Attendance Logs
                            </h2>
                            <p class="text-xs text-slate-500 mt-1">Recent check-in and check-out logs</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-slate-100">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100">
                                <tr>
                                    <th class="px-4 py-3.5">Date</th>
                                    <th class="px-4 py-3.5">Check In</th>
                                    <th class="px-4 py-3.5">Check Out</th>
                                    <th class="px-4 py-3.5">Status</th>
                                    <th class="px-4 py-3.5">Notes</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @forelse ($attendanceLogs as $attendance)
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $attendance['date'] }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $attendance['check_in'] }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $attendance['check_out'] }}</td>
                                        <td class="px-4 py-3">
                                            <span class="badge {{ $badge($attendance['status']) }}">
                                                {{ $attendance['status'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-slate-500 text-xs">{{ $attendance['notes'] ?: 'No notes' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-slate-400 italic">No attendance logs found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </form>
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
                <button type="button" onclick="closePayslipModal()" class="rounded-[0.5rem] border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-[#06112e] shadow-sm transition hover:bg-slate-50">Close</button>
            </div>
        </div>
    </div>

    <!-- Scripts and Interactions -->
    <script>
        // Tab switching logic
        function switchTab(tabId) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(el => {
                el.classList.add('hidden');
                el.classList.remove('block');
            });
            // Show selected tab content
            document.getElementById('tab-content-' + tabId).classList.remove('hidden');
            document.getElementById('tab-content-' + tabId).classList.add('block');

            // Reset all tab button styles
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('bg-blue-50', 'text-blue-600', 'border-blue-100', 'font-semibold');
                btn.classList.add('text-slate-600', 'border-transparent');
            });
            // Apply active styles to the selected tab button
            const activeBtn = document.getElementById('tab-btn-' + tabId);
            activeBtn.classList.remove('text-slate-600', 'border-transparent');
            activeBtn.classList.add('bg-blue-50', 'text-blue-600', 'border-blue-100', 'font-semibold');
        }

        // Payslip Breakdown Modal and Render helpers
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
            const GROUP_PREFIXES = ['attendance', 'previous claim', 'disputes', 'plotted payment', 'previous claim (plotted payment)'];

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
            if (event.key === 'Escape') {
                closePayslipModal();
            }
        });

        // Profile Picture selection previewer
        document.addEventListener('DOMContentLoaded', () => {
            const fileInput = document.getElementById('profile_picture');
            const previewImg = document.getElementById('avatar-preview');
            const fallbackDiv = document.getElementById('avatar-fallback');

            if (fileInput) {
                fileInput.addEventListener('change', (e) => {
                    const files = e.target.files;
                    if (files && files[0]) {
                        const reader = new FileReader();
                        reader.onload = (event) => {
                            if (previewImg) {
                                previewImg.src = event.target.result;
                                previewImg.classList.remove('hidden');
                            }
                            if (fallbackDiv) {
                                fallbackDiv.classList.add('hidden');
                            }
                        };
                        reader.readAsDataURL(files[0]);
                    }
                });
            }
        });
    </script>
</x-app-layout>
