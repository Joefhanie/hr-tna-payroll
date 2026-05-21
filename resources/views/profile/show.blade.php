<x-app-layout>
    <x-slot:title>My Profile Settings</x-slot:title>
    <x-slot:header>My Profile Settings</x-slot:header>

    @php
        $initials = collect(preg_split('/\s+/', trim($user->name)))
            ->filter()
            ->take(2)
            ->map(fn($part) => strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
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
                                <img id="avatar-preview" src="{{ asset('storage/' . $employee->profile_picture) }}" alt="Profile Picture" class="h-full w-full object-cover">
                            @else
                                <div id="avatar-fallback" class="flex h-full w-full items-center justify-center text-3xl font-bold bg-indigo-600">
                                    {{ $initials }}
                                </div>
                                <img id="avatar-preview" src="" alt="Profile Picture" class="hidden h-full w-full object-cover">
                            @endif

                            @if ($employee)
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

            <!-- Profile form segments -->
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
        </form>
    </div>

    <!-- Micro-interaction JavaScript -->
    <script>
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
