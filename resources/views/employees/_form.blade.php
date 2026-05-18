@php
    $employee = $employee ?? null;
    $isEdit   = $isEdit ?? false;

    $inp = 'w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 placeholder-slate-400 transition focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20';
    $sel = 'w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 transition focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20';
    $lbl = 'block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2';
    $err = 'mt-2 text-xs text-red-500';

    $steps = [
        ['label' => 'Personal',    'color' => 'indigo'],
        ['label' => 'Contact',     'color' => 'sky'],
        ['label' => 'Employment',  'color' => 'emerald'],
        ['label' => 'Termination', 'color' => 'rose'],
    ];

    // Pre-calculate all descendant department IDs for each department
    $departmentChildrenMap = [];
    foreach ($departments as $dept) {
        $childrenIds = [];
        $getDescendants = function($parentId) use (&$getDescendants, $departments, &$childrenIds) {
            foreach ($departments as $d) {
                if ($d->parent_dept_id == $parentId) {
                    $childrenIds[] = (string) $d->id;
                    $getDescendants($d->id);
                }
            }
        };
        $getDescendants($dept->id);
        $departmentChildrenMap[$dept->id] = $childrenIds;
    }
@endphp

{{-- ── Step Progress Bar ─────────────────────────────────────────────── --}}
<div class="mb-10">
    {{-- Steps --}}
    <div class="relative flex items-center justify-between">
        {{-- Connecting line behind steps --}}
        <div class="absolute left-0 top-6 h-1 w-full bg-slate-300 -z-10"></div>
        <div id="wizard-progress-line" class="absolute left-0 top-6 h-1 bg-indigo-600 -z-10 transition-all duration-500" style="width:0%"></div>

        @foreach ($steps as $i => $step)
        <div class="wizard-step-indicator flex flex-col items-center gap-3" data-step="{{ $i + 1 }}">
            <div class="step-circle flex h-12 w-12 items-center justify-center rounded-full border-2.5 border-slate-300 bg-white text-base font-bold text-slate-500 shadow-sm transition-all duration-300">
                <span class="step-num">{{ $i + 1 }}</span>
                <svg class="step-check hidden h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <span class="step-label text-sm font-semibold text-slate-600 transition-colors duration-300">{{ $step['label'] }}</span>
        </div>
        @endforeach
    </div>
</div>

{{-- ── Step 1: Personal Information ─────────────────────────────────── --}}
<div class="wizard-panel" data-panel="1">
    <div class="mb-7 flex items-center gap-4">
        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-white shadow-sm">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
        </div>
        <div>
            <h3 class="text-base font-semibold text-slate-800">Personal Information</h3>
            <p class="text-xs text-slate-400">Basic identity details of the employee</p>
        </div>
    </div>

    <div class="grid gap-6 sm:grid-cols-3">
        <div>
            <label class="{{ $lbl }}" for="first_name">First Name</label>
            <input id="first_name" name="first_name" type="text" value="{{ old('first_name', $employee->first_name ?? '') }}" placeholder="e.g. Juan" class="{{ $inp }}">
            @error('first_name')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="{{ $lbl }}" for="middle_name">Middle Name <span class="font-normal normal-case text-slate-400">(optional)</span></label>
            <input id="middle_name" name="middle_name" type="text" value="{{ old('middle_name', $employee->middle_name ?? '') }}" placeholder="e.g. Dela" class="{{ $inp }}">
            @error('middle_name')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="{{ $lbl }}" for="last_name">Last Name</label>
            <input id="last_name" name="last_name" type="text" value="{{ old('last_name', $employee->last_name ?? '') }}" placeholder="e.g. Cruz" class="{{ $inp }}">
            @error('last_name')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="{{ $lbl }}" for="gender">Gender</label>
            <select id="gender" name="gender" class="{{ $sel }}">
                <option value="">Select gender</option>
                @foreach (['Male', 'Female', 'Non-binary', 'Prefer not to say'] as $gender)
                    <option value="{{ $gender }}" @selected(old('gender', $employee->gender ?? '') === $gender)>{{ $gender }}</option>
                @endforeach
            </select>
            @error('gender')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="{{ $lbl }}" for="birth_date">Birth Date</label>
            <input id="birth_date" name="birth_date" type="date" value="{{ old('birth_date', optional($employee->birth_date ?? null)->format('Y-m-d')) }}" class="{{ $inp }}">
            @error('birth_date')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="{{ $lbl }}" for="nationality">Nationality</label>
            <input id="nationality" name="nationality" type="text" value="{{ old('nationality', $employee->nationality ?? '') }}" placeholder="e.g. Filipino" class="{{ $inp }}">
            @error('nationality')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="{{ $lbl }}" for="marital_status">Marital Status</label>
            <select id="marital_status" name="marital_status" class="{{ $sel }}">
                <option value="">Select status</option>
                @foreach (['Single', 'Married', 'Widowed', 'Divorced', 'Separated'] as $status)
                    <option value="{{ $status }}" @selected(old('marital_status', $employee->marital_status ?? '') === $status)>{{ $status }}</option>
                @endforeach
            </select>
            @error('marital_status')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
    </div>
