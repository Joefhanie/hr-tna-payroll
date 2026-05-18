<x-app-layout>
    <x-slot:title>Users</x-slot:title>
    <x-slot:header>Users</x-slot:header>

    <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-[1.65rem] font-bold text-[#06112e]">Users</h1>
            <p class="mt-1 text-sm text-slate-500">Manage user accounts and roles for system access.</p>
        </div>
        <button type="button" onclick="document.getElementById('userFormModal').classList.replace('hidden', 'flex')" class="inline-flex items-center gap-2 rounded-[0.5rem] bg-[#1a56db] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1e40af]">
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
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $user->name }}</td>
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
                                    <a href="{{ route('organization.users.index', ['edit' => $user->id]) }}" class="text-slate-600 hover:text-slate-900 transition">
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
    <div id="userFormModal" class="{{ $errors->any() ? 'flex' : 'hidden' }} fixed inset-0 z-30 items-center justify-center bg-black/40 p-4 transition-opacity" style="padding-left: var(--sidebar-width);">
        <div class="w-full max-w-md rounded-2xl border border-slate-300 bg-white shadow-[0_25px_50px_-12px_rgba(0,0,0,0.45)] ring-4 ring-black/5">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                <h3 class="text-base font-bold text-[#06112e]">Add User</h3>
                <button type="button" onclick="document.getElementById('userFormModal').classList.replace('flex', 'hidden')" class="text-slate-400 transition hover:text-slate-600">
                    <i class="ti ti-x text-xl"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('register.store') }}" class="p-4">
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

                <div class="grid gap-3">
                    <div>
                        <label for="user_name" class="mb-1 block text-[0.75rem] font-bold text-[#06112e]">Full Name</label>
                        <input id="user_name" name="name" type="text" value="{{ old('name') }}" class="w-full rounded-[0.5rem] border border-slate-300 bg-white px-3 py-1.5 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]" placeholder="e.g. John Doe">
                    </div>
                    <div>
                        <label for="user_email" class="mb-1 block text-[0.75rem] font-bold text-[#06112e]">Email</label>
                        <input id="user_email" name="email" type="email" value="{{ old('email') }}" class="w-full rounded-[0.5rem] border border-slate-300 bg-white px-3 py-1.5 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]" placeholder="e.g. john@example.com">
                    </div>
                    <div>
                        <label for="user_username" class="mb-1 block text-[0.75rem] font-bold text-[#06112e]">Username</label>
                        <input id="user_username" name="username" type="text" value="{{ old('username') }}" class="w-full rounded-[0.5rem] border border-slate-300 bg-white px-3 py-1.5 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]" placeholder="e.g. johndoe" pattern="[a-z0-9_-]+" title="Username must contain only lowercase letters, numbers, underscores, and hyphens">
                    </div>
                    <div>
                        <label for="user_role" class="mb-1 block text-[0.75rem] font-bold text-[#06112e]">Role</label>
                        <select id="user_role" name="role" class="w-full rounded-[0.5rem] border border-slate-300 bg-white px-3 py-1.5 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]">
                            <option value="">Select a role</option>
                            <option value="1" @selected(old('role') == 1)>Employee</option>
                            <option value="2" @selected(old('role') == 2)>Supervisor</option>
                            <option value="4" @selected(old('role') == 4)>HR</option>
                        </select>
                    </div>
                    <div>
                        <label for="user_password" class="mb-1 block text-[0.75rem] font-bold text-[#06112e]">Password</label>
                        <input id="user_password" name="password" type="password" class="w-full rounded-[0.5rem] border border-slate-300 bg-white px-3 py-1.5 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]" placeholder="Minimum 8 characters">
                    </div>
                    <div>
                        <label for="user_password_confirm" class="mb-1 block text-[0.75rem] font-bold text-[#06112e]">Confirm Password</label>
                        <input id="user_password_confirm" name="password_confirmation" type="password" class="w-full rounded-[0.5rem] border border-slate-300 bg-white px-3 py-1.5 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]" placeholder="Confirm password">
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('userFormModal').classList.replace('flex', 'hidden')" class="rounded-[0.5rem] border border-slate-200 bg-white px-3 py-1.5 text-sm font-bold text-[#06112e] shadow-sm transition hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-[0.5rem] bg-[#1a56db] px-4 py-1.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#1e40af]">Create User</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
