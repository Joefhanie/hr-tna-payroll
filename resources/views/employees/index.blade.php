<x-app-layout>
    <x-slot:title>Employee Management</x-slot:title>
    <x-slot:header>Employee Management</x-slot:header>

    <div class="mb-6 flex items-center justify-between pb-6 border-b border-slate-200">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Employees</h1>
            <p class="mt-1 text-sm text-slate-600">Manage employee records, positions, and departments.</p>
        </div>
        <a href="{{ route('employees.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 font-medium text-white shadow-sm hover:bg-blue-700 transition">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Add Employee</span>
        </a>
    </div>

    <div class="mb-6 flex items-center gap-3">
        <div class="relative flex-1 max-w-xs bg-white rounded-lg">
            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="search" placeholder="Search employees..." class="w-full rounded-lg border-0 py-2 pl-10 pr-3 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500" />
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

    <!-- Employees Table -->
    <div class="card overflow-visible">
        @if ($employees->count() > 0)
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Code</th>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Position</th>
                        <th class="px-4 py-3">Department</th>
                        <th class="px-4 py-3">Hire Date</th>
                        <th class="px-4 py-3">Employment Type</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 overflow-visible">
                    @foreach ($employees as $employee)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $employee->employee_code }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-semibold">
                                        {{ collect(explode(' ', $employee->full_name))->map(fn($name) => $name[0] ?? '')->join('') }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-900">{{ $employee->full_name }}</p>
                                        <p class="text-xs text-slate-500">{{ $employee->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $employee->position->title ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $employee->department->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ optional($employee->hire_date)->format('Y-m-d') ?? 'N/A' }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $empLabels = [1 => 'Full-time', 2 => 'Part-time', 3 => 'Contractual', 4 => 'Intern'];
                                    $empCode = (int) ($employee->employment_type ?? 0);
                                    $empLabel = $empLabels[$empCode] ?? 'N/A';
                                    $empColors = [
                                        1 => 'badge-green',
                                        2 => 'badge-amber',
                                        3 => 'badge-indigo',
                                        4 => 'badge-purple',
                                    ];
                                    $empBadgeColor = $empColors[$empCode] ?? 'badge-gray';
                                @endphp
                                <span class="badge {{ $empBadgeColor }}">{{ $empLabel }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $statusLabels = [1 => 'Active', 2 => 'Probationary', 3 => 'On Leave', 4 => 'Resigned', 5 => 'Terminated'];
                                    $statusCode = (int) ($employee->status ?? 0);
                                    $statusLabel = $statusLabels[$statusCode] ?? 'Unknown';
                                    $statusColors = [
                                        1 => 'badge-green',
                                        2 => 'badge-blue',
                                        3 => 'badge-amber',
                                        4 => 'badge-gray',
                                        5 => 'badge-gray',
                                    ];
                                    $badgeColor = $statusColors[$statusCode] ?? 'badge-gray';
                                @endphp
                                <span class="badge {{ $badgeColor }}">{{ $statusLabel }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $roleLabels = [1 => 'Employee', 2 => 'Supervisor', 4 => 'HR'];
                                    $roleColors = [
                                        1 => 'badge-blue',
                                        2 => 'badge-purple',
                                        4 => 'badge-red',
                                    ];
                                    $userRole = $employee->user?->role;
                                    $temporaryAssignment = $employee->user
                                        ? \App\Models\TemporaryAssignment::where('user_id', $employee->user->id)
                                            ->latest('to_date')
                                            ->first()
                                        : null;

                                    $now = now();
                                    $isScheduledTemporary = $temporaryAssignment
                                        && $temporaryAssignment->is_active
                                        && $temporaryAssignment->from_date
                                        && $temporaryAssignment->from_date->greaterThan($now);
                                    $isCurrentTemporary = $temporaryAssignment
                                        && $temporaryAssignment->is_active
                                        && $temporaryAssignment->from_date
                                        && $temporaryAssignment->to_date
                                        && $now->between($temporaryAssignment->from_date, $temporaryAssignment->to_date);

                                    if ($isCurrentTemporary || $isScheduledTemporary) {
                                        $prefix = $isScheduledTemporary ? 'Scheduled Temporary ' : 'Temporary ';
                                        $roleLabel = $prefix . ($roleLabels[$temporaryAssignment->temporary_role] ?? 'Role');
                                        $roleBadgeColor = $roleColors[$temporaryAssignment->temporary_role] ?? 'badge-gray';
                                    } else {
                                        $roleLabel = $roleLabels[$userRole] ?? 'N/A';
                                        $roleBadgeColor = $roleColors[$userRole] ?? 'badge-gray';
                                    }
                                @endphp
                                <span class="badge {{ $roleBadgeColor }}">{{ $roleLabel }}</span>
                                @if($isCurrentTemporary || $isScheduledTemporary)
                                    <div class="text-xs text-slate-500 mt-1">
                                        {{ $isScheduledTemporary ? 'Scheduled' : 'Temporary' }} from {{ $temporaryAssignment->from_date->format('M d, Y H:i') }} to {{ $temporaryAssignment->to_date->format('M d, Y H:i') }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="relative">
                                    <button type="button" onclick="toggleMenu(this)" class="inline-flex items-center justify-center rounded-lg p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition" title="Actions">
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 8c1.1 0 2-0.9 2-2s-0.9-2-2-2-2 0.9-2 2 0.9 2 2 2zm0 2c-1.1 0-2 0.9-2 2s0.9 2 2 2 2-0.9 2-2-0.9-2-2-2zm0 6c-1.1 0-2 0.9-2 2s0.9 2 2 2 2-0.9 2-2-0.9-2-2-2z"/>
                                        </svg>
                                    </button>
                                    <div data-menu class="hidden absolute right-0 mt-1 w-48 rounded-lg border border-slate-200 bg-white shadow-lg z-50">
                                        <a href="{{ route('employees.show', $employee) }}" class="flex items-center gap-3 px-4 py-3 text-sm text-slate-700 hover:bg-slate-50 first:rounded-t-lg transition">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            <span>View</span>
                                        </a>
                                        <button type="button" onclick="openRoleModal({{ $employee->id }}, '{{ addslashes($employee->full_name) }}', {{ $employee->user?->role ?? 0 }});" class="w-full flex items-center gap-3 px-4 py-3 text-sm text-slate-700 hover:bg-slate-50 transition text-left">
                                            <i class="ti ti-shield-lock text-base"></i>
                                            <span>Grant Role</span>
                                        </button>
                                        <a href="{{ route('employees.edit', $employee) }}" class="flex items-center gap-3 px-4 py-3 text-sm text-slate-700 hover:bg-slate-50 transition">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            <span>Edit</span>
                                        </a>
                                        <form method="POST" action="{{ route('employees.destroy', $employee) }}" class="block" onsubmit="return confirm('Are you sure you want to delete this employee?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 text-sm text-red-600 hover:bg-red-50 last:rounded-b-lg transition text-left">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                                <span>Delete</span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="bg-white px-6 py-4 border-t border-slate-200 uppercase">
                {{ $employees->links() }}
            </div>
        @else
            <div class="px-6 py-12 text-center text-sm text-slate-500">No employees found.</div>
        @endif
    </div>

    <!-- No User Account Warning Modal -->
    <div id="noUserWarningModal" class="hidden fixed inset-0 z-40 flex items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-md rounded-lg bg-white shadow-lg">
            <div class="border-b border-slate-200 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">User Account Required</h3>
            </div>
            <div class="px-6 py-6">
                <div class="mb-4 rounded-lg bg-yellow-50 p-4">
                    <p class="text-sm text-slate-700">
                        <span class="font-semibold">⚠️ Warning:</span> This employee does not have a user account yet. A user account is required to assign a role.
                    </p>
                </div>
                <p class="text-sm text-slate-600 mb-6">
                    Please create a user account for this employee first by visiting the Users page.
                </p>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeNoUserWarning()" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="button" onclick="redirectToUsers(currentEmployeeId, currentEmployeeName)" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        Go to Users Page
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Grant Role Modal -->
    <div id="grantRoleModal" class="hidden fixed inset-0 z-40 flex items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-md rounded-lg bg-white shadow-lg">
            <div class="border-b border-slate-200 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Grant Role</h3>
            </div>
            <form method="POST" id="grantRoleForm" class="px-6 py-4">
                @csrf
                @method('PATCH')
                <div class="mb-4">
                    <label for="roleSelect" class="block text-sm font-medium text-slate-700 mb-2">Select Role</label>
                    <select id="roleSelect" name="role" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="">Choose a role</option>
                        <option value="1">Employee</option>
                        <option value="2">Supervisor</option>
                        <option value="4">HR</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Temporary Assignment</label>
                    @can('assign-temporary-role-with-time')
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="fromDate" class="block text-xs text-slate-500 mb-1">From (date & time)</label>
                                <input id="fromDate" name="from_date" type="datetime-local" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="toDate" class="block text-xs text-slate-500 mb-1">To (date & time)</label>
                                <input id="toDate" name="to_date" type="datetime-local" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            </div>
                        </div>
                        
                    @else
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="fromDate" class="block text-xs text-slate-500 mb-1">From (date)</label>
                                <input id="fromDate" name="from_date" type="date" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="toDate" class="block text-xs text-slate-500 mb-1">To (date)</label>
                                <input id="toDate" name="to_date" type="date" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            </div>
                        </div>
                        
                    @endcan
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeRoleModal()" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        Grant Role
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentEmployeeId = null;

        function toggleMenu(button) {
            const menu = button.nextElementSibling;
            const allMenus = document.querySelectorAll('[data-menu]');
            
            allMenus.forEach(m => {
                if (m !== menu) {
                    m.classList.add('hidden');
                }
            });
            
            menu.classList.toggle('hidden');
            event.stopPropagation();
        }

        // Close all menus when clicking outside
        document.addEventListener('click', function(e) {
            // Check if click is on a menu button or inside an open menu
            const isMenuButton = e.target.closest('button[onclick*="toggleMenu"]');
            const isInsideMenu = e.target.closest('[data-menu]');
            
            // If clicking outside of menu button and menu, close all menus
            if (!isMenuButton && !isInsideMenu) {
                const menus = document.querySelectorAll('[data-menu]');
                menus.forEach(menu => menu.classList.add('hidden'));
            }
        });

        let currentEmployeeName = null;

        function openRoleModal(employeeId, employeeName, currentRole) {
            currentEmployeeId = employeeId;
            currentEmployeeName = employeeName;
            
            // Check if user account exists (currentRole will be 0 if no user account)
            if (currentRole === 0) {
                document.getElementById('noUserWarningModal').classList.remove('hidden');
                return;
            }
            
            document.getElementById('grantRoleForm').action = `/employees/${employeeId}/grant-role`;
            if (currentRole) {
                document.getElementById('roleSelect').value = currentRole;
            }
            document.getElementById('grantRoleModal').classList.remove('hidden');
        }

        function closeRoleModal() {
            document.getElementById('grantRoleModal').classList.add('hidden');
            currentEmployeeId = null;
        }

        function closeNoUserWarning() {
            document.getElementById('noUserWarningModal').classList.add('hidden');
            currentEmployeeId = null;
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

        // Close modal when clicking outside
        document.getElementById('grantRoleModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRoleModal();
            }
        });
    </script>
</x-app-layout>