</div>

{{-- ── Step 2: Contact & Address ─────────────────────────────────────── --}}
<div class="wizard-panel hidden" data-panel="2">
    <div class="mb-7 flex items-center gap-4">
        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-sky-600 text-white shadow-sm">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>
        <div>
            <h3 class="text-base font-semibold text-slate-800">Contact & Address</h3>
            <p class="text-xs text-slate-400">Reachability and location details</p>
        </div>
    </div>

    <div class="grid gap-6 sm:grid-cols-2">
        <div>
            <label class="{{ $lbl }}" for="email">Email Address</label>
            <input id="email" name="email" type="email" value="{{ old('email', $employee->email ?? ($pendingUser->email ?? '')) }}" placeholder="e.g. juan@company.com" class="{{ $inp }}">
            @error('email')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="{{ $lbl }}" for="phone">Phone Number</label>
            <input id="phone" name="phone" type="text" value="{{ old('phone', $employee->phone ?? '') }}" placeholder="e.g. 09xxxxxxxxx" class="{{ $inp }}">
            @error('phone')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
        <div class="sm:col-span-2">
            <label class="{{ $lbl }}" for="address_line1">Address Line 1</label>
            <input id="address_line1" name="address_line1" type="text" value="{{ old('address_line1', $employee->address_line1 ?? '') }}" placeholder="Street / Barangay" class="{{ $inp }}">
            @error('address_line1')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
        <div class="sm:col-span-2">
            <label class="{{ $lbl }}" for="address_line2">Address Line 2 <span class="font-normal normal-case text-slate-400">(optional)</span></label>
            <input id="address_line2" name="address_line2" type="text" value="{{ old('address_line2', $employee->address_line2 ?? '') }}" placeholder="Subdivision / Building / Unit" class="{{ $inp }}">
            @error('address_line2')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="{{ $lbl }}" for="city">City / Municipality</label>
            <input id="city" name="city" type="text" value="{{ old('city', $employee->city ?? '') }}" placeholder="e.g. Quezon City" class="{{ $inp }}">
            @error('city')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="{{ $lbl }}" for="province">Province / State</label>
            <input id="province" name="province" type="text" value="{{ old('province', $employee->province ?? '') }}" placeholder="e.g. Metro Manila" class="{{ $inp }}">
            @error('province')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="{{ $lbl }}" for="postal_code">Postal Code</label>
            <input id="postal_code" name="postal_code" type="text" value="{{ old('postal_code', $employee->postal_code ?? '') }}" placeholder="e.g. 1100" class="{{ $inp }}">
            @error('postal_code')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="{{ $lbl }}" for="country">Country</label>
            <input id="country" name="country" type="text" value="{{ old('country', $employee->country ?? '') }}" placeholder="e.g. Philippines" class="{{ $inp }}">
            @error('country')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
    </div>
</div>

