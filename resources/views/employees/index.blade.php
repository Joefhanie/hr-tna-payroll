<x-app-layout>
    <x-slot:title>Employee Management</x-slot:title>
    <x-slot:header>Employee Management</x-slot:header>

    <div class="flex items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Employees</h1>
            <p class="text-sm text-slate-500">Manage employee records, positions, and departments.</p>
        </div>
        <a href="{{ route('employees.create') }}" class="btn-primary inline-flex items-center gap-2">
            <span>+ Add Employee</span>
        </a>
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
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Code</th>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Position</th>
                        <th class="px-4 py-3">Department</th>
                        <th class="px-4 py-3">Hire Date</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
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
                                <span class="badge {{ match((string) $employee->status) {
                                    '1', 'Active' => 'badge-green',
                                    '2', 'Probationary' => 'badge-blue',
                                    '3', 'On Leave' => 'badge-amber',
                                    '4', 'Resigned', 'Terminated' => 'badge-gray',
                                    default => 'badge-gray',
                                } }}">{{ $employee->status }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('employees.show', $employee) }}" class="text-slate-600 hover:text-slate-900 transition">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                    <a href="{{ route('employees.edit', $employee) }}" class="text-slate-600 hover:text-slate-900 transition">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('employees.destroy', $employee) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this employee?');">
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
</x-app-layout>
