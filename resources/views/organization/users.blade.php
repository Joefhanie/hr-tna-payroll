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
                                    <a href="{{ route('organization.users.edit', $user) }}" class="text-slate-600 hover:text-slate-900 transition">
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
    <div id="userFormModal" class="{{ $errors->any() && ! isset($editingUser) ? 'flex' : 'hidden' }} fixed inset-0 z-30 items-center justify-center bg-black/40 p-4 transition-opacity" style="padding-left: var(--sidebar-width);">
        <div class="flex max-h-[88vh] w-full max-w-md flex-col overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-[0_25px_50px_-12px_rgba(0,0,0,0.45)] ring-4 ring-black/5">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2.5">
                <h3 class="text-base font-bold text-[#06112e]">Add User</h3>
                <button type="button" onclick="document.getElementById('userFormModal').classList.replace('flex', 'hidden')" class="text-slate-400 transition hover:text-slate-600">
                    <i class="ti ti-x text-xl"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('organization.users.store') }}" class="flex-1 overflow-y-auto px-4 py-3">
                @csrf

                @if ($errors->any())
                    <div class="mb-3 rounded-lg border border-red-200 bg-red-50 p-2">
                        <ul class="space-y-0.5 text-[0.75rem] text-red-700">
                            @foreach ($errors->all() as $error)
                                <li>• {{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

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
        <div id="editUserModal" class="flex fixed inset-0 z-30 items-center justify-center bg-black/40 p-4 transition-opacity" style="padding-left: var(--sidebar-width);">
            <div class="flex max-h-[88vh] w-full max-w-md flex-col overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-[0_25px_50px_-12px_rgba(0,0,0,0.45)] ring-4 ring-black/5">
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

    <script>
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
