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

    <!-- Documents Section -->
    <div class="mb-4 rounded-lg bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-base font-semibold text-slate-900">Documents & Records</h2>
        </div>

        <div class="space-y-3">
            @forelse ($employee->documents as $document)
                @php
                    $docName = $document->display_name ?? $document->file_name;
                    $docType = $document->doc_type ?? $document->document_type ?? 'Document';
                    $docDate = optional($document->uploaded_at ?? $document->created_at ?? $document->issued_date)->format('M d, Y');
                    $filePath = $document->file_url ?? $document->file_path ?? null;

                    // Calculate human-readable size
                    $fileSize = $document->file_size_kb ?? $document->file_size ?? null;
                    $fileSizeLabel = null;
                    if ($fileSize !== null) {
                        $kilobytes = (float) $fileSize;
                        if ($document->file_size !== null && $document->file_size_kb === null) {
                            $kilobytes = round($kilobytes / 1024, 2);
                        }
                        $fileSizeLabel = number_format($kilobytes, $kilobytes >= 10 ? 0 : 2) . ' KB';
                    }
                @endphp
                <div class="flex items-center justify-between rounded-lg border border-slate-200 p-4 transition hover:bg-slate-50/30">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $docName }}</p>
                            <p class="text-xs text-slate-500">
                                {{ $docType }} | {{ $docDate ?? 'N/A' }}{{ $fileSizeLabel ? ' | ' . $fileSizeLabel : '' }}
                            </p>
                        </div>
                    </div>
                    @if ($filePath)
                        <div class="flex items-center gap-2">
                            <a href="{{ route('media.file', ['path' => ltrim($filePath, '/')]) }}" target="_blank"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                                <svg class="h-3.5 w-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                View
                            </a>
                            <a href="{{ route('media.file', ['path' => ltrim($filePath, '/'), 'download' => 1]) }}"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                                <svg class="h-3.5 w-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Download
                            </a>
                        </div>
                    @else
                        <span class="text-xs font-medium text-slate-400">No file</span>
                    @endif
                </div>
            @empty
                <div class="rounded-lg border border-dashed border-slate-200 py-10 text-center">
                    <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="mt-2 text-sm font-medium text-slate-500">No documents found for this employee.</p>
                </div>
            @endforelse
        </div>
    </div>

</x-app-layout>
