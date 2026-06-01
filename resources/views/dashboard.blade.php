<x-app-layout>
    <x-slot:title>Dashboard</x-slot:title>
    <x-slot:header>Dashboard</x-slot:header>

    @php
        $firstName = collect(preg_split('/\s+/', trim($user->name ?? 'User')))->first();
        $attendanceStatus = [1 => 'Present', 2 => 'Late', 3 => 'Absent', 4 => 'On Leave'];
        $attendanceClasses = [
            1 => 'badge-green',
            2 => 'badge-amber',
            3 => 'badge-red',
            4 => 'badge-blue',
        ];
        $leaveStatusNames = [1 => 'Pending', 2 => 'Approved', 3 => 'Rejected'];
        $leaveStatusClasses = [1 => 'badge-amber', 2 => 'badge-green', 3 => 'badge-red'];
    @endphp

    @if ($isHrDashboard)
        @php
            $statCards = [
                [
                    'label' => 'Total Employees',
                    'value' => number_format($totalEmployees),
                    'meta' => 'Active headcount',
                    'icon' => 'users',
                    'iconBg' => 'bg-sky-100 text-sky-700',
                    'link' => route('employees.index'),
                ],
                [
                    'label' => 'New Hires',
                    'value' => number_format($newHires),
                    'meta' => 'Joined in the last 30 days',
                    'icon' => 'user-plus',
                    'iconBg' => 'bg-emerald-100 text-emerald-700',
                    'link' => route('employees.index'),
                ],
                [
                    'label' => 'On Leave Today',
                    'value' => number_format($onLeaveToday),
                    'meta' => $leavesPendingApproval . ' pending approval',
                    'icon' => 'calendar-days',
                    'iconBg' => 'bg-amber-100 text-amber-700',
                    'link' => route('leave.index'),
                ],
                [
                    'label' => 'Payroll (current)',
                    'value' => 'PHP ' . number_format($totalPayroll, 0),
                    'meta' => $payrollProcessing . ' processing',
                    'icon' => 'wallet',
                    'iconBg' => 'bg-violet-100 text-violet-700',
                    'link' => route('payroll.index'),
                ],
            ];
        @endphp

        <section class="card overflow-hidden">
            <div class="bg-gradient-to-br from-sky-50 via-white to-slate-100 px-4 py-5 sm:px-6 sm:py-6">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-2xl">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-sky-700">Daily Overview</p>
                        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 sm:text-[2rem]">Welcome, {{ $firstName }}!</h2>
                        <p class="mt-2 max-w-xl text-sm leading-6 text-slate-600 sm:text-[0.98rem]">Here&apos;s what&apos;s happening with your team today, with the most urgent updates pulled forward for quick review on mobile or desktop.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:justify-end">
                        <a href="{{ route('employees.index') }}" class="btn-outline inline-flex min-h-11 items-center justify-center gap-2 px-4 py-3 text-sm font-medium">
                            <i class="ti ti-users text-base"></i>
                            Team
                        </a>
                        <a href="{{ route('timekeeping.index') }}" class="btn-outline inline-flex min-h-11 items-center justify-center gap-2 px-4 py-3 text-sm font-medium">
                            <i class="ti ti-clock text-base"></i>
                            Attendance
                        </a>
                        <a href="{{ route('leave.index') }}" class="btn-primary inline-flex min-h-11 items-center justify-center gap-2 px-4 py-3 text-sm font-semibold">
                            <i class="ti ti-calendar-event text-base"></i>
                            Leave
                        </a>
                        <a href="{{ route('payroll.index') }}" class="btn-outline inline-flex min-h-11 items-center justify-center gap-2 px-4 py-3 text-sm font-medium">
                            <i class="ti ti-wallet text-base"></i>
                            Payroll
                        </a>
                    </div>
                </div>
            </div>

            <div class="grid gap-3 p-4 sm:grid-cols-2 sm:p-5 xl:grid-cols-4">
                @foreach ($statCards as $card)
                    <a href="{{ $card['link'] }}" class="block rounded-3xl border border-slate-200/80 bg-white/80 p-4 shadow-sm hover:shadow hover:border-slate-300 transition duration-200">
                        <div class="flex items-start justify-between gap-3">
                            <div class="rounded-2xl {{ $card['iconBg'] }} p-3">
                                <i class="fas fa-{{ $card['icon'] }} text-lg"></i>
                            </div>
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700">
                                <i class="fas fa-arrow-trend-up text-[10px]"></i>
                                Today
                            </span>
                        </div>
                        <p class="mt-4 text-sm text-slate-500">{{ $card['label'] }}</p>
                        <p class="mt-1 text-2xl font-semibold tracking-tight text-slate-950 sm:text-[2rem]">{{ $card['value'] }}</p>
                        <p class="mt-2 text-xs leading-5 text-slate-500 sm:text-sm">{{ $card['meta'] }}</p>
                    </a>
                @endforeach
            </div>
        </section>

        <div class="grid gap-4 xl:grid-cols-[minmax(0,1.45fr)_minmax(320px,0.85fr)]">
            <div class="card overflow-hidden p-4 sm:p-5">
                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-[1.05rem] font-semibold text-slate-950">Today&apos;s Attendance</h3>
                        <p class="mt-1 text-sm text-slate-500">Latest clock-in and clock-out activity for the day.</p>
                    </div>
                    <a href="{{ route('timekeeping.index') }}" class="inline-flex items-center gap-2 self-start text-sm font-medium text-slate-600 hover:text-slate-900">
                        View all <i class="fas fa-arrow-up-right-from-square text-xs"></i>
                    </a>
                </div>

                <div class="space-y-2.5">
                    @forelse($todayAttendance as $attendance)
                        <div class="rounded-2xl border border-slate-200 px-4 py-3.5">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <p class="truncate text-[0.98rem] font-semibold text-slate-950">{{ $attendance->user->display_name }}</p>
                                    <p class="mt-1 text-sm text-slate-500">In: {{ $attendance->check_in ? $attendance->check_in->format('H:i') : '--' }} <span class="px-1.5 text-slate-300">|</span> Out: {{ $attendance->check_out ? $attendance->check_out->format('H:i') : '--' }}</p>
                                </div>
                                @php $s = $attendance->status; @endphp
                                <span class="badge w-fit {{ $attendanceClasses[$s] ?? 'badge-gray' }}">
                                    {{ $attendanceStatus[$s] ?? 'Unknown' }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 px-5 py-8 text-center text-sm text-slate-500">No attendance records yet.</div>
                    @endforelse
                </div>
            </div>

            <div class="card p-4 sm:p-5">
                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-[1.05rem] font-semibold text-slate-950">Pending Leave</h3>
                        <p class="mt-1 text-sm text-slate-500">Review requests that still need action.</p>
                    </div>
                    <a href="{{ route('leave.index') }}" class="inline-flex items-center gap-2 self-start text-sm font-medium text-slate-600 hover:text-slate-900">
                        View all <i class="fas fa-arrow-up-right-from-square text-xs"></i>
                    </a>
                </div>

                <div class="space-y-3.5">
                    @forelse($pendingLeaves as $leave)
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex flex-col gap-3">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="min-w-0">
                                        <p class="text-[0.98rem] font-semibold text-slate-950">{{ $leave->employee?->full_name ?? 'Unknown' }}</p>
                                        <p class="mt-1 text-sm leading-6 text-slate-500">{{ $leaveTypeNames[$leave->leave_type_id] ?? 'Leave' }} <span class="px-1.5 text-slate-300">|</span> {{ $leave->start_date->format('M d') }} to {{ $leave->end_date->format('M d') }}</p>
                                    </div>
                                    <span class="badge badge-amber w-fit">Pending</span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 sm:flex sm:items-center">
                                    <form action="{{ route('leave.approve', $leave) }}" method="POST" class="sm:w-auto">
                                        @csrf
                                        <button type="submit" title="Approve" aria-label="Approve"
                                            class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 text-sm font-semibold text-emerald-700 shadow-sm transition hover:bg-emerald-100">
                                            <i class="ti ti-check text-lg"></i>
                                            Approve
                                        </button>
                                    </form>
                                    <form action="{{ route('leave.decline', $leave) }}" method="POST" class="sm:w-auto">
                                        @csrf
                                        <button type="submit" title="Decline" aria-label="Decline"
                                            class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 text-sm font-semibold text-rose-700 shadow-sm transition hover:bg-rose-100">
                                            <i class="ti ti-x text-lg"></i>
                                            Decline
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 px-5 py-8 text-center text-sm text-slate-500">No pending leave requests.</div>
                    @endforelse
                </div>
            </div>
        </div>
    @else
        @php
            $employeeRecord = $user?->employee;
            $employeeCode = $employeeRecord?->employee_code ?: ($employeeRecord ? 'EMP-' . str_pad((string) $employeeRecord->id, 4, '0', STR_PAD_LEFT) : null);
            $personalHubHref = auth()->user()->hasPermission('self-service.view') ? route('self-service') : route('profile.show');
            $personalHubLabel = auth()->user()->hasPermission('self-service.view') ? 'Self-Service' : 'Profile';
            $myStatus = $todayAttendanceRecord ? ($attendanceStatus[$todayAttendanceRecord->status] ?? 'Unknown') : 'No Record';
            $myStatusClass = $todayAttendanceRecord ? ($attendanceClasses[$todayAttendanceRecord->status] ?? 'badge-gray') : 'badge-gray';
            $personalStatCards = [
                [
                    'label' => "Today's Attendance",
                    'value' => $myStatus,
                    'meta' => $todayAttendanceRecord
                        ? 'In: ' . ($todayAttendanceRecord->check_in ? $todayAttendanceRecord->check_in->format('H:i') : '--') . ' | Out: ' . ($todayAttendanceRecord->check_out ? $todayAttendanceRecord->check_out->format('H:i') : '--')
                        : 'No attendance logged today',
                    'eyebrow' => 'Today',
                    'icon' => 'clock',
                    'iconBg' => 'bg-sky-100 text-sky-700',
                    'valueClass' => $myStatus === 'No Record' ? 'text-xl' : 'text-2xl',
                    'link' => auth()->user()->hasPermission('timekeeping.view') ? route('timekeeping.index') : '#',
                ],
                [
                    'label' => 'Pending Leaves',
                    'value' => number_format($myPendingLeaves),
                    'meta' => $myApprovedLeaves > 0 ? $myApprovedLeaves . ' active approved leave(s)' : 'No active approved leave',
                    'eyebrow' => 'Leave',
                    'icon' => 'calendar-event',
                    'iconBg' => 'bg-amber-100 text-amber-700',
                    'valueClass' => 'text-2xl',
                    'link' => auth()->user()->hasPermission('leaves.view') ? route('leave.index') : '#',
                ],
                [
                    'label' => 'Profile Updates',
                    'value' => number_format($profileUpdatePending),
                    'meta' => 'Requests awaiting review',
                    'eyebrow' => 'Requests',
                    'icon' => 'edit-circle',
                    'iconBg' => 'bg-violet-100 text-violet-700',
                    'valueClass' => 'text-2xl',
                    'link' => $personalHubHref,
                ],
                [
                    'label' => 'Available Payslips',
                    'value' => number_format($releasedPayslips),
                    'meta' => 'Approved and released payslips',
                    'eyebrow' => 'Payroll',
                    'icon' => 'file-invoice',
                    'iconBg' => 'bg-emerald-100 text-emerald-700',
                    'valueClass' => 'text-2xl',
                    'link' => $personalHubHref . '#payslips',
                ],
            ];
        @endphp

        <section class="card overflow-hidden">
            <div class="bg-gradient-to-br from-sky-50 via-white to-slate-100 px-4 py-5 sm:px-6 sm:py-6">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-2xl">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-sky-700">My Workspace</p>
                        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 sm:text-[2rem]">Good morning, {{ $firstName }}</h2>
                        <p class="mt-2 max-w-xl text-sm leading-6 text-slate-600 sm:text-[0.98rem]">Your dashboard only shows your own attendance, leave, profile requests, and payslip-related activity.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:justify-end">
                        <a href="{{ $personalHubHref }}" class="btn-primary inline-flex min-h-11 items-center justify-center gap-2 px-4 py-3 text-sm font-semibold">
                            <i class="ti ti-user-circle text-base"></i>
                            {{ $personalHubLabel }}
                        </a>
                        @if (auth()->user()->hasPermission('leaves.view'))
                            <a href="{{ route('leave.index') }}" class="btn-outline inline-flex min-h-11 items-center justify-center gap-2 px-4 py-3 text-sm font-medium">
                                <i class="ti ti-calendar-event text-base"></i>
                                Leave
                            </a>
                        @endif
                        @if (auth()->user()->hasPermission('timekeeping.view'))
                            <a href="{{ route('timekeeping.index') }}" class="btn-outline inline-flex min-h-11 items-center justify-center gap-2 px-4 py-3 text-sm font-medium">
                                <i class="ti ti-clock text-base"></i>
                                Timekeeping
                            </a>
                        @endif
                        <button type="button" data-qr-modal-open class="inline-flex min-h-11 items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white/85 px-4 py-3 text-sm font-medium text-slate-700 transition hover:bg-white hover:text-slate-900" @disabled(!$employeeCode)>
                            <i class="ti ti-qrcode text-base"></i>
                            QR Code
                        </button>
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-200/80 p-4 sm:p-5">
                <div class="mb-3 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">At A Glance</p>
                        <p class="text-xs text-slate-500">A quick read of your own records only.</p>
                    </div>
                    <span class="hidden rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600 sm:inline-flex">Personal</span>
                </div>

                <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
                    @foreach ($personalStatCards as $card)
                        <a href="{{ $card['link'] ?? '#' }}" class="block rounded-[1.35rem] border border-slate-200/80 bg-white/90 p-3.5 shadow-sm sm:p-4 hover:shadow hover:border-slate-300 transition duration-200">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-[1rem] {{ $card['iconBg'] }}">
                                    <i class="ti ti-{{ $card['icon'] }} text-[1.05rem]"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">{{ $card['eyebrow'] }}</p>
                                    <p class="mt-0.5 truncate text-lg font-semibold tracking-tight text-slate-950 sm:text-2xl">{{ $card['value'] }}</p>
                                </div>
                            </div>
                            <p class="mt-3 hidden text-[13px] font-medium leading-5 text-slate-700 sm:block">{{ $card['label'] }}</p>
                            <p class="mt-1 hidden text-[11px] leading-4 text-slate-500 sm:block">{{ $card['meta'] }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        <div class="grid gap-4 xl:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.8fr)]">
            <div class="card overflow-hidden p-4 sm:p-5">
                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-[1.05rem] font-semibold text-slate-950">My Attendance</h3>
                        <p class="mt-1 text-sm text-slate-500">Your most recent attendance activity.</p>
                    </div>
                    @if (auth()->user()->hasPermission('timekeeping.view'))
                        <a href="{{ route('timekeeping.index') }}" class="inline-flex items-center gap-2 self-start text-sm font-medium text-slate-600 hover:text-slate-900">
                            Open timekeeping <i class="fas fa-arrow-up-right-from-square text-xs"></i>
                        </a>
                    @endif
                </div>

                <div class="space-y-2.5">
                    @forelse($todayAttendance as $attendance)
                        <div class="rounded-2xl border border-slate-200 px-4 py-3.5">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <p class="truncate text-[0.98rem] font-semibold text-slate-950">{{ optional($attendance->attendance_date)->format('M d, Y') ?? 'Attendance Record' }}</p>
                                    <p class="mt-1 text-sm text-slate-500">In: {{ $attendance->check_in ? $attendance->check_in->format('H:i') : '--' }} <span class="px-1.5 text-slate-300">|</span> Out: {{ $attendance->check_out ? $attendance->check_out->format('H:i') : '--' }}</p>
                                </div>
                                @php $s = $attendance->status; @endphp
                                <span class="badge w-fit {{ $attendanceClasses[$s] ?? 'badge-gray' }}">
                                    {{ $attendanceStatus[$s] ?? 'Unknown' }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 px-5 py-8 text-center text-sm text-slate-500">No attendance records available yet.</div>
                    @endforelse
                </div>
            </div>

            <div class="card p-4 sm:p-5">
                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-[1.05rem] font-semibold text-slate-950">My Leave Requests</h3>
                        <p class="mt-1 text-sm text-slate-500">Only your submitted leave records are shown here.</p>
                    </div>
                    @if (auth()->user()->hasPermission('self-service.view'))
                        <a href="{{ route('self-service') }}" class="inline-flex items-center gap-2 self-start text-sm font-medium text-slate-600 hover:text-slate-900">
                            Open self-service <i class="fas fa-arrow-up-right-from-square text-xs"></i>
                        </a>
                    @endif
                </div>

                <div class="space-y-3.5">
                    @forelse($pendingLeaves as $leave)
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <p class="text-[0.98rem] font-semibold text-slate-950">{{ $leaveTypeNames[$leave->leave_type_id] ?? 'Leave' }}</p>
                                    <p class="mt-1 text-sm leading-6 text-slate-500">{{ $leave->start_date->format('M d') }} to {{ $leave->end_date->format('M d') }}</p>
                                </div>
                                @php $leaveStatus = (int) $leave->status; @endphp
                                <span class="badge w-fit {{ $leaveStatusClasses[$leaveStatus] ?? 'badge-gray' }}">
                                    {{ $leaveStatusNames[$leaveStatus] ?? 'Unknown' }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 px-5 py-8 text-center text-sm text-slate-500">No leave requests found.</div>
                    @endforelse
                </div>
            </div>
        </div>

        @if ($employeeCode)
            <div id="employeeQrModal" class="fixed inset-0 z-50 hidden bg-slate-950/45 p-4 justify-center items-start sm:items-center overflow-y-auto">
                <div class="my-auto w-full max-w-sm rounded-3xl border border-slate-200 bg-white p-5 shadow-2xl max-h-[calc(100vh-2rem)] sm:max-h-[90vh] overflow-y-auto">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Employee QR</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-950">{{ $firstName }}'s Code</h3>
                        </div>
                        <button type="button" data-qr-modal-close class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700">
                            <i class="ti ti-x text-lg"></i>
                        </button>
                    </div>

                    <div class="mt-5 rounded-3xl border border-slate-200 bg-slate-50 px-4 py-5 text-center">
                        <div class="mx-auto flex h-[210px] w-[210px] items-center justify-center rounded-3xl bg-white p-3 shadow-sm">
                            <img
                                src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode($employeeCode) }}"
                                alt="QR code for employee code {{ $employeeCode }}"
                                class="h-[180px] w-[180px]"
                            >
                        </div>
                        <p class="mt-4 text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Employee Code</p>
                        <p class="mt-1 text-lg font-semibold tracking-[0.18em] text-slate-950">{{ $employeeCode }}</p>
                    </div>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const qrModal = document.getElementById('employeeQrModal');

                    if (!qrModal) {
                        return;
                    }

                    const openQrModal = function () {
                        qrModal.classList.remove('hidden');
                        qrModal.classList.add('flex');
                    };

                    const closeQrModal = function () {
                        qrModal.classList.remove('flex');
                        qrModal.classList.add('hidden');
                    };

                    document.querySelectorAll('[data-qr-modal-open]').forEach(function (button) {
                        button.addEventListener('click', openQrModal);
                    });

                    document.querySelectorAll('[data-qr-modal-close]').forEach(function (button) {
                        button.addEventListener('click', closeQrModal);
                    });

                    qrModal.addEventListener('click', function (event) {
                        if (event.target === qrModal) {
                            closeQrModal();
                        }
                    });

                    document.addEventListener('keydown', function (event) {
                        if (event.key === 'Escape') {
                            closeQrModal();
                        }
                    });
                });
            </script>
        @endif
    @endif
</x-app-layout>
