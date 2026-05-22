<x-app-layout>
    <x-slot:title>Users</x-slot:title>
    <x-slot:header>Users</x-slot:header>

    @php
        $createHasEmployeeRecord = old('has_employee_record');
        $editHasEmployeeRecord = old('has_employee_record', isset($editingUser) && $editingUser->employee_id);
        $availableEmployees = $availableEmployees ?? collect();
    @endphp

    <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-[1.65rem] font-bold text-[#06112e]">Users</h1>
            <p class="mt-1 text-sm text-slate-500">Manage user accounts and roles for system access.</p>
        </div>
        <button type="button" onclick="document.getElementById('userFormModal').classList.replace('hidden', 'flex')" class="inline-flex items-center gap-2 rounded-lg bg-[#1a56db] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1e40af]">
            <i class="ti ti-plus text-base"></i>
            Add User
        </button>
    </div>

    <div class="mb-4 h-px w-full bg-slate-200"></div>



    <section class="card p-6">
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Username</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $user->display_name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $user->email }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $user->username }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700">
                                    @switch($user->role)
                                        @case(1)
                                            Employee
                                            @break
                                        @case(2)
                                            Supervisor
                                            @break
                                        @case(3)
                                            OIC
                                            @break
                                        @case(4)
                                            HR
                                            @break
                                        @default
                                            Unknown
                                    @endswitch
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" onclick="openPermissionsModal({{ $user->id }}, '{{ addslashes($user->username) }}', {{ $user->role }}, {{ json_encode($user->permissions ?? null) }})" class="text-slate-600 hover:text-indigo-600 transition" title="Manage Permissions/Access">
                                        <i class="ti ti-shield-lock text-xl"></i>
                                    </button>
                                    <div class="w-px h-4 bg-slate-200 mx-1"></div>
                                    <a href="{{ route('organization.users.edit', $user) }}" class="text-slate-600 hover:text-slate-900 transition" title="Edit User">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">No users yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- Add User Modal -->
    <div id="userFormModal" class="{{ $errors->any() && ! isset($editingUser) ? 'flex' : 'hidden' }} fixed inset-0 z-30 justify-center items-start sm:items-center bg-black/40 p-4 transition-opacity overflow-y-auto" style="padding-left: var(--sidebar-width);">
        <div class="my-auto flex max-h-[calc(100vh-2rem)] sm:max-h-[88vh] w-full max-w-md flex-col overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-[0_25px_50px_-12px_rgba(0,0,0,0.45)] ring-4 ring-black/5">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2.5">
                <h3 class="text-base font-bold text-[#06112e]">Add User</h3>
                <button type="button" onclick="document.getElementById('userFormModal').classList.replace('flex', 'hidden')" class="text-slate-400 transition hover:text-slate-600">
                    <i class="ti ti-x text-xl"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('organization.users.store') }}" class="flex-1 overflow-y-auto px-4 py-3">
                @csrf



                <div class="grid gap-2.5">
                    <div>
                        <label for="user_email" class="mb-1 block text-[0.75rem] font-bold text-[#06112e]">Email</label>
                        <input id="user_email" name="email" type="email" value="{{ old('email') }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]" placeholder="e.g. john@example.com">
                    </div>
                    <div>
                        <label for="user_username" class="mb-1 block text-[0.75rem] font-bold text-[#06112e]">Username</label>
                        <input id="user_username" name="username" type="text" value="{{ old('username') }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]" placeholder="e.g. johndoe" pattern="[a-z0-9_-]+" title="Username must contain only lowercase letters, numbers, underscores, and hyphens">
                    </div>
                    <div>
                        <label for="user_role" class="mb-1 block text-[0.75rem] font-bold text-[#06112e]">Role</label>
                        <select id="user_role" name="role" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]">
                            <option value="">Select a role</option>
                            <option value="1" @selected(old('role') == 1)>Employee</option>
                            <option value="2" @selected(old('role') == 2)>Supervisor</option>
                            <option value="4" @selected(old('role') == 4)>HR</option>
                        </select>
                    </div>
                    <div>
                        <label for="user_password" class="mb-1 block text-[0.75rem] font-bold text-[#06112e]">Password</label>
                        <input id="user_password" name="password" type="password" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]" placeholder="Minimum 8 characters">
                    </div>
                    <div>
                        <label for="user_password_confirm" class="mb-1 block text-[0.75rem] font-bold text-[#06112e]">Confirm Password</label>
                        <input id="user_password_confirm" name="password_confirmation" type="password" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]" placeholder="Confirm password">
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5">
                        <label class="flex items-center gap-2 text-sm font-semibold text-[#06112e]">
                            <input id="user_has_employee_record" name="has_employee_record" type="checkbox" value="1" @checked($createHasEmployeeRecord) class="h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]">
                            This user already has an employee record
                        </label>
                        <p class="mt-1 text-xs text-slate-500">Leave it off to create the user account first, then continue to employee creation.</p>
                    </div>
                    <div id="user_employee_selector" class="{{ $availableEmployees->isNotEmpty() && $createHasEmployeeRecord ? '' : 'hidden' }}">
                        <label for="user_employee_id" class="mb-1 block text-[0.75rem] font-bold text-[#06112e]">Select existing employee</label>
                        <select id="user_employee_id" name="employee_id" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]" @disabled(! $createHasEmployeeRecord || $availableEmployees->isEmpty())>
                            <option value="">Choose an employee</option>
                            @foreach ($availableEmployees as $employee)
                                <option value="{{ $employee->id }}" @selected((string) old('employee_id') === (string) $employee->id)>{{ $employee->full_name }} @if ($employee->employee_code) ({{ $employee->employee_code }}) @endif</option>
                            @endforeach
                        </select>
                    </div>
                    @if ($availableEmployees->isEmpty())
                        <div id="user_employee_redirect_notice" class="{{ $createHasEmployeeRecord ? '' : 'hidden' }} rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                            No employee without a user account is available. Saving will take you to the employee create form.
                        </div>
                    @endif
                </div>

                <div class="mt-4 flex justify-end gap-2 pt-1">
                    <button type="button" onclick="document.getElementById('userFormModal').classList.replace('flex', 'hidden')" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-bold text-[#06112e] shadow-sm transition hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-lg bg-[#1a56db] px-4 py-1.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#1e40af]">Create User</button>
                </div>
            </form>
        </div>
    </div>

    @if (isset($editingUser))
        <div id="editUserModal" class="flex fixed inset-0 z-30 justify-center items-start sm:items-center bg-black/40 p-4 transition-opacity overflow-y-auto" style="padding-left: var(--sidebar-width);">
            <div class="my-auto flex max-h-[calc(100vh-2rem)] sm:max-h-[88vh] w-full max-w-md flex-col overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-[0_25px_50px_-12px_rgba(0,0,0,0.45)] ring-4 ring-black/5">
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2.5">
                    <h3 class="text-base font-bold text-[#06112e]">Edit User</h3>
                    <button type="button" onclick="document.getElementById('editUserModal').classList.replace('flex', 'hidden')" class="text-slate-400 transition hover:text-slate-600">
                        <i class="ti ti-x text-xl"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('organization.users.update', $editingUser) }}" class="flex-1 overflow-y-auto px-4 py-3" onsubmit="document.getElementById('editUserModal').classList.replace('flex', 'hidden')">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-2.5">
                        <div>
                            <label for="edit_user_email" class="mb-1 block text-[0.75rem] font-bold text-[#06112e]">Email</label>
                            <input id="edit_user_email" name="email" type="email" value="{{ old('email', $editingUser->email) }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]">
                        </div>
                        <div>
                            <label for="edit_user_username" class="mb-1 block text-[0.75rem] font-bold text-[#06112e]">Username</label>
                            <input id="edit_user_username" name="username" type="text" value="{{ old('username', $editingUser->username) }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]" pattern="[a-z0-9_-]+" title="Username must contain only lowercase letters, numbers, underscores, and hyphens">
                        </div>
                        <div>
                            <label for="edit_user_role" class="mb-1 block text-[0.75rem] font-bold text-[#06112e]">Role</label>
                            <select id="edit_user_role" name="role" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]">
                                <option value="">Select a role</option>
                                <option value="1" @selected((string) old('role', $editingUser->role) === '1')>Employee</option>
                                <option value="2" @selected((string) old('role', $editingUser->role) === '2')>Supervisor</option>
                                <option value="4" @selected((string) old('role', $editingUser->role) === '4')>HR</option>
                            </select>
                        </div>
                        <div>
                            <label for="edit_user_password" class="mb-1 block text-[0.75rem] font-bold text-[#06112e]">Password</label>
                            <input id="edit_user_password" name="password" type="password" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]" placeholder="Leave blank to keep current password">
                        </div>
                        <div>
                            <label for="edit_user_password_confirm" class="mb-1 block text-[0.75rem] font-bold text-[#06112e]">Confirm Password</label>
                            <input id="edit_user_password_confirm" name="password_confirmation" type="password" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]" placeholder="Confirm password">
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5">
                            <label class="flex items-center gap-2 text-sm font-semibold text-[#06112e]">
                                <input id="edit_user_has_employee_record" name="has_employee_record" type="checkbox" value="1" @checked($editHasEmployeeRecord) class="h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]">
                                This user already has an employee record
                            </label>
                            <p class="mt-1 text-xs text-slate-500">Use this if the account should be linked to an existing employee profile.</p>
                        </div>
                        <div id="edit_user_employee_selector" class="{{ $editHasEmployeeRecord ? '' : 'hidden' }}">
                            <label for="edit_user_employee_id" class="mb-1 block text-[0.75rem] font-bold text-[#06112e]">Select employee</label>
                            <select id="edit_user_employee_id" name="employee_id" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]" @disabled(! $editHasEmployeeRecord)>
                                <option value="">Choose an employee</option>
                                @foreach ($availableEmployees as $employee)
                                    <option value="{{ $employee->id }}" @selected((string) old('employee_id', $editingUser->employee_id) === (string) $employee->id)>{{ $employee->full_name }} @if ($employee->employee_code) ({{ $employee->employee_code }}) @endif</option>
                                @endforeach
                            </select>
                        </div>
                        @if ($availableEmployees->isEmpty() && ! $editingUser->employee_id)
                            <div id="edit_user_employee_redirect_notice" class="{{ $editHasEmployeeRecord ? '' : 'hidden' }} rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                No employee without a user account is available. Saving will take you to the employee create form.
                            </div>
                        @endif
                    </div>

                    <div class="mt-4 flex justify-end gap-2 pt-1">
                        <button type="button" onclick="document.getElementById('editUserModal').classList.replace('flex', 'hidden')" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-bold text-[#06112e] shadow-sm transition hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="rounded-lg bg-[#1a56db] px-4 py-1.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#1e40af]">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- User Permissions Modal -->
    <div id="userPermissionsModal" class="hidden fixed inset-0 z-30 justify-center items-start sm:items-center bg-black/40 p-4 transition-opacity overflow-y-auto" style="padding-left: var(--sidebar-width);">
        <div class="my-auto flex max-h-[calc(100vh-2rem)] sm:max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-[0_25px_50px_-12px_rgba(0,0,0,0.45)] ring-4 ring-black/5">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <div>
                    <h3 class="text-lg font-bold text-[#06112e]">Manage Access & Permissions</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Customize module permissions for user: <span id="perm_username_title" class="font-semibold text-slate-800"></span> <span id="perm_role_badge"></span></p>
                </div>
                <button type="button" onclick="closePermissionsModal()" class="text-slate-400 transition hover:text-slate-600">
                    <i class="ti ti-x text-xl"></i>
                </button>
            </div>

            <form id="userPermissionsForm" method="POST" action="" class="flex-1 overflow-y-auto px-6 py-4">
                @csrf
                @method('PUT')

                <!-- HR Warning Banner -->
                <div id="hr_warning_banner" class="hidden mb-4 rounded-xl border border-blue-200 bg-blue-50 p-3.5 text-xs text-blue-800 flex items-start gap-2.5">
                    <i class="ti ti-info-circle text-lg mt-0.5 shrink-0"></i>
                    <div>
                        <span class="font-bold">Administrator Access:</span> This user is assigned the HR role and automatically has full, unrestricted access to all modules and actions across the system. Individual permissions cannot be disabled.
                    </div>
                </div>

                <!-- Custom Override Info Banner -->
                <div id="custom_override_banner" class="hidden mb-4 rounded-xl border border-amber-200 bg-amber-50 p-3.5 text-xs text-amber-800 flex items-start gap-2.5">
                    <i class="ti ti-alert-triangle text-lg mt-0.5 shrink-0"></i>
                    <div>
                        <span class="font-bold">Custom Role Overrides:</span> This user has customized permissions that override the default access level for their role. Checkboxes showing <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded bg-amber-100 text-[10px] font-bold text-amber-700">Override</span> indicate explicit configuration.
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Employees -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/60">
                        <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3 flex items-center gap-1.5 border-b border-slate-200/50 pb-1.5">
                            <i class="ti ti-users text-base text-indigo-500"></i> Employees
                        </h4>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="employees.view" class="perm-checkbox mt-0.5 h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800">View</span>
                                    <p class="text-[9px] text-slate-500">View profiles & lists</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="employees.create" class="perm-checkbox mt-0.5 h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800">Create</span>
                                    <p class="text-[9px] text-slate-500">Add new employees</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="employees.edit" class="perm-checkbox mt-0.5 h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800">Edit</span>
                                    <p class="text-[9px] text-slate-500">Update profiles & status</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="employees.delete" class="perm-checkbox mt-0.5 h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800">Delete</span>
                                    <p class="text-[9px] text-slate-500">Permanently remove</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Onboarding -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/60">
                        <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3 flex items-center gap-1.5 border-b border-slate-200/50 pb-1.5">
                            <i class="ti ti-user-plus text-base text-indigo-500"></i> Onboarding
                        </h4>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="onboarding.view" class="perm-checkbox mt-0.5 h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800">View</span>
                                    <p class="text-[9px] text-slate-500">View onboarding list</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="onboarding.create" class="perm-checkbox mt-0.5 h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800">Create</span>
                                    <p class="text-[9px] text-slate-500">Add onboarding task</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="onboarding.edit" class="perm-checkbox mt-0.5 h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800">Edit</span>
                                    <p class="text-[9px] text-slate-500">Update onboarding info</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="onboarding.delete" class="perm-checkbox mt-0.5 h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800">Delete</span>
                                    <p class="text-[9px] text-slate-500">Delete tasks/records</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Timekeeping -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/60">
                        <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3 flex items-center gap-1.5 border-b border-slate-200/50 pb-1.5">
                            <i class="ti ti-clock text-base text-indigo-500"></i> Timekeeping
                        </h4>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="timekeeping.view" class="perm-checkbox mt-0.5 h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800">View</span>
                                    <p class="text-[9px] text-slate-500">View schedule & logs</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="timekeeping.create" class="perm-checkbox mt-0.5 h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800">Create</span>
                                    <p class="text-[9px] text-slate-500">Add schedule & manual logs</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="timekeeping.edit" class="perm-checkbox mt-0.5 h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800">Edit</span>
                                    <p class="text-[9px] text-slate-500">Update shifts & settings</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="timekeeping.delete" class="perm-checkbox mt-0.5 h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800">Delete</span>
                                    <p class="text-[9px] text-slate-500">Delete schedules & logs</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Leave -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/60">
                        <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3 flex items-center gap-1.5 border-b border-slate-200/50 pb-1.5">
                            <i class="ti ti-calendar-event text-base text-indigo-500"></i> Leave
                        </h4>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="leaves.view" class="perm-checkbox mt-0.5 h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800">View</span>
                                    <p class="text-[9px] text-slate-500">View leave balances</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="leaves.create" class="perm-checkbox mt-0.5 h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800">Create</span>
                                    <p class="text-[9px] text-slate-500">Request leave requests</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="leaves.edit" class="perm-checkbox mt-0.5 h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800">Edit / Approve</span>
                                    <p class="text-[9px] text-slate-500">Approve/Reject requests</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="leaves.delete" class="perm-checkbox mt-0.5 h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800">Delete</span>
                                    <p class="text-[9px] text-slate-500">Remove leave entries</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Payroll -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/60">
                        <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3 flex items-center gap-1.5 border-b border-slate-200/50 pb-1.5">
                            <i class="ti ti-wallet text-base text-indigo-500"></i> Payroll & Salaries
                        </h4>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="payroll.view" class="perm-checkbox mt-0.5 h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800">View</span>
                                    <p class="text-[9px] text-slate-500">View payslips & salaries</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="payroll.create" class="perm-checkbox mt-0.5 h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800">Create</span>
                                    <p class="text-[9px] text-slate-500">Generate pay runs</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="payroll.edit" class="perm-checkbox mt-0.5 h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800">Edit</span>
                                    <p class="text-[9px] text-slate-500">Update rates & finalize</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="payroll.delete" class="perm-checkbox mt-0.5 h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800">Delete</span>
                                    <p class="text-[9px] text-slate-500">Remove payroll runs</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Benefits -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/60">
                        <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3 flex items-center gap-1.5 border-b border-slate-200/50 pb-1.5">
                            <i class="ti ti-heart text-base text-indigo-500"></i> Benefits
                        </h4>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="benefits.view" class="perm-checkbox mt-0.5 h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800">View</span>
                                    <p class="text-[9px] text-slate-500">View benefit plans</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="benefits.create" class="perm-checkbox mt-0.5 h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800">Create</span>
                                    <p class="text-[9px] text-slate-500">Add plans & enroll</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="benefits.edit" class="perm-checkbox mt-0.5 h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800">Edit</span>
                                    <p class="text-[9px] text-slate-500">Update rates & rules</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="benefits.delete" class="perm-checkbox mt-0.5 h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800">Delete</span>
                                    <p class="text-[9px] text-slate-500">Remove enrollments</p>
                                </div>
                            </label>
                        </div>
                    </div>


                    <!-- Reports -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/60">
                        <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3 flex items-center gap-1.5 border-b border-slate-200/50 pb-1.5">
                            <i class="ti ti-chart-bar text-base text-indigo-500"></i> Reports
                        </h4>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="reports.view" class="perm-checkbox mt-0.5 h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800">View</span>
                                    <p class="text-[9px] text-slate-500">View analytics & charts</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="reports.create" class="perm-checkbox mt-0.5 h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800">Create</span>
                                    <p class="text-[9px] text-slate-500">Generate new reports</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="reports.edit" class="perm-checkbox mt-0.5 h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800">Edit</span>
                                    <p class="text-[9px] text-slate-500">Update report configs</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="reports.delete" class="perm-checkbox mt-0.5 h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800">Delete</span>
                                    <p class="text-[9px] text-slate-500">Remove saved reports</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Self-Service -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/60">
                        <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3 flex items-center gap-1.5 border-b border-slate-200/50 pb-1.5">
                            <i class="ti ti-user-circle text-base text-indigo-500"></i> Self-Service
                        </h4>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="self-service.view" class="perm-checkbox mt-0.5 h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800">View</span>
                                    <p class="text-[9px] text-slate-500">View personal portal</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="self-service.create" class="perm-checkbox mt-0.5 h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800">Create</span>
                                    <p class="text-[9px] text-slate-500">Submit requests</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="self-service.edit" class="perm-checkbox mt-0.5 h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800">Edit</span>
                                    <p class="text-[9px] text-slate-500">Update personal info</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="self-service.delete" class="perm-checkbox mt-0.5 h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800">Delete</span>
                                    <p class="text-[9px] text-slate-500">Cancel requests</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-between items-center border-t border-slate-100 pt-4">
                    <button type="button" id="btnRestoreDefaults" onclick="restoreRoleDefaults()" class="rounded-lg border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-bold text-[#06112e] shadow-sm transition hover:bg-slate-50">Restore Role Defaults</button>
                    <div class="flex gap-2">
                        <button type="button" onclick="closePermissionsModal()" class="rounded-lg border border-slate-200 bg-white px-4 py-1.5 text-sm font-bold text-[#06112e] shadow-sm transition hover:bg-slate-50">Cancel</button>
                        <button type="submit" id="btnSavePermissions" class="rounded-lg bg-[#1a56db] px-4 py-1.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#1e40af]">Save Permissions</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        let activeUserRole = null;
        const roleDefaults = {
            2: [
                'employees.view', 'onboarding.view', 'onboarding.edit',
                'timekeeping.view',
                'leaves.view', 'leaves.create',
                'self-service.view', 'self-service.create'
            ], // Supervisor
            3: [
                'timekeeping.view', 'timekeeping.create', 'timekeeping.edit', 'timekeeping.delete',
                'leaves.view', 'leaves.create', 'leaves.edit', 'leaves.delete',
                'self-service.view', 'self-service.create', 'self-service.edit', 'self-service.delete'
            ], // OIC
            1: [
                'onboarding.view',
                'timekeeping.view',
                'leaves.view', 'leaves.create',
                'self-service.view', 'self-service.create'
            ], // Employee
            4: [] // HR gets everything via backend, no defaults needed
        };

        const roleLabels = {
            1: 'Employee',
            2: 'Supervisor',
            3: 'OIC',
            4: 'HR'
        };

        const roleBadgeClasses = {
            1: 'bg-blue-100 text-blue-700',
            2: 'bg-slate-100 text-slate-700',
            3: 'bg-indigo-100 text-indigo-700',
            4: 'bg-red-100 text-red-700'
        };

        function openPermissionsModal(userId, username, role, customPermissions) {
            activeUserRole = role;
            document.getElementById('perm_username_title').textContent = username;
            
            // Set Badge
            const badgeEl = document.getElementById('perm_role_badge');
            badgeEl.className = 'inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium ' + (roleBadgeClasses[role] || 'bg-slate-100 text-slate-700');
            badgeEl.textContent = roleLabels[role] || 'Unknown';

            // Set Form action
            document.getElementById('userPermissionsForm').action = '/organization/users/' + userId + '/permissions';

            // Show HR Warning or Custom Override Banner
            const hrBanner = document.getElementById('hr_warning_banner');
            const overrideBanner = document.getElementById('custom_override_banner');
            const saveBtn = document.getElementById('btnSavePermissions');
            const restoreBtn = document.getElementById('btnRestoreDefaults');

            hrBanner.classList.add('hidden');
            overrideBanner.classList.add('hidden');
            
            const checkboxes = document.querySelectorAll('.perm-checkbox');

            if (role === 4) {
                // HR
                hrBanner.classList.remove('hidden');
                checkboxes.forEach(cb => {
                    cb.checked = true;
                    cb.disabled = true;
                });
                saveBtn.disabled = true;
                saveBtn.classList.add('opacity-50', 'cursor-not-allowed');
                restoreBtn.disabled = true;
                restoreBtn.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                // Not HR
                saveBtn.disabled = false;
                saveBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                restoreBtn.disabled = false;
                restoreBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                
                // If customPermissions is defined/active, show override banner
                const hasCustom = customPermissions !== null && customPermissions !== undefined;
                if (hasCustom) {
                    overrideBanner.classList.remove('hidden');
                }

                // Check permissions
                const activePerms = hasCustom ? customPermissions : (roleDefaults[role] || []);

                checkboxes.forEach(cb => {
                    cb.disabled = false;
                    cb.checked = activePerms.includes(cb.value);
                });
            }

            // Show Modal
            const modal = document.getElementById('userPermissionsModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closePermissionsModal() {
            const modal = document.getElementById('userPermissionsModal');
            modal.classList.replace('flex', 'hidden');
        }

        function restoreRoleDefaults() {
            if (activeUserRole === 4) return;
            const defaults = roleDefaults[activeUserRole] || [];
            const checkboxes = document.querySelectorAll('.perm-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = defaults.includes(cb.value);
            });
            // Hide custom override banner temporarily until they save or if they click restore
            document.getElementById('custom_override_banner').classList.add('hidden');
        }

        document.addEventListener('DOMContentLoaded', function () {
            function bindEmployeeToggle(checkboxId, selectorId, noticeId) {
                const checkbox = document.getElementById(checkboxId);
                const selector = document.getElementById(selectorId);
                const notice = noticeId ? document.getElementById(noticeId) : null;

                if (!checkbox) return;

                const sync = function () {
                    const checked = checkbox.checked;

                    if (selector) {
                        selector.classList.toggle('hidden', !checked);
                        const select = selector.querySelector('select');
                        if (select) {
                            select.disabled = !checked;
                        }
                    }

                    if (notice) {
                        notice.classList.toggle('hidden', !checked);
                    }
                };

                checkbox.addEventListener('change', sync);
                sync();
            }

            bindEmployeeToggle('user_has_employee_record', 'user_employee_selector', 'user_employee_redirect_notice');
            bindEmployeeToggle('edit_user_has_employee_record', 'edit_user_employee_selector', 'edit_user_employee_redirect_notice');
        });
    </script>
</x-app-layout>
