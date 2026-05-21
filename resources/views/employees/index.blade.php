<x-app-layout>
    <x-slot:title>Employee Management</x-slot:title>
    <x-slot:header>Employee Management</x-slot:header>

    <div class="mb-6 flex items-center justify-between pb-6 border-b border-slate-200">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Employees</h1>
            <p class="mt-1 text-sm text-slate-600">Manage employee records, positions, and departments.</p>
        </div>
        @if(auth()->user()->hasPermission('employees.create'))
        <a href="{{ route('employees.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 font-medium text-white shadow-sm hover:bg-blue-700 transition">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Add Employee</span>
        </a>
        @endif
    </div>

    {{-- Tabs removed --}}

    <div class="mb-6 flex items-center gap-3">
        <div class="relative flex-1 max-w-xs bg-white rounded-lg">
            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input id="emp-search" type="search" placeholder="Search by name or code…" class="w-full rounded-lg border-0 py-2 pl-10 pr-3 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-700 bg-white" />
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
    <div class="card overflow-hidden">
        @if ($employees->count() > 0)
            <table id="emp-table" class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Code</th>
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Position</th>
                        <th class="px-4 py-3">Department</th>
                        <th class="px-4 py-3">Hire Date</th>
                        <th class="px-4 py-3">Employment Type</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($employees as $employee)
                        <tr class="emp-row hover:bg-slate-50 transition">
                            <td class="px-4 py-3 font-mono text-xs text-slate-500 emp-code">{{ $employee->employee_code }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $employee->id }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-semibold">
                                        {{ collect(explode(' ', $employee->full_name))->map(fn($name) => $name[0] ?? '')->join('') }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-900 emp-name">{{ $employee->full_name }}</p>
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
                                        5 => 'badge-red',
                                    ];
                                    $badgeColor = $statusColors[$statusCode] ?? 'badge-gray';
                                @endphp
                                <span class="badge {{ $badgeColor }}">{{ $statusLabel }}</span>
                            </td>

                            <td class="px-4 py-3 text-sm">
                                <div class="flex items-center gap-2">
                                    {{-- Eye (View Profile) --}}
                                    <a href="{{ route('employees.show', $employee) }}"
                                       class="text-slate-500 hover:text-slate-900 transition"
                                       title="View Details">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>

                                    {{-- Edit --}}
                                    @if(auth()->user()->hasPermission('employees.edit'))
                                    <a href="{{ route('employees.edit', $employee) }}"
                                       class="text-blue-600 hover:text-blue-800 transition"
                                       title="Edit Employee">
                                        <i class="ti ti-edit text-base"></i>
                                    </a>
                                    @endif

                                    {{-- Terminate --}}
                                    @if(auth()->check() && auth()->user()->role === 4)
                                    <button type="button"
                                            class="text-red-600 hover:text-red-800 transition"
                                            title="Terminate Employee"
                                            onclick="openTerminationModal({{ $employee->id }}, '{{ addslashes($employee->full_name) }}')">
                                        <i class="ti ti-ban text-base"></i>
                                    </button>
                                    @endif
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

    {{-- ── Termination Modal ── --}}
    <div id="terminationModal" class="hidden fixed inset-0 z-40 flex items-center justify-center bg-black/50 p-4"
         style="padding-left: var(--sidebar-width, 0);">
        <div class="w-full max-w-md rounded-xl bg-white shadow-xl">
            <div class="border-b border-slate-200 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Terminate Employee</h3>
                <p class="text-xs text-slate-500 mt-1">This will mark the employee as terminated</p>
            </div>
            <form id="terminationForm" method="POST" action="" class="p-6 space-y-5">
                @csrf
                <input type="hidden" id="terminationEmployeeId" name="employee_id">

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Termination Date <span class="text-red-500">*</span></label>
                    <input type="date" id="terminationDate" name="termination_date" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Termination Reason</label>
                    <input type="text" id="terminationReason" name="termination_reason" placeholder="e.g. Resignation, Retirement"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500">
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                    <button type="submit"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition">
                        Terminate
                    </button>
                    <button type="button" onclick="closeTerminationModal()"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openTerminationModal(employeeId, employeeName) {
            document.getElementById('terminationEmployeeId').value = employeeId;
            document.getElementById('terminationDate').value = '';
            document.getElementById('terminationReason').value = '';
            document.getElementById('terminationForm').action = `/employees/${employeeId}/terminate`;
            document.getElementById('terminationModal').classList.remove('hidden');
            document.getElementById('terminationModal').classList.add('flex');
        }

        function closeTerminationModal() {
            document.getElementById('terminationModal').classList.add('hidden');
            document.getElementById('terminationModal').classList.remove('flex');
        }

        // Close modal on outside click
        document.getElementById('terminationModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeTerminationModal();
            }
        });

        // Client-side Table Search
        document.getElementById('emp-search')?.addEventListener('input', function () {
            const term = this.value.toLowerCase().trim();
            document.querySelectorAll('#emp-table .emp-row').forEach(row => {
                const name = row.querySelector('.emp-name')?.textContent.toLowerCase() ?? '';
                const code = row.querySelector('.emp-code')?.textContent.toLowerCase() ?? '';
                row.style.display = (name.includes(term) || code.includes(term)) ? '' : 'none';
            });
        });
    </script>

</x-app-layout>
