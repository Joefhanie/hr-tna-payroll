<x-app-layout>
    <x-slot:title>Temporary Access</x-slot:title>
    <x-slot:header>Temporary Access</x-slot:header>

    {{-- ── Page Header ── --}}
    <div class="mb-6 flex items-center justify-between pb-6 border-b border-slate-200">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Temporary Access</h1>
            <p class="mt-1 text-sm text-slate-600">View and manage temporary role permissions and scheduled system access for employees.</p>
        </div>
        <button type="button" id="openAccessModal"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
            <i class="ti ti-shield-lock text-base"></i>
            Grant Temporary Access
        </button>
    </div>

    {{-- Tabs removed --}}

    {{-- ── Filters ── --}}
    <form id="taFilterForm" method="GET" action="{{ route('employees.temporary-access') }}" class="card p-4 mb-6">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[220px]">
                <label class="block text-xs font-medium text-slate-600 mb-1">Search</label>
                <div class="relative">
                    <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input id="ta-search" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="Search by name, code, email, position…"
                           class="w-full rounded-lg border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>
            </div>

            <div class="min-w-[160px]">
                <label class="block text-xs font-medium text-slate-600 mb-1">From Date</label>
                <input type="date" name="from_date" id="taFromDate" value="{{ $filters['from_date'] ?? '' }}" onchange="this.form.submit()" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="min-w-[160px]">
                <label class="block text-xs font-medium text-slate-600 mb-1">To Date</label>
                <input type="date" name="to_date" id="taToDate" value="{{ $filters['to_date'] ?? '' }}" onchange="this.form.submit()" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="min-w-[180px]">
                <label class="block text-xs font-medium text-slate-600 mb-1">Position</label>
                <select name="position_id" id="taPosition" onchange="this.form.submit()" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Positions</option>
                    @foreach ($positions as $position)
                        <option value="{{ $position->id }}" {{ ($filters['position_id'] ?? '') == $position->id ? 'selected' : '' }}>{{ $position->title }}</option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-[180px]">
                <label class="block text-xs font-medium text-slate-600 mb-1">Department</label>
                <select name="department_id" id="taDepartment" onchange="this.form.submit()" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Departments</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" {{ ($filters['department_id'] ?? '') == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>

            <a href="{{ route('employees.temporary-access') }}" class="px-4 py-2 rounded-lg border border-slate-200 text-sm text-slate-600 hover:bg-slate-50 transition flex items-center gap-1.5">
                Clear
            </a>

            @if(auth()->user()->role === 4)
                <a href="{{ route('employees.temporary-access.export') }}" id="btnTaExport" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-lg border border-slate-200 text-sm font-medium transition flex items-center gap-1.5 ml-auto">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export CSV
                </a>
            @endif
        </div>
    </form>



    {{-- ── Table ── --}}
    <div class="card overflow-hidden">
        @if ($employees->count() > 0)
            <table id="ta-table" class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">ID</th>
                         <th class="px-4 py-3">Code</th>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Position</th>
                        <th class="px-4 py-3">Dep</th>
                        <th class="px-4 py-3">From</th>
                        <th class="px-4 py-3">To</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3">Temporary Role</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($employees as $employee)
                        @php
                            $user = $employee->user;
                            $roleLabels = [1 => 'Employee', 2 => 'Supervisor', 4 => 'HR'];
                            $roleColors = [
                                1 => 'badge-blue',
                                2 => 'badge-gray',
                                4 => 'badge-red',
                            ];

                            $now = now();
                            // Only show assignments that are currently active or still scheduled.
                            $temporaryAssignment = $user
                                ? $user->temporaryAssignments
                                    ->where('is_active', true)
                                    ->sortByDesc('to_date')
                                    ->first(function ($assignment) use ($now) {
                                        return $assignment->from_date
                                            && $assignment->to_date
                                            && ($now->between($assignment->from_date, $assignment->to_date) || $assignment->from_date->isFuture());
                                    })
                                : null;
                            $isCurrentTemporary = $temporaryAssignment
                                && $temporaryAssignment->is_active
                                && $temporaryAssignment->from_date
                                && $temporaryAssignment->to_date
                                && $now->between($temporaryAssignment->from_date, $temporaryAssignment->to_date);

                            $isScheduled = $temporaryAssignment
                                && $temporaryAssignment->is_active
                                && $temporaryAssignment->from_date
                                && $temporaryAssignment->from_date->isFuture();

                            if ($isCurrentTemporary) {
                                $statusLabel = 'Active';
                                $statusColor = 'badge-green';
                            } elseif ($isScheduled) {
                                $statusLabel = 'Scheduled';
                                $statusColor = 'badge-blue';
                            } else {
                                $statusLabel = 'None';
                                $statusColor = 'badge-gray';
                            }

                            if ($temporaryAssignment) {
                                $tempRoleLabel = $roleLabels[$temporaryAssignment->temporary_role] ?? 'Role';
                                $tempRoleColor = $roleColors[$temporaryAssignment->temporary_role] ?? 'badge-gray';
                            } else {
                                $tempRoleLabel = '—';
                                $tempRoleColor = '';
                            }

                            // Role column should represent original_role from temporary assignments
                            // when available, with user role as fallback.
                            $latestAssignmentForRole = $user
                                ? $user->temporaryAssignments->sortByDesc('id')->first()
                                : null;
                            $baseRole = $latestAssignmentForRole
                                ? $latestAssignmentForRole->original_role
                                : ($user ? $user->role : null);
                            $roleLabel = $baseRole ? ($roleLabels[$baseRole] ?? 'N/A') : 'N/A';
                            $roleColor = $baseRole ? ($roleColors[$baseRole] ?? 'badge-gray') : 'badge-gray';
                        @endphp
                        <tr class="ta-row hover:bg-slate-50 transition">
                            <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $employee->id }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $employee->employee_code }}</td>

                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if ($employee->profile_picture)
                                        <div class="h-8 w-8 overflow-hidden rounded-full bg-slate-100">
                                            <img src="{{ route('media.file', ['path' => ltrim($employee->profile_picture, '/')]) }}" alt="{{ $employee->full_name }}" class="h-8 w-8 object-cover">
                                        </div>
                                    @else
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700">
                                            {{ collect(explode(' ', $employee->full_name))->map(fn($n) => $n[0] ?? '')->join('') }}
                                        </div>
                                    @endif
                                    <div>
                                        <p class="font-medium text-slate-900 ta-name">{{ $employee->full_name }}</p>
                                        <p class="text-xs text-slate-500">{{ $employee->email }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-3 text-slate-600">{{ $employee->position->title ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $employee->department->name ?? 'N/A' }}</td>

                            {{-- From --}}
                            <td class="px-4 py-3 text-xs text-slate-500 font-mono">
                                @if ($temporaryAssignment)
                                    {{ $temporaryAssignment->from_date?->format('Y-m-d') }}
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>

                            {{-- To --}}
                            <td class="px-4 py-3 text-xs text-slate-500 font-mono">
                                @if ($temporaryAssignment)
                                    {{ $temporaryAssignment->to_date?->format('Y-m-d') }}
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>

                            {{-- Role --}}
                            <td class="px-4 py-3">
                                @if ($user)
                                    <span class="badge {{ $roleColor }}">{{ $roleLabel }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>

                            {{-- Temporary Role --}}
                            <td class="px-4 py-3">
                                @if ($tempRoleColor)
                                    <span class="badge {{ $tempRoleColor }}">{{ $tempRoleLabel }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                                @if ($temporaryAssignment && $temporaryAssignment->temporary_role == 2)
                                    <div class="text-xs text-slate-400 mt-1">Granted by: {{ $temporaryAssignment->grantedBy?->display_name ?? $temporaryAssignment->grantedBy?->name ?? '—' }}</div>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-3 text-sm">
                                <div class="flex items-center gap-2">
                                    {{-- Eye (View Details) --}}
                                    <a href="{{ route('employees.temporary-access.show', $employee) }}"
                                       class="text-slate-500 hover:text-slate-900 transition"
                                       title="View Details">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>

                                    {{-- Edit (Grant / Edit Role) --}}
                                    @if ($user)
                                        <button type="button"
                                                class="text-blue-600 hover:text-blue-800 transition"
                                                title="Grant / Edit Temporary Role"
                                                data-role-id="{{ $employee->id }}"
                                                data-role-name="{{ $employee->full_name }}"
                                                data-role-from="{{ $temporaryAssignment ? ($temporaryAssignment->from_date?->format('Y-m-d\TH:i') ?? '') : '' }}"
                                                data-role-to="{{ $temporaryAssignment ? ($temporaryAssignment->to_date?->format('Y-m-d\TH:i') ?? '') : '' }}">
                                            <i class="ti ti-edit text-base"></i>
                                        </button>
                                    @else
                                        <button type="button"
                                            class="text-slate-400 hover:text-red-500 transition"
                                            title="User Account Required"
                                                data-no-user-id="{{ $employee->id }}"
                                                data-no-user-name="{{ $employee->full_name }}">
                                            <i class="ti ti-edit text-base"></i>
                                        </button>
                                    @endif

                                    {{-- Revoke Temporary Role Assignment --}}
                                    @if ($user && ($isCurrentTemporary || $isScheduled))
                                        <form method="POST" action="{{ route('employees.revoke-role', $employee) }}" class="inline-block" data-confirm="Are you sure you want to revoke this temporary role assignment?" data-confirm-type="danger" data-confirm-text="Revoke" data-confirm-title="Revoke Temporary Access">
                                            @csrf
                                            <button type="submit"
                                                    class="text-red-600 hover:text-red-800 transition"
                                                    title="Revoke Temporary Role">
                                                <i class="ti ti-ban text-base"></i>
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" disabled
                                                class="text-slate-300 cursor-not-allowed"
                                                title="No Active Temporary Assignment">
                                            <i class="ti ti-ban text-base"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <x-table-pagination target="ta-table" itemsPerPage="10" />
        @else
            <div class="px-6 py-12 text-center text-sm text-slate-500">No employees found.</div>
        @endif
    </div>

    {{-- ── No User Account Warning Modal ── --}}
    <div id="noUserWarningModal" class="hidden fixed inset-0 z-40 bg-black/50 p-4 justify-center items-center overflow-y-auto">
        <div class="my-auto w-full max-w-md rounded-xl bg-white shadow-xl max-h-[calc(100vh-2rem)] sm:max-h-[90vh] overflow-y-auto">
            <div class="border-b border-slate-200 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">User Account Required</h3>
            </div>
            <div class="px-6 py-6">
                <div class="mb-4 rounded-lg bg-yellow-50 p-4">
                    <p class="text-sm text-slate-700">
                        <span class="font-semibold">⚠️ Warning:</span> This employee does not have a user account yet. A user account is required to assign a temporary role.
                    </p>
                </div>
                <p class="text-sm text-slate-600 mb-6 font-medium">
                    Please create a user account for this employee first by visiting the Users page.
                </p>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeNoUserModal()" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="button" onclick="redirectToUsers(currentEmployeeId, currentEmployeeName)" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        Go to Users Page
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Grant Temporary Access Modal ── --}}
            <div id="grantRoleModal"
                class="hidden fixed inset-0 z-35 bg-black/40 p-4 justify-center items-center overflow-y-auto">

        <div class="my-auto w-full max-w-md rounded-xl bg-white shadow-xl max-h-[calc(100vh-2rem)] sm:max-h-[90vh] overflow-y-auto">
            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <div>
                    <h3 id="roleModalTitle" class="text-lg font-semibold text-slate-900">Edit Temporary Access</h3>
                    <p id="roleModalSubtitle" class="text-xs text-slate-500 mt-0.5">Assign a temporary system role & validity timeframe</p>
                </div>
                <button type="button" onclick="closeRoleModal()" class="text-slate-400 hover:text-slate-600 transition">
                    <i class="ti ti-x text-xl"></i>
                </button>
            </div>

            {{-- Form --}}
            <form id="grantRoleForm" method="POST" action="" class="p-6 space-y-5">
                @csrf
                @method('PATCH')

                {{-- Employee Search (Autocomplete) --}}
                <div id="roleEmployeeSearchWrap">
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">
                        Employee <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" id="roleEmpSearch" autocomplete="off"
                               placeholder="Search by name or code…"
                               class="w-full rounded-lg border border-slate-300 py-2 pr-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-700 bg-white"
                               style="padding-left:2.25rem">
                        <input type="hidden" id="roleEmpId" name="employee_id">

                        <div id="roleEmpSuggestions"
                             class="hidden absolute left-0 right-0 top-full z-20 mt-1 max-h-48 overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg">
                            @foreach ($allEmployees as $emp)
                                <button type="button" class="ta-suggestion w-full px-4 py-2 text-left text-sm hover:bg-slate-50 text-slate-700 transition"
                                        data-id="{{ $emp->id }}"
                                        data-name="{{ $emp->full_name }}"
                                        data-search="{{ strtolower($emp->full_name . ' ' . $emp->employee_code) }}"
                                        data-role="{{ $emp->user?->role ?? 0 }}">
                                    <span class="font-medium">{{ $emp->full_name }}</span>
                                    <span class="ml-2 font-mono text-xs text-slate-400">{{ $emp->employee_code }}</span>
                                </button>
                            @endforeach
                            <div id="taNoSuggestions" class="hidden px-4 py-3 text-sm text-slate-500 text-center">No employee found.</div>
                        </div>
                    </div>
                </div>

                {{-- Role Selection (Hidden) --}}
                <input type="hidden" name="role" value="2">

                {{-- Date/Time Validity --}}
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-sm font-medium text-slate-700">Timeframe Validity <span class="text-red-500">*</span></label>
                        <label class="inline-flex items-center text-xs font-semibold text-slate-600 cursor-pointer select-none">
                            <input id="sameDayToggle" type="checkbox" checked class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 h-3.5 w-3.5 mr-1.5 transition">
                            Same Day Access
                        </label>
                    </div>
                    @can('assign-temporary-role-with-time')
                        <div class="grid grid-cols-2 gap-3" id="dateGrid">
                            <div>
                                <label for="fromDate" class="block text-xs text-slate-500 mb-1">From (date & time)</label>
                                <input id="fromDate" name="from_date" type="datetime-local" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-700">
                            </div>
                            <div id="toDateContainer">
                                <label for="toDate" class="block text-xs text-slate-500 mb-1">To (date & time)</label>
                                <input id="toDate" name="to_date" type="datetime-local" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-700">
                            </div>
                        </div>
                    @else
                        <div class="grid grid-cols-2 gap-3" id="dateGrid">
                            <div>
                                <label for="fromDate" class="block text-xs text-slate-500 mb-1">From (date)</label>
                                <input id="fromDate" name="from_date" type="date" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-700">
                            </div>
                            <div id="toDateContainer">
                                <label for="toDate" class="block text-xs text-slate-500 mb-1">To (date)</label>
                                <input id="toDate" name="to_date" type="date" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-700">
                            </div>
                        </div>
                    @endcan
                </div>

                {{-- Footer --}}
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeRoleModal()"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit" id="roleSubmitBtn"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                        Grant Role
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const grantRoleUrlTemplate = @json(route('employees.grant-role', ['employee' => '__EMPLOYEE__']));

        function buildGrantRoleUrl(employeeId) {
            return grantRoleUrlTemplate.replace('__EMPLOYEE__', employeeId);
        }

        let currentEmployeeId = null;
        let currentEmployeeName = null;

        /* ── Open from Header Button (no pre-filled employee) ── */
        document.getElementById('openAccessModal').addEventListener('click', function () {
            resetRoleModal();
            document.getElementById('roleModalTitle').textContent = 'Grant Temporary Access';
            document.getElementById('roleSubmitBtn').textContent = 'Grant Role';
            document.getElementById('roleEmployeeSearchWrap').classList.remove('hidden');
            document.getElementById('roleModalSubtitle').textContent = 'Search an employee, then assign a temporary role & timeframe';
            showRoleModal();
        });

        function getFormattedToday(isDateTime) {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');

            if (isDateTime) {
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                return `${year}-${month}-${day}T${hours}:${minutes}`;
            } else {
                return `${year}-${month}-${day}`;
            }
        }

        function updateDateValidityLayout() {
            const sameDayToggle = document.getElementById('sameDayToggle');
            const toDateContainer = document.getElementById('toDateContainer');
            const dateGrid = document.getElementById('dateGrid');
            const fromInput = document.getElementById('fromDate');
            const toInput = document.getElementById('toDate');

            if (sameDayToggle.checked) {
                if (toDateContainer) toDateContainer.classList.add('hidden');
                if (dateGrid) dateGrid.classList.remove('grid-cols-2');
                if (fromInput && toInput) {
                    toInput.value = fromInput.value;
                }
            } else {
                if (toDateContainer) toDateContainer.classList.remove('hidden');
                if (dateGrid) dateGrid.classList.add('grid-cols-2');
            }

            // Sync min attribute of toInput with fromInput value to prevent choosing end dates before start dates
            if (fromInput && toInput) {
                toInput.min = fromInput.value;
            }
        }

        // Add event listeners for same day toggle
        document.getElementById('sameDayToggle').addEventListener('change', updateDateValidityLayout);
        document.getElementById('fromDate').addEventListener('input', function () {
            const sameDayToggle = document.getElementById('sameDayToggle');
            const toInput = document.getElementById('toDate');
            if (sameDayToggle.checked) {
                toInput.value = this.value;
            }
            toInput.min = this.value;
        });

        /* ── Open Per-Row Button ── */
        function openRoleModal(id, name, fromDate, toDate) {
            resetRoleModal();

            document.getElementById('roleModalTitle').textContent = 'Edit Temporary Access';
            document.getElementById('roleSubmitBtn').textContent = 'Save Changes';

            // Hide employee search autocomplete — we already know who it is
            document.getElementById('roleEmployeeSearchWrap').classList.add('hidden');
            document.getElementById('roleModalSubtitle').textContent = name;
            document.getElementById('roleEmpId').value = id;
            document.getElementById('grantRoleForm').action = buildGrantRoleUrl(id);

            const fromInput = document.getElementById('fromDate');
            const toInput = document.getElementById('toDate');

            if (fromDate) {
                fromInput.value = fromInput.type === 'date' ? fromDate.substring(0, 10) : fromDate;
            }
            if (toDate) {
                toInput.value = toInput.type === 'date' ? toDate.substring(0, 10) : toDate;
            }

            const sameDayToggle = document.getElementById('sameDayToggle');
            if (fromInput.value && toInput.value && fromInput.value !== toInput.value) {
                sameDayToggle.checked = false;
            } else {
                sameDayToggle.checked = true;
            }

            // For editing, set min to today's date only if existing fromDate is in the future.
            // If the existing fromDate is in the past, set min to the existing fromDate to avoid browser validation error.
            const isDateTime = fromInput.type === 'datetime-local';
            const todayStr = getFormattedToday(isDateTime);
            if (fromInput.value && fromInput.value < todayStr) {
                fromInput.min = fromInput.value;
            } else {
                fromInput.min = todayStr;
            }

            updateDateValidityLayout();
            showRoleModal();
        }

        function showRoleModal() {
            const m = document.getElementById('grantRoleModal');
            m.classList.remove('hidden');
            m.classList.add('flex');
        }

        function closeRoleModal() {
            const m = document.getElementById('grantRoleModal');
            m.classList.add('hidden');
            m.classList.remove('flex');
            currentEmployeeId = null;
        }

        function resetRoleModal() {
            document.getElementById('roleEmpSearch').value  = '';
            document.getElementById('roleEmpId').value      = '';

            const fromInput = document.getElementById('fromDate');
            const toInput = document.getElementById('toDate');

            const isDateTime = fromInput.type === 'datetime-local';
            const todayStr = getFormattedToday(isDateTime);

            fromInput.value = todayStr;
            toInput.value = todayStr;

            // Limit date pickers to disable past dates by default
            fromInput.min = todayStr;
            toInput.min = todayStr;

            document.getElementById('grantRoleForm').action = '';
            document.getElementById('roleEmpSuggestions').classList.add('hidden');

            const sameDayToggle = document.getElementById('sameDayToggle');
            sameDayToggle.checked = true;
            updateDateValidityLayout();
        }

        /* ── Autocomplete Search Logic ── */
        const searchInput  = document.getElementById('roleEmpSearch');
        const suggestBox   = document.getElementById('roleEmpSuggestions');
        const noSuggest    = document.getElementById('taNoSuggestions');
        const hiddenId     = document.getElementById('roleEmpId');
        const items        = document.querySelectorAll('.ta-suggestion');

        searchInput.addEventListener('input', function () {
            const term = this.value.toLowerCase().trim();
            hiddenId.value = '';
            document.getElementById('grantRoleForm').action = '';

            if (!term) { suggestBox.classList.add('hidden'); return; }

            suggestBox.classList.remove('hidden');
            let found = false;
            items.forEach(item => {
                const match = item.dataset.search.includes(term);
                item.classList.toggle('hidden', !match);
                if (match) found = true;
            });
            noSuggest.classList.toggle('hidden', found);
        });

        // Show pre-populated suggestions when the input is focused or clicked
        function showAllSuggestions() {
            hiddenId.value = '';
            document.getElementById('grantRoleForm').action = '';
            items.forEach(item => item.classList.remove('hidden'));
            noSuggest.classList.add('hidden');
            suggestBox.classList.remove('hidden');
        }

        searchInput.addEventListener('focus', function () {
            if (this.value.trim() === '') showAllSuggestions();
        });

        searchInput.addEventListener('click', function (e) {
            if (this.value.trim() === '') showAllSuggestions();
            e.stopPropagation();
        });

        items.forEach(item => {
            item.addEventListener('click', function () {
                const employeeId = parseInt(this.dataset.id);
                const employeeName = this.dataset.name;
                const userRole = parseInt(this.dataset.role);

                // If user doesn't have an account
                if (userRole === 0) {
                    suggestBox.classList.add('hidden');
                    openNoUserModal(employeeId, employeeName);
                    return;
                }

                searchInput.value  = employeeName;
                hiddenId.value     = employeeId;
                document.getElementById('grantRoleForm').action = buildGrantRoleUrl(employeeId);

                suggestBox.classList.add('hidden');
            });
        });

        // Close suggestions on click outside
        document.addEventListener('click', function (e) {
            if (!searchInput.contains(e.target) && !suggestBox.contains(e.target)) {
                suggestBox.classList.add('hidden');
            }
        });

        /* ── Warning Modal Logic ── */
        function openNoUserModal(id, name) {
            currentEmployeeId = id;
            currentEmployeeName = name;
            const modal = document.getElementById('noUserWarningModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeNoUserModal() {
            const modal = document.getElementById('noUserWarningModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            currentEmployeeId = null;
            currentEmployeeName = null;
        }

        function redirectToUsers(employeeId, employeeName) {
            let url = "{{ route('organization.users.index') }}?action=create";
            if (employeeId) {
                url += `&employee_id=${employeeId}`;
            }
            if (employeeName) {
                url += `&employee_name=${encodeURIComponent(employeeName)}`;
            }
            window.location.href = url;
        }

        function updateTaExportUrl() {
            const q = document.getElementById('ta-search')?.value || '';
            const fromDate = document.getElementById('taFromDate')?.value || '';
            const toDate = document.getElementById('taToDate')?.value || '';
            const positionId = document.getElementById('taPosition')?.value || '';
            const departmentId = document.getElementById('taDepartment')?.value || '';

            let url = "{{ route('employees.temporary-access.export') }}";
            const params = new URLSearchParams();

            if (q) params.set('q', q);
            if (fromDate) params.set('from_date', fromDate);
            if (toDate) params.set('to_date', toDate);
            if (positionId) params.set('position_id', positionId);
            if (departmentId) params.set('department_id', departmentId);

            if ([...params].length) {
                url += '?' + params.toString();
            }

            const exportBtn = document.getElementById('btnTaExport');
            if (exportBtn) exportBtn.href = url;
        }

        function filterTemporaryAccessRows() {
            const term = (document.getElementById('ta-search')?.value || '').trim().toLowerCase();
            document.querySelectorAll('#ta-table .ta-row').forEach((row) => {
                if (!term) {
                    row.style.display = '';
                    return;
                }

                const searchable = row.textContent.toLowerCase();
                row.style.display = searchable.includes(term) ? '' : 'none';
            });
        }

        document.getElementById('ta-search')?.addEventListener('input', function () {
            filterTemporaryAccessRows();
            updateTaExportUrl();
        });

        document.getElementById('taFromDate')?.addEventListener('change', updateTaExportUrl);
        document.getElementById('taToDate')?.addEventListener('change', updateTaExportUrl);
        document.getElementById('taPosition')?.addEventListener('change', updateTaExportUrl);
        document.getElementById('taDepartment')?.addEventListener('change', updateTaExportUrl);

        document.querySelectorAll('[data-role-id]').forEach((button) => {
            button.addEventListener('click', () => {
                openRoleModal(
                    button.dataset.roleId,
                    button.dataset.roleName,
                    button.dataset.roleFrom || '',
                    button.dataset.roleTo || ''
                );
            });
        });

        document.querySelectorAll('[data-no-user-id]').forEach((button) => {
            button.addEventListener('click', () => {
                openNoUserModal(button.dataset.noUserId, button.dataset.noUserName || '');
            });
        });

        filterTemporaryAccessRows();

        updateTaExportUrl();
    </script>
</x-app-layout>