{{-- ── Step 3: Employment Details ────────────────────────────────────── --}}
<div class="wizard-panel hidden" data-panel="3">
    <div class="mb-7 flex items-center gap-4">
        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-600 text-white shadow-sm">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>
        <div>
            <h3 class="text-base font-semibold text-slate-800">Employment Details</h3>
            <p class="text-xs text-slate-400">Role, assignment, and employment status</p>
        </div>
    </div>

    <div class="grid gap-6 sm:grid-cols-2">
        <div>
            <label class="{{ $lbl }}" for="employment_type">Employment Type</label>
            @php
                // UI uses numeric codes; DB now stores integer codes for employment_type
                $employmentOptions = [1 => 'Full-time', 2 => 'Part-time', 3 => 'Contractual', 4 => 'Intern'];
                $selectedEmployment = old('employment_type', $employee->employment_type ?? '');
            @endphp
            <select id="employment_type" name="employment_type" class="{{ $sel }}">
                <option value="" disabled>Select type</option>
                @foreach ($employmentOptions as $key => $label)
                    <option value="{{ $key }}" @selected((string)$selectedEmployment === (string)$key)>{{ $label }}</option>
                @endforeach
            </select>
            @error('employment_type')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="{{ $lbl }}" for="department_id">Department</label>
            <select id="department_id" name="department_id" class="{{ $sel }}">
                <option value="">Select department</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected(old('department_id', $employee->department_id ?? '') == $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
            @error('department_id')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="{{ $lbl }}" for="position_id">Position</label>
            <select id="position_id" name="position_id" class="{{ $sel }} disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed">
                <option value="">Select position</option>
                @foreach ($positions as $position)
                    <option value="{{ $position->id }}" data-department-id="{{ $position->department_id }}" @selected(old('position_id', $employee->position_id ?? '') == $position->id)>{{ $position->title }}</option>
                @endforeach
            </select>
            @error('position_id')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="{{ $lbl }}" for="manager_id">Direct Manager <span class="font-normal normal-case text-slate-400">(optional)</span></label>
            <select id="manager_id" name="manager_id" class="{{ $sel }}">
                <option value="">Select manager</option>
                @foreach ($managers as $manager)
                    <option value="{{ $manager->id }}" @selected(old('manager_id', $employee->manager_id ?? '') == $manager->id)>
                        {{ $manager->full_name ?? $manager->name ?? 'Employee #' . $manager->id }}
                    </option>
                @endforeach
            </select>
            @error('manager_id')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="{{ $lbl }}" for="status">Employment Status <span class="text-red-500">*</span></label>
            @php
                $statusOptions = [
                    1 => 'Active',
                    2 => 'Probationary',
                    3 => 'On Leave',
                    4 => 'Resigned',
                    5 => 'Terminated',
                ];
                $selectedStatus = old('status', $employee->status ?? '');
            @endphp
            <select id="status" name="status" required class="{{ $sel }}">
                <option value="" disabled selected>Select status</option>
                @foreach ($statusOptions as $key => $label)
                    <option value="{{ $key }}" @selected((string)$selectedStatus == (string)$key)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="{{ $lbl }}" for="hire_date">Hire Date</label>
            <input id="hire_date" name="hire_date" type="date" value="{{ old('hire_date', optional($employee->hire_date ?? null)->format('Y-m-d')) }}" class="{{ $inp }}">
            @error('hire_date')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="{{ $lbl }}" for="regularization_date">Regularization Date <span class="font-normal normal-case text-slate-400">(optional)</span></label>
            <input id="regularization_date" name="regularization_date" type="date" value="{{ old('regularization_date', optional($employee->regularization_date ?? null)->format('Y-m-d')) }}" class="{{ $inp }}">
            @error('regularization_date')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
    </div>
</div>

{{-- ── Step 4: Termination Details ──────────────────────────────────── --}}
<div class="wizard-panel hidden" data-panel="4">
    <div class="mb-7 flex items-center gap-4">
        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-rose-500 text-white shadow-sm">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <h3 class="text-base font-semibold text-slate-800">Termination Details</h3>
            <p class="text-xs text-slate-400">Leave blank if not applicable</p>
        </div>
    </div>

    <div class="grid gap-6 sm:grid-cols-2">
        <div>
            <label class="{{ $lbl }}" for="termination_date">Termination Date</label>
            <input id="termination_date" name="termination_date" type="date" value="{{ old('termination_date', optional($employee->termination_date ?? null)->format('Y-m-d')) }}" class="{{ $inp }}">
            @error('termination_date')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="{{ $lbl }}" for="termination_reason">Termination Reason</label>
            <input id="termination_reason" name="termination_reason" type="text" value="{{ old('termination_reason', $employee->termination_reason ?? '') }}" placeholder="e.g. Resignation" class="{{ $inp }}">
            @error('termination_reason')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
    </div>

    <p class="mt-6 text-xs text-slate-400">Review all details before submitting. You can go back to any step using the Previous button.</p>
</div>

{{-- ── Navigation Buttons ────────────────────────────────────────────── --}}
<div class="mt-10 flex items-center justify-between border-t border-slate-100 pt-8">
    <button type="button" id="wizard-prev"
            class="hidden rounded-lg border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
        ← Previous
    </button>
    <div class="ml-auto flex items-center gap-3">
        <a href="{{ route('employees.index') }}"
           class="rounded-lg border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
            Cancel
        </a>
        <button type="button" id="wizard-next"
                class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
            Next →
        </button>
        <button type="submit" id="wizard-submit"
                class="hidden rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            {{ $isEdit ? 'Save Changes' : 'Create Employee' }}
        </button>
    </div>
</div>

{{-- ── Wizard Script ─────────────────────────────────────────────────── --}}
<script>
(function () {
    const TOTAL = 4;
    let current = 1;

    function isStepCompleted(stepNum) {
        // A step is only completed if it has been passed and has no error fields
        if (stepNum >= current) return false; // Future or current steps are not completed

        const panel = document.querySelector(`.wizard-panel[data-panel="${stepNum}"]`);
        if (!panel) return false;

        const errors = panel.querySelectorAll('[class*="text-red"]');
        return errors.length === 0; // Completed only if no errors
    }

    function update() {
        // Panels
        document.querySelectorAll('.wizard-panel').forEach(p => {
            p.classList.toggle('hidden', parseInt(p.dataset.panel) !== current);
        });

        // Step indicators
        document.querySelectorAll('.wizard-step-indicator').forEach(ind => {
            const s     = parseInt(ind.dataset.step);
            const circle = ind.querySelector('.step-circle');
            const num    = ind.querySelector('.step-num');
            const check  = ind.querySelector('.step-check');
            const label  = ind.querySelector('.step-label');

            circle.classList.remove('border-indigo-600', 'bg-indigo-600', 'text-white',
                                    'border-indigo-300', 'bg-indigo-50',  'text-indigo-600',
                                    'border-slate-200',  'bg-white',      'text-slate-400');
            if (s < current) {
                // Completed
                circle.classList.add('border-indigo-600', 'bg-indigo-600', 'text-white');
                num.classList.add('hidden'); check.classList.remove('hidden');
                label.classList.replace('text-slate-400', 'text-indigo-600');
            } else if (s === current) {
                // Active
                circle.classList.add('border-indigo-600', 'bg-indigo-50', 'text-indigo-600');
                num.classList.remove('hidden'); check.classList.add('hidden');
                label.classList.replace('text-slate-400', 'text-indigo-600');
            } else {
                // Future
                circle.classList.add('border-slate-200', 'bg-white', 'text-slate-400');
                num.classList.remove('hidden'); check.classList.add('hidden');
                try { label.classList.replace('text-indigo-600', 'text-slate-400'); } catch(e) {}
            }
        });

        // Progress line
        const pct = ((current - 1) / (TOTAL - 1)) * 100;
        document.getElementById('wizard-progress-line').style.width = pct + '%';

        // Buttons
        document.getElementById('wizard-prev').classList.toggle('hidden', current === 1);
        document.getElementById('wizard-next').classList.toggle('hidden', current === TOTAL);
        document.getElementById('wizard-submit').classList.toggle('hidden', current !== TOTAL);
    }

    document.getElementById('wizard-next').addEventListener('click', () => {
        if (current < TOTAL) { current++; update(); window.scrollTo({top: 0, behavior: 'smooth'}); }
    });
    document.getElementById('wizard-prev').addEventListener('click', () => {
        if (current > 1) { current--; update(); window.scrollTo({top: 0, behavior: 'smooth'}); }
    });

    update();

    // Position Dropdown Filtering Logic
    const deptSelect = document.getElementById('department_id');
    const posSelect = document.getElementById('position_id');
    const departmentChildrenMap = @json($departmentChildrenMap);
    
    function updatePositionDropdown() {
        const deptId = String(deptSelect.value);
        const currentPosId = posSelect.value;
        
        if (!deptId) {
            posSelect.disabled = true;
            posSelect.value = '';
        } else {
            posSelect.disabled = false;
            let isValidSelection = false;
            
            const validDeptIds = [deptId];
            if (departmentChildrenMap[deptId]) {
                validDeptIds.push(...departmentChildrenMap[deptId]);
            }
            
            Array.from(posSelect.options).forEach(opt => {
                if (opt.value === '') return;
                
                const optDeptId = String(opt.dataset.departmentId);
                
                if (optDeptId === '' || validDeptIds.includes(optDeptId)) {
                    opt.hidden = false;
                    opt.disabled = false;
                    if (opt.value === currentPosId) isValidSelection = true;
                } else {
                    opt.hidden = true;
                    opt.disabled = true;
                }
            });
            
            if (!isValidSelection) {
                posSelect.value = '';
            }
        }
    }
    
    if (deptSelect && posSelect) {
        deptSelect.addEventListener('change', updatePositionDropdown);
        
        posSelect.addEventListener('change', () => {
            const selectedOpt = posSelect.options[posSelect.selectedIndex];
            if (selectedOpt && selectedOpt.value) {
                const optDeptId = selectedOpt.dataset.departmentId;
                if (optDeptId && deptSelect.value !== optDeptId) {
                    deptSelect.value = optDeptId;
                    // Re-run the dropdown update to ensure UI consistency
                    updatePositionDropdown();
                }
            }
        });
        
        // Delay initial run slightly to ensure selected values are populated
        setTimeout(updatePositionDropdown, 0);
    }
})();
</script>
