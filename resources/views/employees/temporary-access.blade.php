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

    {{-- ── Toolbar ── --}}
    <div class="mb-6 flex items-center gap-3">
        <div class="relative flex-1 max-w-xs bg-white">
            <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
            <input id="ta-search" type="search" placeholder="Search by name or code…"
                   class="w-full rounded-lg border border-slate-300 py-2 pr-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-700 bg-white"
                   style="padding-left:2.25rem" />
        </div>
        <button class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
            Filter
        </button>
        <button class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export
        </button>
    </div>

    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4">
            <ul class="space-y-1 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    {{-- ── Table ── --}}
    <div class="card overflow-hidden">
        @if ($employees->count() > 0)
            <table id="ta-table" class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Code</th>
                        <th class="px-4 py-3">ID</th>
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

                            // Only consider active temporary assignments for table display/actions.
                            // This ensures revoked assignments do not keep showing From/To/Temporary Role.
                            $temporaryAssignment = $user
                                ? $user->temporaryAssignments->where('is_active', true)->sortByDesc('to_date')->first()
                                : null;

                            $now = now();
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
                                $tempRoleLabel = $roleLabels[$temporaryAssignment->temporary_role] ?? 'Role';
                                $tempRoleColor = $roleColors[$temporaryAssignment->temporary_role] ?? 'badge-gray';
                            } elseif ($isScheduled) {
                                $statusLabel = 'Scheduled';
                                $statusColor = 'badge-blue';
                                $tempRoleLabel = $roleLabels[$temporaryAssignment->temporary_role] ?? 'Role';
                                $tempRoleColor = $roleColors[$temporaryAssignment->temporary_role] ?? 'badge-gray';
                            } else {
                                $statusLabel = 'None';
                                $statusColor = 'badge-gray';
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
                            <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $employee->employee_code }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $employee->id }}</td>

                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-semibold">
                                        {{ collect(explode(' ', $employee->full_name))->map(fn($n) => $n[0] ?? '')->join('') }}
                                    </div>
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
                                                onclick="openRoleModal(
                                                    {{ $employee->id }},
                                                    '{{ addslashes($employee->full_name) }}',
                                                    '{{ $temporaryAssignment ? ($temporaryAssignment->from_date?->format('Y-m-d\TH:i') ?? '') : '' }}',
                                                    '{{ $temporaryAssignment ? ($temporaryAssignment->to_date?->format('Y-m-d\TH:i') ?? '') : '' }}'
                                                )">
                                            <i class="ti ti-edit text-base"></i>
                                        </button>
                                    @else
                                        <button type="button"
                                            class="text-slate-400 hover:text-red-500 transition"
                                            title="User Account Required"
                                                onclick="openNoUserModal({{ $employee->id }}, '{{ addslashes($employee->full_name) }}')">
                                            <i class="ti ti-edit text-base"></i>
                                        </button>
                                    @endif

                                    {{-- Revoke Temporary Role Assignment --}}
                                    @if ($user && ($isCurrentTemporary || $isScheduled))
                                        <form method="POST" action="{{ route('employees.revoke-role', $employee) }}" class="inline-block" onsubmit="return confirm('Are you sure you want to revoke this temporary role assignment?');">
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

            <div class="bg-white px-6 py-4 border-t border-slate-200 uppercase">
                {{ $employees->links() }}
            </div>
        @else
            <div class="px-6 py-12 text-center text-sm text-slate-500">No employees found.</div>
        @endif
    </div>

    {{-- ── No User Account Warning Modal ── --}}
    <div id="noUserWarningModal" class="hidden fixed inset-0 z-40 flex items-center justify-center bg-black/50 p-4"
         style="padding-left: var(--sidebar-width, 0);">
        <div class="w-full max-w-md rounded-xl bg-white shadow-xl">
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
         class="hidden fixed inset-0 z-35 items-center justify-center bg-black/40 p-4"
         style="padding-left: var(--sidebar-width, 0);">

        <div class="w-full max-w-md rounded-xl bg-white shadow-xl">
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

                {{-- Role Selection --}}
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Temporary Role <span class="text-red-500">*</span></label>
                    <input type="text" readonly value="Supervisor"
                           class="w-full rounded-lg border border-slate-200 bg-slate-100 px-3 py-2 text-sm text-slate-500 cursor-not-allowed outline-none font-medium">
                    <input type="hidden" name="role" value="2">
                </div>

                {{-- Date/Time Validity --}}
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Timeframe Validity <span class="text-red-500">*</span></label>
                    @can('assign-temporary-role-with-time')
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="fromDate" class="block text-xs text-slate-500 mb-1">From (date & time)</label>
                                <input id="fromDate" name="from_date" type="datetime-local" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-700">
                            </div>
                            <div>
                                <label for="toDate" class="block text-xs text-slate-500 mb-1">To (date & time)</label>
                                <input id="toDate" name="to_date" type="datetime-local" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-700">
                            </div>
                        </div>
                    @else
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="fromDate" class="block text-xs text-slate-500 mb-1">From (date)</label>
                                <input id="fromDate" name="from_date" type="date" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-700">
                            </div>
                            <div>
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

        /* ── Open Per-Row Button ── */
        function openRoleModal(id, name, fromDate, toDate) {
            resetRoleModal();

            document.getElementById('roleModalTitle').textContent = 'Edit Temporary Access';
            document.getElementById('roleSubmitBtn').textContent = 'Save Changes';

            // Hide employee search autocomplete — we already know who it is
            document.getElementById('roleEmployeeSearchWrap').classList.add('hidden');
            document.getElementById('roleModalSubtitle').textContent = name;
            document.getElementById('roleEmpId').value = id;
            document.getElementById('grantRoleForm').action = `/employees/${id}/grant-role`;

            if (fromDate) {
                const fromInput = document.getElementById('fromDate');
                fromInput.value = fromInput.type === 'date' ? fromDate.substring(0, 10) : fromDate;
            }
            if (toDate) {
                const toInput = document.getElementById('toDate');
                toInput.value = toInput.type === 'date' ? toDate.substring(0, 10) : toDate;
            }
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

            document.getElementById('fromDate').value       = '';
            document.getElementById('toDate').value         = '';
            document.getElementById('grantRoleForm').action = '';
            document.getElementById('roleEmpSuggestions').classList.add('hidden');
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
                document.getElementById('grantRoleForm').action = `/employees/${employeeId}/grant-role`;

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
            document.getElementById('noUserWarningModal').classList.remove('hidden');
        }

        function closeNoUserModal() {
            document.getElementById('noUserWarningModal').classList.add('hidden');
            currentEmployeeId = null;
            currentEmployeeName = null;
        }

        function redirectToUsers(employeeId, employeeName) {
            let url = '/organization/users?action=create';
            if (employeeId) {
                url += `&employee_id=${employeeId}`;
            }
            if (employeeName) {
                url += `&employee_name=${encodeURIComponent(employeeName)}`;
            }
            window.location.href = url;
        }

        /* ── Client-side Table Search ── */
        document.getElementById('ta-search').addEventListener('input', function () {
            const term = this.value.toLowerCase();
            document.querySelectorAll('#ta-table .ta-row').forEach(row => {
                const name = row.querySelector('.ta-name')?.textContent.toLowerCase() ?? '';
                const code = row.querySelector('td:first-child')?.textContent.toLowerCase() ?? '';
                row.style.display = (name.includes(term) || code.includes(term)) ? '' : 'none';
            });
        });
    </script>
</x-app-layout>
