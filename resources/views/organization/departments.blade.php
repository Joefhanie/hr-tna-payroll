<x-app-layout>
    <x-slot:title>Departments</x-slot:title>
    <x-slot:header>Departments</x-slot:header>

    <div>
        <h1 class="text-2xl font-semibold">Departments</h1>
        <p class="text-sm text-slate-500">Manage department records and parent departments.</p>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4">
            <ul class="space-y-1 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="card p-6">
        <h2 class="text-lg font-semibold text-slate-900">Departments</h2>
        <p class="mt-1 text-sm text-slate-600">Create top-level or child departments.</p>

        <form id="departmentForm" method="POST" action="{{ route('organization.departments.store') }}" class="mt-6 {{ old('name') || old('parent_dept_id') ? '' : 'hidden' }} grid gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
            @csrf
            <div>
                <label for="department_name" class="mb-2 block text-sm font-medium text-slate-700">Department Name</label>
                <input id="department_name" name="name" type="text" value="{{ old('name') }}" class="w-full rounded-lg border-slate-300 focus:border-slate-500 focus:ring-slate-500" placeholder="Human Resources">
            </div>
            <div>
                <label for="parent_dept_id" class="mb-2 block text-sm font-medium text-slate-700">Parent Department</label>
                <select id="parent_dept_id" name="parent_dept_id" class="w-full rounded-lg border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                    <option value="">None</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected(old('parent_dept_id') == $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <button type="submit" class="btn-primary">Add Department</button>
            </div>
        </form>

        <div class="mt-6 overflow-hidden rounded-lg border border-slate-200 bg-white">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Parent</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($departments as $department)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $department->name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $department->parentDepartment->name ?? 'Top Level' }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('organization.departments.show', $department) }}" class="text-slate-600 hover:text-slate-900 transition">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                    <a href="{{ route('organization.departments.edit', $department) }}" class="text-slate-600 hover:text-slate-900 transition">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('organization.departments.destroy', $department) }}" onsubmit="return confirm('Delete this department?');" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 transition">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-sm text-slate-500">No departments yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <script>
        document.getElementById('toggleFormBtn').addEventListener('click', function() {
            const form = document.getElementById('departmentForm');
            form.classList.toggle('hidden');
        });

        document.getElementById('cancelFormBtn').addEventListener('click', function() {
            const form = document.getElementById('departmentForm');
            form.classList.add('hidden');
        });
    </script>
</x-app-layout>
