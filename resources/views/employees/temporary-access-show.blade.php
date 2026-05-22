<x-app-layout>
    <x-slot:title>Temporary Access Details</x-slot:title>
    <x-slot:header>Temporary Access Details</x-slot:header>

    {{-- ── Header ── --}}
    <div class="mb-6 flex items-center justify-between pb-6 border-b border-slate-200">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">{{ $employee->full_name }}</h1>
            <p class="mt-1 text-sm text-slate-600">Temporary role permissions and scheduled system access history</p>
        </div>
        <a href="{{ route('employees.temporary-access') }}"
           class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
            <i class="ti ti-arrow-left text-base"></i>
            Back to Temporary Access
        </a>
    </div>

    {{-- ── Details Grid ── --}}
    <div class="grid gap-6 lg:grid-cols-3 mb-6">
        <!-- Profile info -->
        <div class="lg:col-span-1 rounded-xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col justify-between">
            <div>
                <h2 class="text-base font-semibold text-slate-900 mb-4">Employee Information</h2>
                <div class="flex items-center gap-4 mb-6">
                    @if ($employee->profile_picture)
                        <div class="h-12 w-12 overflow-hidden rounded-full bg-slate-100">
                            <img src="{{ asset('storage/' . $employee->profile_picture) }}" alt="{{ $employee->full_name }}" class="h-12 w-12 object-cover">
                        </div>
                    @else
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100 text-lg font-semibold text-indigo-700">
                            {{ collect(explode(' ', $employee->full_name))->map(fn($name) => $name[0] ?? '')->join('') }}
                        </div>
                    @endif
                    <div>
                        <h3 class="font-bold text-slate-900 text-lg leading-snug">{{ $employee->full_name }}</h3>
                        <p class="text-xs text-slate-500">{{ $employee->email }}</p>
                    </div>
                </div>

                <dl class="space-y-3.5 text-sm">
                    <div class="flex justify-between border-b border-slate-100 pb-2">
                        <dt class="text-slate-500">Employee Code</dt>
                        <dd class="font-mono text-xs font-semibold text-slate-900">{{ $employee->employee_code }}</dd>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 pb-2">
                        <dt class="text-slate-500">Base Role</dt>
                        <dd>
                            @php
                                $roleLabels = [1 => 'Employee', 2 => 'Supervisor', 3 => 'OIC', 4 => 'HR'];
                                $roleColors = [1 => 'badge-blue', 2 => 'badge-purple', 3 => 'badge-amber', 4 => 'badge-green'];
                                $user = $employee->user;
                                $baseRoleLabel = $user ? ($roleLabels[$user->role] ?? 'Unknown') : 'No User Account';
                                $baseRoleColor = $user ? ($roleColors[$user->role] ?? 'badge-gray') : 'badge-gray';
                            @endphp
                            <span class="badge {{ $baseRoleColor }}">{{ $baseRoleLabel }}</span>
                        </dd>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 pb-2">
                        <dt class="text-slate-500">Department</dt>
                        <dd class="font-medium text-slate-900">{{ $employee->department->name ?? 'N/A' }}</dd>
                    </div>
                    <div class="flex justify-between pb-2">
                        <dt class="text-slate-500">Position</dt>
                        <dd class="font-medium text-slate-900">{{ $employee->position->title ?? 'N/A' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Active Assignment Status -->
        <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col justify-between">
            <div>
                <h2 class="text-base font-semibold text-slate-900 mb-4">Active & Scheduled Assignment</h2>

                @php
                    $tempAssignment = $employee->user?->temporaryAssignments;
                    $temporaryAssignment = $tempAssignment?->where('is_active', true)->first();

                    $isCurrentTemporary = false;
                    $isScheduled = false;
                    $statusLabel = 'None';
                    $statusColor = 'badge-gray';
                    $tempRoleLabel = '—';
                    $tempRoleColor = '';

                    if ($temporaryAssignment) {
                        $now = now();
                        $fromDate = \Carbon\Carbon::parse($temporaryAssignment->from_date);
                        $toDate = \Carbon\Carbon::parse($temporaryAssignment->to_date);

                        if ($now->between($fromDate, $toDate)) {
                            $isCurrentTemporary = true;
                            $statusLabel = 'Active';
                            $statusColor = 'badge-green';
                            $tempRoleLabel = $roleLabels[$temporaryAssignment->temporary_role] ?? 'Role';
                            $tempRoleColor = $roleColors[$temporaryAssignment->temporary_role] ?? 'badge-gray';
                        } elseif ($now->lt($fromDate)) {
                            $isScheduled = true;
                            $statusLabel = 'Scheduled';
                            $statusColor = 'badge-blue';
                            $tempRoleLabel = $roleLabels[$temporaryAssignment->temporary_role] ?? 'Role';
                            $tempRoleColor = $roleColors[$temporaryAssignment->temporary_role] ?? 'badge-gray';
                        }
                    }
                @endphp

                @if ($temporaryAssignment && ($isCurrentTemporary || $isScheduled))
                    <div class="grid gap-6 sm:grid-cols-2">
                        <div class="space-y-4">
                            <div>
                                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Status</span>
                                <span class="badge {{ $statusColor }} text-xs px-2.5 py-1 font-semibold">{{ $statusLabel }}</span>
                            </div>
                            <div>
                                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Temporary Role</span>
                                <span class="badge {{ $tempRoleColor }} text-xs px-2.5 py-1 font-semibold">{{ $tempRoleLabel }}</span>
                            </div>
                            <div>
                                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Assigned By</span>
                                <span class="text-sm font-medium text-slate-900">{{ auth()->user()->name ?? 'System' }}</span>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Validity Start</span>
                                <span class="text-sm font-medium text-slate-900 block">
                                    {{ \Carbon\Carbon::parse($temporaryAssignment->from_date)->format('Y-m-d') }}
                                </span>
                            </div>
                            <div>
                                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Validity End</span>
                                <span class="text-sm font-medium text-slate-900 block">
                                    {{ \Carbon\Carbon::parse($temporaryAssignment->to_date)->format('Y-m-d') }}
                                </span>
                            </div>
                            <div>
                                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1">Original Role</span>
                                <span class="badge {{ $roleColors[$temporaryAssignment->original_role] ?? 'badge-gray' }} text-xs px-2.5 py-1 font-semibold">
                                    {{ $roleLabels[$temporaryAssignment->original_role] ?? 'Role' }}
                                </span>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-12 text-center">
                        <div class="h-12 w-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mb-3">
                            <i class="ti ti-shield-alert text-2xl"></i>
                        </div>
                        <h3 class="text-sm font-semibold text-slate-900">No Active or Scheduled Temporary Role</h3>
                        <p class="text-xs text-slate-500 mt-1 max-w-xs">This employee does not currently have any active or future scheduled temporary supervisor permissions.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── History Table ── --}}
    <div class="card overflow-hidden">
        <div class="border-b border-slate-200 px-6 py-4">
            <h2 class="text-base font-semibold text-slate-900">Assignment Logs & History</h2>
            <p class="text-xs text-slate-500 mt-1">Showing all history of temporary role assignments for {{ $employee->full_name }}</p>
        </div>

        @if ($tempAssignment && $tempAssignment->count() > 0)
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-6 py-3.5">Temporary Role</th>
                        <th class="px-6 py-3.5">Original Role</th>
                        <th class="px-6 py-3.5">From Date</th>
                        <th class="px-6 py-3.5">To Date</th>
                        <th class="px-6 py-3.5">Assigned By</th>
                        <th class="px-6 py-3.5">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($tempAssignment as $assignment)
                        @php
                            $from = \Carbon\Carbon::parse($assignment->from_date);
                            $to = \Carbon\Carbon::parse($assignment->to_date);
                            $now = now();

                            if (!$assignment->is_active) {
                                $historyStatus = 'Revoked';
                                $historyStatusColor = 'badge-red';
                            } elseif ($now->between($from, $to)) {
                                $historyStatus = 'Active';
                                $historyStatusColor = 'badge-green';
                            } elseif ($now->lt($from)) {
                                $historyStatus = 'Scheduled';
                                $historyStatusColor = 'badge-blue';
                            } else {
                                $historyStatus = 'Expired';
                                $historyStatusColor = 'badge-gray';
                            }
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-4">
                                <span class="badge {{ $roleColors[$assignment->temporary_role] ?? 'badge-gray' }}">
                                    {{ $roleLabels[$assignment->temporary_role] ?? 'Role' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="badge {{ $roleColors[$assignment->original_role] ?? 'badge-gray' }}">
                                    {{ $roleLabels[$assignment->original_role] ?? 'Role' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-600 font-medium">
                                {{ $from->format('Y-m-d') }}
                            </td>
                            <td class="px-6 py-4 text-slate-600 font-medium">
                                {{ $to->format('Y-m-d') }}
                            </td>
                            <td class="px-6 py-4 text-slate-600 font-medium">
                                {{ auth()->user()->name ?? 'System' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="badge {{ $historyStatusColor }}">{{ $historyStatus }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="px-6 py-12 text-center text-sm text-slate-500">No temporary access history found.</div>
        @endif
    </div>
</x-app-layout>
