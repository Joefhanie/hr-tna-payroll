@php
    $account = session('registration.account', []);
    $profile = session('registration.profile', []);

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

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    <!-- Employment Type -->
    <div>
        <label for="employment_type" class="block text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-2">Employment Type</label>
        <div class="relative">
            <select id="employment_type" name="employment_type" required class="reg-field appearance-none pr-10">
                <option value="">Select type</option>
                @foreach ([1 => 'Full-time', 2 => 'Part-time', 3 => 'Contractual', 4 => 'Intern'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('employment_type') == $value)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
    </div>

    <!-- Hire Date -->
    <div>
        <label for="hire_date" class="block text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-2">Hire Date</label>
        <input id="hire_date" name="hire_date" type="date" value="{{ old('hire_date', now()->toDateString()) }}" required
            class="reg-field">
    </div>

    <!-- Department -->
    <div>
        <label for="department_id" class="block text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-2">Department</label>
        <div class="relative">
            <select id="department_id" name="department_id" class="reg-field appearance-none pr-10">
                <option value="">Select department</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
    </div>

    <!-- Position -->
    <div>
        <label for="position_id" class="block text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-2">Position</label>
        <div class="relative">
            <select id="position_id" name="position_id" class="reg-field appearance-none pr-10 disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed">
                <option value="">Select position</option>
                @foreach ($positions as $position)
                    <option value="{{ $position->id }}" data-department-id="{{ $position->department_id }}" @selected(old('position_id') == $position->id)>{{ $position->title }}</option>
                @endforeach
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
    </div>

    <!-- Direct Manager -->
    <div class="md:col-span-2">
        <label for="manager_id" class="block text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-2">Direct Manager</label>
        <div class="relative">
            <select id="manager_id" name="manager_id" class="reg-field appearance-none pr-10">
                <option value="">Select manager</option>
                @foreach ($managers as $manager)
                    <option value="{{ $manager->id }}" @selected(old('manager_id') == $manager->id)>{{ $manager->full_name ?? $manager->name ?? 'Employee #' . $manager->id }}</option>
                @endforeach
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
    </div>
</div>

<div class="flex items-center justify-between border-t border-slate-100 pt-6 mt-8">
    <a href="{{ route('register.profile') }}" class="brand-btn-ghost">Back</a>
    <button type="submit" class="brand-btn rounded-xl px-6 py-3 text-sm font-semibold shadow-md">Create account</button>
</div>

<script>
    (function () {
        const deptSelect = document.getElementById('department_id');
        const posSelect  = document.getElementById('position_id');
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
                if (departmentChildrenMap[deptId]) validDeptIds.push(...departmentChildrenMap[deptId]);

                Array.from(posSelect.options).forEach(opt => {
                    if (opt.value === '') return;
                    const optDeptId = String(opt.dataset.departmentId);
                    if (optDeptId === '' || validDeptIds.includes(optDeptId)) {
                        opt.hidden = false; opt.disabled = false;
                        if (opt.value === currentPosId) isValidSelection = true;
                    } else {
                        opt.hidden = true; opt.disabled = true;
                    }
                });

                if (!isValidSelection) posSelect.value = '';
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
                        updatePositionDropdown();
                    }
                }
            });
            updatePositionDropdown();
        }
    })();
</script>
