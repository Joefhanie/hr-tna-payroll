<x-app-layout>
    <x-slot:title>Employee Details</x-slot:title>
    <x-slot:header>Employee Details</x-slot:header>

    <div class="mb-8 flex items-center justify-between">
        <div>
            <p class="text-slate-600">Viewing the profile for {{ $employee->full_name_with_middle_name }}.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('employees.edit', $employee) }}" class="rounded-lg bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition">Edit Employee</a>
            <a href="{{ route('employees.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">Back</a>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Personal Information</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Employee Code</dt><dd class="font-medium text-slate-900">{{ $employee->employee_code }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Name</dt><dd class="font-medium text-slate-900">{{ $employee->full_name_with_middle_name }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Email</dt><dd class="font-medium text-slate-900">{{ $employee->email }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Phone</dt><dd class="font-medium text-slate-900">{{ $employee->phone ?? 'N/A' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Birth Date</dt><dd class="font-medium text-slate-900">{{ $employee->birth_date?->format('Y-m-d') ?? 'N/A' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Gender</dt><dd class="font-medium text-slate-900">{{ $employee->gender ?? 'N/A' }}</dd></div>
            </dl>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Employment Information</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Department</dt><dd class="font-medium text-slate-900">{{ $employee->department->name ?? 'N/A' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Position</dt><dd class="font-medium text-slate-900">{{ $employee->position->title ?? 'N/A' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Manager</dt><dd class="font-medium text-slate-900">{{ $employee->manager?->full_name_with_middle_name ?? 'N/A' }}</dd></div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Employment Type</dt>
                    @php
                        $empLabels = [1 => 'Full-time', 2 => 'Part-time', 3 => 'Contractual', 4 => 'Intern'];
                        $empCode = (int) ($employee->employment_type ?? 0);
                        $empLabel = $empLabels[$empCode] ?? ($employee->employment_type ?? 'N/A');
                    @endphp
                    <dd class="font-medium text-slate-900">{{ $empLabel }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Status</dt>
                    @php
                        $statusLabels = [1 => 'Active', 2 => 'Probationary', 3 => 'On Leave', 4 => 'Resigned', 5 => 'Terminated'];
                        $statusLabel = $statusLabels[$employee->status] ?? $employee->status;
                    @endphp
                    <dd class="font-medium text-slate-900">{{ $statusLabel }}</dd>
                </div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Hire Date</dt><dd class="font-medium text-slate-900">{{ $employee->hire_date?->format('Y-m-d') ?? 'N/A' }}</dd></div>
            </dl>
        </div>
    </div>
</x-app-layout>
