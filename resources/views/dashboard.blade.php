<x-app-layout>
    <x-slot:title>Dashboard</x-slot:title>
    <x-slot:header>Dashboard</x-slot:header>

    <div class="flex flex-col gap-3 border-b border-slate-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-[2rem] font-semibold tracking-tight text-slate-950">Good morning, Ana</h2>
            <p class="mt-1.5 text-[0.98rem] text-slate-500">Here&apos;s what&apos;s happening with your team today.</p>
        </div>

        <button type="button" class="btn-primary px-5 py-2.5 text-sm">Quick Action</button>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="card p-5">
            <div class="mb-4 flex items-start justify-between">
                <div class="rounded-2xl bg-blue-100 p-3 text-blue-700">
                    <i class="fas fa-users text-xl"></i>
                </div>
                <i class="fas fa-arrow-trend-up text-emerald-500"></i>
            </div>
            <p class="text-[0.98rem] text-slate-600">Total Employees</p>
            <p class="mt-1.5 text-[2rem] font-semibold tracking-tight text-slate-950">{{ $totalEmployees }}</p>
        </div>

        <div class="card p-5">
            <div class="mb-4 flex items-start justify-between">
                <div class="rounded-2xl bg-blue-100 p-3 text-blue-700">
                    <i class="fas fa-user-plus text-xl"></i>
                </div>
                <i class="fas fa-arrow-trend-up text-emerald-500"></i>
            </div>
            <p class="text-[0.98rem] text-slate-600">New Hires</p>
            <p class="mt-1.5 text-[2rem] font-semibold tracking-tight text-slate-950">{{ $newHires }}</p>
            <p class="mt-1.5 text-sm text-slate-500">Onboarding now</p>
        </div>

        <div class="card p-5">
            <div class="mb-4 flex items-start justify-between">
                <div class="rounded-2xl bg-blue-100 p-3 text-blue-700">
                    <i class="fas fa-calendar-days text-xl"></i>
                </div>
                <i class="fas fa-arrow-trend-up text-emerald-500"></i>
            </div>
            <p class="text-[0.98rem] text-slate-600">On Leave Today</p>
            <p class="mt-1.5 text-[2rem] font-semibold tracking-tight text-slate-950">{{ $onLeaveToday }}</p>
            <p class="mt-1.5 text-sm text-slate-500">{{ $leavesPendingApproval }} pending approval</p>
        </div>

        <div class="card p-5">
            <div class="mb-4 flex items-start justify-between">
                <div class="rounded-2xl bg-blue-100 p-3 text-blue-700">
                    <i class="fas fa-wallet text-xl"></i>
                </div>
                <i class="fas fa-arrow-trend-up text-emerald-500"></i>
            </div>
            <p class="text-[0.98rem] text-slate-600">Payroll (current)</p>
            <p class="mt-1.5 text-[2rem] font-semibold tracking-tight text-slate-950">₱{{ number_format($totalPayroll, 0) }}</p>
            <p class="mt-1.5 text-sm text-slate-500">{{ $payrollProcessing }} processing</p>
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1.45fr)_minmax(340px,0.85fr)]">
        <div class="card overflow-hidden p-5">
            <div class="mb-5 flex items-center justify-between">
                <h3 class="text-[1.05rem] font-semibold text-slate-950">Today's Attendance</h3>
                <a href="#" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-slate-900">
                    View all <i class="fas fa-arrow-up-right-from-square text-xs"></i>
                </a>
            </div>

            <div class="space-y-2.5">
                @php
                    $attendanceStatus = [1 => 'Present', 2 => 'Late', 3 => 'Absent', 4 => 'Excused'];
                    $attendanceClasses = [
                        1 => 'badge-green',
                        2 => 'badge-amber',
                        3 => 'badge-red',
                    ];
                @endphp

                @forelse($todayAttendance as $attendance)
                    <div class="flex items-center justify-between rounded-2xl border border-slate-200 px-4 py-3.5">
                        <div>
                            <p class="text-[0.98rem] font-semibold text-slate-950">{{ $attendance->user->name }}</p>
                            <p class="mt-0.5 text-sm text-slate-500">In: {{ $attendance->check_in ? $attendance->check_in->format('H:i') : '—' }} • Out: {{ $attendance->check_out ? $attendance->check_out->format('H:i') : '—' }}</p>
                        </div>
                        @php $s = $attendance->status; @endphp
                        <span class="badge {{ $attendanceClasses[$s] ?? 'badge-gray' }}">
                            {{ $attendanceStatus[$s] ?? 'Unknown' }}
                        </span>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-200 px-5 py-8 text-center text-sm text-slate-500">No attendance records yet.</div>
                @endforelse
            </div>
        </div>

        <div class="card p-5">
            <h3 class="text-[1.05rem] font-semibold text-slate-950">Pending Leave</h3>
            <div class="mt-5 space-y-3.5">
                @forelse($pendingLeaves as $leave)
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <div class="mb-3 flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[0.98rem] font-semibold text-slate-950">{{ $leave->user->name }}</p>
                                <p class="mt-0.5 text-sm text-slate-500">{{ ucfirst($leave->type) }} • {{ $leave->start_date->format('M d') }} – {{ $leave->end_date->format('M d') }}</p>
                            </div>
                            <span class="badge badge-amber">Pending</span>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" class="btn-primary px-4 py-2 text-xs">Approve</button>
                            <button type="button" class="btn-outline px-4 py-2 text-xs">Decline</button>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-200 px-5 py-8 text-center text-sm text-slate-500">No pending leave requests.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
