<x-app-layout>
    <x-slot:title>Departments</x-slot:title>
    <x-slot:header>Departments</x-slot:header>

    <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-[1.65rem] font-bold text-[#06112e]">Departments</h1>
            <p class="mt-1 text-sm text-slate-500">Manage department records and positions.</p>
        </div>
        <div class="flex gap-2">
            <button type="button" onclick="document.getElementById('departmentFormModal').classList.replace('hidden', 'flex')" class="inline-flex items-center gap-2 rounded-[0.5rem] bg-[#1a56db] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1e40af]">
                <i class="ti ti-plus text-base"></i>
                Add Department
            </button>
        </div>
    </div>

    <div class="mb-4 h-px w-full bg-slate-200"></div>

    @if (session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <section class="card p-6">
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3 w-8"></th>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Parent</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($departmentRows as $departmentRow)
                        @php
                            $department = $departmentRow['department'];
                            $departmentEmployees = $department->employees
                                ->sortBy('first_name')
                                ->map(fn ($employee) => [
                                    'name' => $employee->full_name,
                                    'employee_code' => $employee->employee_code,
                                    'email' => $employee->email,
                                ])
                                ->values();
                        @endphp
                        <tr class="group hover:bg-slate-50/50 transition">
                            <td class="px-4 py-3 text-center">
                                <button type="button" onclick="togglePositions({{ $department->id }})" class="text-slate-400 hover:text-slate-700 transition flex items-center justify-center">
                                    <i id="icon-dept-{{ $department->id }}" class="ti ti-chevron-right text-lg"></i>
                                </button>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3" style="padding-left: {{ $departmentRow['depth'] * 1.5 }}rem">
                                    <div>
                                        <span class="font-medium text-slate-900">{{ $department->name }}</span>
                                    </div>
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[0.65rem] font-semibold text-slate-600" title="Positions Count">{{ $department->positions->count() }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $department->parentDepartment->name ?? 'Top Level' }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        type="button"
                                        onclick="openPositionModal({{ $department->id }})"
                                        class="text-indigo-500 hover:text-indigo-700 transition"
                                        title="Add Position"
                                    >
                                        <i class="ti ti-plus text-xl"></i>
                                    </button>

                                    <div class="w-px h-4 bg-slate-200 mx-1"></div>

                                    <button
                                        type="button"
                                        class="flex items-center gap-1.5 text-slate-500 hover:text-slate-700 transition"
                                        title="View Employees"
                                        data-department-name="{{ $departmentRow['path'] }}"
                                        data-parent-name="{{ $department->parentDepartment->name ?? 'Top Level' }}"
                                        data-employee-count="{{ $department->employees_count }}"
                                        data-employees='@json($departmentEmployees)'
                                        onclick="openDepartmentViewModal(this)"
                                    >
                                        <i class="ti ti-users text-lg"></i>
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[0.65rem] font-semibold text-slate-600">{{ $department->employees_count }}</span>
                                    </button>
                                    <a href="{{ route('organization.departments.edit', $department) }}" class="text-slate-500 hover:text-slate-700 transition" title="Edit Department">
                                        <i class="ti ti-edit text-lg"></i>
                                    </a>
                                    <form method="POST" action="{{ route('organization.departments.destroy', $department) }}" onsubmit="return confirm('Delete this department?');" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 transition" title="Delete Department">
                                            <i class="ti ti-trash text-lg"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <tr id="positions-row-{{ $department->id }}" class="hidden bg-slate-50/50">
                            <td colspan="4" class="p-0 border-b border-slate-100">
                                <div class="px-14 py-4">
                                    <div class="rounded-lg border border-slate-200 bg-white">
                                        <div class="border-b border-slate-100 px-4 py-3 flex items-center justify-between bg-slate-50/50">
                                            <h4 class="text-xs font-semibold text-slate-600 uppercase tracking-wide">Positions in {{ $department->name }}</h4>
                                        </div>
                                        @if ($department->positions->count() > 0)
                                            <table class="w-full text-sm">
                                                <thead class="text-left text-xs text-slate-400">
                                                    <tr>
                                                        <th class="px-4 py-2 font-medium">Title</th>
                                                        <th class="px-4 py-2 font-medium">Level</th>
                                                        <th class="px-4 py-2 font-medium">Salary Range</th>
                                                        <th class="px-4 py-2 text-right font-medium">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-50">
                                                    @foreach ($department->positions->sortBy('title') as $position)
                                                        <tr class="hover:bg-slate-50 transition">
                                                            <td class="px-4 py-2.5 font-medium text-slate-800">{{ $position->title }}</td>
                                                            <td class="px-4 py-2.5 text-slate-500">{{ $position->level ?? '-' }}</td>
                                                            <td class="px-4 py-2.5 text-slate-500">
                                                                @if($position->min_salary || $position->max_salary)
                                                                    {{ $position->min_salary ? number_format($position->min_salary, 2) : '0.00' }} - {{ $position->max_salary ? number_format($position->max_salary, 2) : 'Unlimited' }}
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>
                                                            <td class="px-4 py-2.5 text-right">
                                                                <div class="flex items-center justify-end gap-2">
                                                                    <a href="{{ route('organization.positions.edit', $position) }}" class="text-slate-400 hover:text-slate-700 transition">
                                                                        <i class="ti ti-edit text-[1.1rem]"></i>
                                                                    </a>
                                                                    <form method="POST" action="{{ route('organization.positions.destroy', $position) }}" onsubmit="return confirm('Delete this position?');" style="display: inline;">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit" class="text-red-400 hover:text-red-600 transition">
                                                                            <i class="ti ti-trash text-[1.1rem]"></i>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @else
                                            <div class="px-4 py-6 text-center text-sm text-slate-400">
                                                No positions found for this department.
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">No departments yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- Add Department Modal -->
    <div id="departmentFormModal" class="{{ $errors->has('name') || $errors->has('parent_dept_id') ? 'flex' : 'hidden' }} fixed inset-0 z-30 items-center justify-center bg-black/40 p-4 transition-opacity" style="padding-left: var(--sidebar-width);">
        <div class="w-full max-w-md rounded-2xl border border-slate-300 bg-white shadow-[0_25px_50px_-12px_rgba(0,0,0,0.45)] ring-4 ring-black/5">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h3 class="text-lg font-bold text-[#06112e]">Add Department</h3>
                <button type="button" onclick="document.getElementById('departmentFormModal').classList.replace('flex', 'hidden')" class="text-slate-400 transition hover:text-slate-600">
                    <i class="ti ti-x text-xl"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('organization.departments.store') }}" class="p-6">
                @csrf

                @if ($errors->has('name') || $errors->has('parent_dept_id'))
                    <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-3">
                        <ul class="space-y-1 text-[0.8rem] text-red-700">
                            @foreach ($errors->all() as $error)
                                <li>• {{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="grid gap-5">
                    <div>
                        <label for="department_name" class="mb-1.5 block text-[0.8rem] font-bold text-[#06112e]">Department Name</label>
                        <input id="department_name" name="name" type="text" value="{{ old('name') }}" class="w-full rounded-[0.5rem] border border-slate-300 bg-white px-3 py-2 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]" placeholder="e.g. Human Resources">
                    </div>
                    <div>
                        <label for="parent_dept_id" class="mb-1.5 block text-[0.8rem] font-bold text-[#06112e]">Parent Department</label>
                        <select id="parent_dept_id" name="parent_dept_id" class="w-full rounded-[0.5rem] border border-slate-300 bg-white px-3 py-2 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]">
                            <option value="">None (Top Level)</option>
                            @foreach ($departmentRows as $departmentRow)
                                @php
                                    $dept = $departmentRow['department'];
                                @endphp
                                <option value="{{ $dept->id }}" @selected(old('parent_dept_id') == $dept->id)>{{ $departmentRow['path'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-8 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('departmentFormModal').classList.replace('flex', 'hidden')" class="rounded-[0.5rem] border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-[#06112e] shadow-sm transition hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-[0.5rem] bg-[#1a56db] px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-[#1e40af]">Save Department</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Department Modal -->
    <div id="departmentViewModal" class="hidden fixed inset-0 z-30 items-center justify-center bg-black/40 p-4 transition-opacity" style="padding-left: var(--sidebar-width);">
        <div class="flex max-h-[88vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-[0_25px_50px_-12px_rgba(0,0,0,0.45)] ring-4 ring-black/5">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <div>
                    <h3 id="departmentViewTitle" class="text-lg font-bold text-[#06112e]"></h3>
                    <p id="departmentViewSubtitle" class="mt-1 text-sm text-slate-500"></p>
                </div>
                <button type="button" onclick="closeDepartmentViewModal()" class="text-slate-400 transition hover:text-slate-600">
                    <i class="ti ti-x text-xl"></i>
                </button>
            </div>
            <div class="overflow-y-auto px-6 py-4">
                <div id="departmentEmployeesEmpty" class="hidden rounded-lg border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">
                    No employees are assigned to this department.
                </div>
                <div id="departmentEmployeesList" class="hidden overflow-hidden rounded-lg border border-slate-200">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">Employee Code</th>
                                <th class="px-4 py-3">Email</th>
                            </tr>
                        </thead>
                        <tbody id="departmentEmployeesBody" class="divide-y divide-slate-100 bg-white"></tbody>
                    </table>
                </div>
            </div>
            <div class="flex justify-end border-t border-slate-100 px-6 py-4">
                <button type="button" onclick="closeDepartmentViewModal()" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-[#06112e] shadow-sm transition hover:bg-slate-50">Close</button>
            </div>
        </div>
    </div>

    <!-- Add Position Modal -->
    <div id="positionFormModal" class="{{ $errors->has('title') || $errors->has('level') || $errors->has('department_id') || $errors->has('min_salary') || $errors->has('max_salary') ? 'flex' : 'hidden' }} fixed inset-0 z-30 items-center justify-center bg-black/40 p-4 transition-opacity" style="padding-left: var(--sidebar-width);">
        <div class="w-full max-w-md rounded-2xl border border-slate-300 bg-white shadow-[0_25px_50px_-12px_rgba(0,0,0,0.45)] ring-4 ring-black/5">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h3 class="text-lg font-bold text-[#06112e]">Add Position</h3>
                <button type="button" onclick="document.getElementById('positionFormModal').classList.replace('flex', 'hidden')" class="text-slate-400 transition hover:text-slate-600">
                    <i class="ti ti-x text-xl"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('organization.positions.store') }}" class="p-6">
                @csrf

                @if ($errors->has('title') || $errors->has('level') || $errors->has('department_id') || $errors->has('min_salary') || $errors->has('max_salary'))
                    <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-3">
                        <ul class="space-y-1 text-[0.8rem] text-red-700">
                            @foreach ($errors->all() as $error)
                                <li>• {{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="grid gap-5">
                    <div>
                        <label for="position_title" class="mb-1.5 block text-[0.8rem] font-bold text-[#06112e]">Position Title</label>
                        <input id="position_title" name="title" type="text" value="{{ old('title') }}" class="w-full rounded-[0.5rem] border border-slate-300 bg-white px-3 py-2 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]" placeholder="e.g. Payroll Officer">
                    </div>
                    <div>
                        <label for="position_level" class="mb-1.5 block text-[0.8rem] font-bold text-[#06112e]">Level</label>
                        <input id="position_level" name="level" type="text" value="{{ old('level') }}" class="w-full rounded-[0.5rem] border border-slate-300 bg-white px-3 py-2 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]" placeholder="e.g. Senior">
                    </div>
                    <div>
                        <label for="position_department_id" class="mb-1.5 block text-[0.8rem] font-bold text-[#06112e]">Department</label>
                        <select id="position_department_id" name="department_id" class="w-full rounded-[0.5rem] border border-slate-300 bg-white px-3 py-2 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]">
                            <option value="">None (Unassigned)</option>
                            @foreach ($departmentOptions as $department)
                                <option value="{{ $department['id'] }}" @selected(old('department_id') == $department['id'])>{{ $department['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="min_salary" class="mb-1.5 block text-[0.8rem] font-bold text-[#06112e]">Min Salary</label>
                            <input id="min_salary" name="min_salary" type="text" inputmode="decimal" maxlength="10" oninput="this.value = this.value.replace(/[^0-9.]/g, '')" value="{{ old('min_salary') }}" class="w-full rounded-[0.5rem] border border-slate-300 bg-white px-3 py-2 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]">
                        </div>
                        <div>
                            <label for="max_salary" class="mb-1.5 block text-[0.8rem] font-bold text-[#06112e]">Max Salary</label>
                            <input id="max_salary" name="max_salary" type="text" inputmode="decimal" maxlength="10" oninput="this.value = this.value.replace(/[^0-9.]/g, '')" value="{{ old('max_salary') }}" class="w-full rounded-[0.5rem] border border-slate-300 bg-white px-3 py-2 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]">
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('positionFormModal').classList.replace('flex', 'hidden')" class="rounded-[0.5rem] border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-[#06112e] shadow-sm transition hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-[0.5rem] bg-[#1a56db] px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-[#1e40af]">Save Position</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function togglePositions(departmentId) {
            const row = document.getElementById(`positions-row-${departmentId}`);
            const icon = document.getElementById(`icon-dept-${departmentId}`);
            if(row.classList.contains('hidden')) {
                row.classList.remove('hidden');
                icon.classList.replace('ti-chevron-right', 'ti-chevron-down');
            } else {
                row.classList.add('hidden');
                icon.classList.replace('ti-chevron-down', 'ti-chevron-right');
            }
        }

        function openPositionModal(departmentId = '') {
            const modal = document.getElementById('positionFormModal');
            const select = document.getElementById('position_department_id');
            if(select) {
                select.value = departmentId;
            }
            modal.classList.replace('hidden', 'flex');
        }

        function escapeHtml(value) {
            return String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#39;');
        }

        function openDepartmentViewModal(button) {
            const modal = document.getElementById('departmentViewModal');
            const title = document.getElementById('departmentViewTitle');
            const subtitle = document.getElementById('departmentViewSubtitle');
            const employeesBody = document.getElementById('departmentEmployeesBody');
            const list = document.getElementById('departmentEmployeesList');
            const empty = document.getElementById('departmentEmployeesEmpty');

            const departmentName = button.dataset.departmentName || 'Department';
            const parentName = button.dataset.parentName || 'Top Level';
            const employeeCount = Number(button.dataset.employeeCount || 0);
            const employees = JSON.parse(button.dataset.employees || '[]');

            title.textContent = departmentName;
            subtitle.textContent = `Parent: ${parentName} • Employees: ${employeeCount}`;

            if (employees.length === 0) {
                employeesBody.innerHTML = '';
                list.classList.add('hidden');
                empty.classList.remove('hidden');
            } else {
                employeesBody.innerHTML = employees.map((employee) => {
                    return `<tr>
                        <td class="px-4 py-3 font-medium text-slate-900">${escapeHtml(employee.name ?? 'N/A')}</td>
                        <td class="px-4 py-3 text-slate-600">${escapeHtml(employee.employee_code ?? 'N/A')}</td>
                        <td class="px-4 py-3 text-slate-600">${escapeHtml(employee.email ?? 'N/A')}</td>
                    </tr>`;
                }).join('');
                empty.classList.add('hidden');
                list.classList.remove('hidden');
            }

            modal.classList.replace('hidden', 'flex');
        }

        function closeDepartmentViewModal() {
            document.getElementById('departmentViewModal').classList.replace('flex', 'hidden');
        }

        @if ($errors->has('title') || $errors->has('level') || $errors->has('department_id') || $errors->has('min_salary') || $errors->has('max_salary'))
            document.addEventListener('DOMContentLoaded', () => {
                const modal = document.getElementById('positionFormModal');
                if(modal) {
                    modal.classList.replace('hidden', 'flex');
                }
            });
        @endif
    </script>
</x-app-layout>
