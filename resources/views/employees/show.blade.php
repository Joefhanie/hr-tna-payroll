<x-app-layout>
    <x-slot:title>Employee Details</x-slot:title>
    <x-slot:header>Employee Details</x-slot:header>

    @php
        $initials = collect([$employee->first_name, $employee->last_name])
            ->filter()
            ->map(fn ($value) => strtoupper(substr($value, 0, 1)))
            ->join('');

        $empLabels = [1 => 'Full-time', 2 => 'Part-time', 3 => 'Contractual', 4 => 'Intern'];
        $empCode = (int) ($employee->employment_type ?? 0);
        $empLabel = $empLabels[$empCode] ?? ($employee->employment_type ?? 'N/A');

        $statusLabels = [1 => 'Regular', 2 => 'Probationary', 3 => 'On Leave', 4 => 'Resigned', 5 => 'Terminated'];
        $statusLabel = $statusLabels[(int) ($employee->status ?? 0)] ?? ($employee->status ?? 'N/A');
    @endphp

    <div class="mb-4 flex items-center justify-between">
        <div>
            <p class="text-slate-600">Viewing the profile for {{ $employee->full_name_with_middle_name }}.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('employees.edit', $employee) }}" class="rounded-lg bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition">Edit Employee</a>
            <a href="{{ route('employees.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">Back</a>
        </div>
    </div>

    <div class="mb-4 rounded-lg bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-14 w-14 items-center justify-center overflow-hidden rounded-full bg-blue-600 text-lg font-bold text-white">
                    @if ($employee->profile_picture)
                        <img src="{{ route('media.file', ['path' => ltrim($employee->profile_picture, '/')]) }}" alt="{{ $employee->full_name_with_middle_name }}" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full w-full items-center justify-center">{{ $initials }}</div>
                    @endif
                </div>
                <div>
                    <h1 class="text-lg font-semibold text-slate-900">{{ $employee->full_name_with_middle_name }}</h1>
                    <p class="text-xs text-slate-600">{{ $employee->email }}</p>
                </div>
            </div>
            <div class="text-xs text-slate-500 text-right">
                <p>{{ $employee->phone ?: 'No phone number' }}</p>
                <p>{{ $employee->birth_date?->format('M d, Y') ?? 'No birth date' }}</p>
            </div>
        </div>
    </div>

    <div class="mb-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-lg bg-white p-4 shadow-sm lg:col-span-2">
            <h2 class="mb-4 text-base font-semibold text-slate-900">Personal Information</h2>
            <div class="grid grid-cols-1 gap-4 text-sm text-slate-600 sm:grid-cols-2">
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Employee Code</p>
                    <p class="mt-1 font-medium text-slate-900">{{ $employee->employee_code ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Name</p>
                    <p class="mt-1 font-medium text-slate-900">{{ $employee->full_name_with_middle_name }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Email</p>
                    <p class="mt-1 font-medium text-slate-900">{{ $employee->email }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Phone</p>
                    <p class="mt-1 font-medium text-slate-900">{{ $employee->phone ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Birth Date</p>
                    <p class="mt-1 font-medium text-slate-900">{{ $employee->birth_date?->format('M d, Y') ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Gender</p>
                    <p class="mt-1 font-medium text-slate-900">{{ $employee->gender ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-lg bg-white p-4 shadow-sm">
            <h2 class="mb-4 text-base font-semibold text-slate-900">Account Summary</h2>
            <div class="space-y-3 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <span class="text-slate-500">Linked User</span>
                    <span class="font-medium text-slate-900">{{ $employee->user?->username ?? 'None' }}</span>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <span class="text-slate-500">Role</span>
                    <span class="font-medium text-slate-900">{{ match((int) ($employee->user?->role ?? 0)) {1 => 'Employee', 2 => 'Supervisor', 4 => 'HR', default => 'N/A'} }}</span>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <span class="text-slate-500">Department</span>
                    <span class="font-medium text-slate-900">{{ $employee->department?->name ?? 'N/A' }}</span>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <span class="text-slate-500">Position</span>
                    <span class="font-medium text-slate-900">{{ $employee->position?->title ?? 'N/A' }}</span>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <span class="text-slate-500">Employment Type</span>
                    <span class="font-medium text-slate-900">{{ $empLabel }}</span>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <span class="text-slate-500">Status</span>
                    <span class="font-medium text-slate-900">{{ $statusLabel }}</span>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <span class="text-slate-500">Hire Date</span>
                    <span class="font-medium text-slate-900">{{ $employee->hire_date?->format('M d, Y') ?? 'N/A' }}</span>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
