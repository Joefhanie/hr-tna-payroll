<x-app-layout>
    <x-slot:title>Leave Calendar</x-slot:title>
    <x-slot:header>Leave Calendar</x-slot:header>

    @php
        $selectedDateCarbon = \Carbon\Carbon::parse($selectedDate);
        $startOfMonth  = $selectedDateCarbon->copy()->startOfMonth();
        $endOfMonth    = $selectedDateCarbon->copy()->endOfMonth();
        $endOfPrevMonth = $startOfMonth->copy()->subDay();
        $daysInMonth   = $endOfMonth->daysInMonth;
        $daysInPrevMonth = $endOfPrevMonth->daysInMonth;
        $firstDayOfWeek = $startOfMonth->dayOfWeek; // 0=Sun
        $monthName = $startOfMonth->format('F');
        $year      = $startOfMonth->format('Y');
        $todayStr  = \Carbon\Carbon::now()->toDateString();
    @endphp

    <style>
        /* Mobile vs Desktop Display for Badges */
        @media (max-width: 639px) {
            .lc-mobile-dots {
                display: flex !important;
            }
            .lc-desktop-badges {
                display: none !important;
            }
        }
        @media (min-width: 640px) {
            .lc-mobile-dots {
                display: none !important;
            }
            .lc-desktop-badges {
                display: flex !important;
            }
        }
    </style>

    {{-- Page Header --}}
    <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-[1.65rem] font-bold text-[#06112e]">Leave Calendar</h1>
            <p class="mt-1 text-sm text-slate-500">View employee availability and leave requests by day.</p>
        </div>
        @if(auth()->user()->hasPermission('leaves.create'))
        <a href="{{ route('leave.index') }}"
           class="inline-flex items-center gap-2 rounded-[0.5rem] border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
            <i class="ti ti-list text-base"></i>
            View Requests
        </a>
        @endif
    </div>

    <div class="mb-4 h-px w-full bg-slate-200"></div>

    {{-- Legend --}}
    <div class="mb-4 flex flex-wrap gap-3 items-center">
        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Legend:</span>
        <span class="inline-flex items-center gap-1.5 text-xs font-semibold" style="color: #92400e;">
            <span class="inline-block h-3 w-3 rounded-full" style="background-color: #fef3c7; border: 1px solid #fde68a;"></span> Pending
        </span>
        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700">
            <span class="inline-block h-3 w-3 rounded-full bg-emerald-500"></span> Approved
        </span>
        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-rose-700">
            <span class="inline-block h-3 w-3 rounded-full bg-rose-400"></span> Rejected
        </span>
    </div>

    {{-- Calendar Layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-12">

        {{-- ── Left Sidebar (Day Detail Panel) ── --}}
        {{-- order-2 on mobile so calendar shows first; order-1 on desktop for left-column position --}}
        <div class="order-2 lg:order-1 lg:col-span-1 bg-[#f8f9fc] border border-slate-200 rounded-2xl shadow-sm flex flex-col overflow-hidden lg:h-[700px]">
            {{-- Date Header --}}
            <div class="p-4 lg:p-8 border-b border-slate-200 bg-white text-center">
                <h2 id="lc-dayNumber" class="text-5xl lg:text-7xl font-black text-[#06112e] tracking-tight">
                    {{ $selectedDateCarbon->format('d') }}
                </h2>
                <p id="lc-dayName" class="text-sm font-bold uppercase tracking-widest text-slate-500 mt-2">
                    {{ $selectedDateCarbon->format('l') }}
                </p>
            </div>

            {{-- Stats Bar --}}
            <div class="grid grid-cols-2 divide-x divide-slate-100 border-b border-slate-100 bg-white">
                <div class="py-3 text-center">
                    <p id="lc-onLeaveCount" class="text-lg font-black text-[#06112e]">0</p>
                    <p class="text-[0.65rem] font-semibold uppercase tracking-wider text-slate-400 mt-0.5">On Leave</p>
                </div>
                <div class="py-3 text-center">
                    <p id="lc-availableCount" class="text-lg font-black text-emerald-600">—</p>
                    <p class="text-[0.65rem] font-semibold uppercase tracking-wider text-slate-400 mt-0.5">Available</p>
                </div>
            </div>

            {{-- Leave List --}}
            <div class="p-5 flex-1 overflow-y-auto">
                <h3 class="text-[0.7rem] font-bold uppercase tracking-wider text-slate-400 mb-4">On Leave This Day</h3>
                <div id="lc-leaveList" class="space-y-3">
                    <div class="text-center py-8">
                        <i class="ti ti-calendar-check text-3xl text-slate-300 mb-2 block"></i>
                        <p class="text-sm text-slate-400">Click a day to view details.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Right: Full Calendar ── --}}
        {{-- order-1 on mobile so calendar appears above sidebar --}}
        <div class="order-1 lg:order-2 lg:col-span-3 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden flex flex-col lg:h-[700px]">

            {{-- Month Nav Header --}}
            <div class="bg-brand-primary px-4 py-4 lg:p-6 flex justify-center items-center" style="color: var(--brand-text-on-primary);">
                <div class="flex items-center justify-between w-full max-w-sm lg:w-[300px]">
                    <a href="{{ route('leave.calendar') }}?date={{ $prevMonthDate }}"
                       class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/40 bg-white/90 text-slate-900 shadow-sm transition hover:bg-slate-900 hover:text-white hover:border-slate-900 shrink-0"
                       title="Previous Month">
                        <i class="ti ti-chevron-left text-lg"></i>
                    </a>
                    <h2 class="text-xl font-bold tracking-wider uppercase flex-1 text-center select-none">
                        {{ $monthName }} {{ $year }}
                    </h2>
                    <a href="{{ route('leave.calendar') }}?date={{ $nextMonthDate }}"
                       class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/40 bg-white/90 text-slate-900 shadow-sm transition hover:bg-slate-900 hover:text-white hover:border-slate-900 shrink-0"
                       title="Next Month">
                        <i class="ti ti-chevron-right text-lg"></i>
                    </a>
                </div>
            </div>

            {{-- Calendar Grid --}}
            <div class="p-6 flex-1 flex flex-col overflow-hidden">

                {{-- Day Headers --}}
                <div class="grid text-center mb-2" style="grid-template-columns: repeat(7, 1fr);">
                    @foreach(['SUN','MON','TUE','WED','THU','FRI','SAT'] as $dh)
                        <div class="text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest">{{ $dh }}</div>
                    @endforeach
                </div>

                {{-- Day Cells --}}
                <div class="grid flex-1 border-t border-l border-slate-100 rounded-lg overflow-hidden"
                     style="grid-template-columns: repeat(7, 1fr);">

                    {{-- Prev-month filler cells --}}
                    @for ($i = 0; $i < $firstDayOfWeek; $i++)
                        <div class="bg-slate-50 border-r border-b border-slate-100 p-1 lg:p-2 min-h-[48px] sm:min-h-[70px] lg:min-h-[80px] flex items-start justify-center pt-2 lg:pt-4">
                            <span class="inline-flex h-6 w-6 sm:h-7 sm:w-7 lg:h-8 lg:w-8 items-center justify-center rounded-full text-xs sm:text-sm font-semibold text-slate-300">
                                {{ $daysInPrevMonth - $firstDayOfWeek + $i + 1 }}
                            </span>
                        </div>
                    @endfor

                    {{-- Current-month cells --}}
                    @for ($day = 1; $day <= $daysInMonth; $day++)
                        @php
                            $cellDate    = $startOfMonth->copy()->addDays($day - 1);
                            $cellDateStr = $cellDate->toDateString();
                            $isToday     = $todayStr === $cellDateStr;
                            $dayLeaves   = $calendarData[$cellDateStr] ?? [];
                            $pending     = collect($dayLeaves)->where('status', 1)->count();
                            $approved    = collect($dayLeaves)->where('status', 2)->count();
                            $rejected    = collect($dayLeaves)->where('status', 3)->count();
                            $total       = count($dayLeaves);
                        @endphp
                        <div data-date="{{ $cellDateStr }}"
                             data-day="{{ $day }}"
                             data-dayname="{{ strtoupper($cellDate->format('l')) }}"
                             class="lc-day-cell bg-white border-r border-b border-slate-100 p-1 lg:p-2 cursor-pointer hover:bg-[#f0f4ff] transition group relative flex flex-col items-center pt-2 lg:pt-4 min-h-[48px] sm:min-h-[70px] lg:min-h-[80px]"
                             onclick="lcSelectDate('{{ $cellDateStr }}', {{ $day }}, '{{ strtoupper($cellDate->format('l')) }}', this)">

                            {{-- Day number --}}
                            <span class="inline-flex h-6 w-6 sm:h-7 sm:w-7 lg:h-8 lg:w-8 items-center justify-center rounded-full text-xs sm:text-sm font-semibold
                                {{ $isToday ? 'bg-indigo-600 text-white' : 'text-slate-700 group-hover:text-indigo-600' }}">
                                {{ $day }}
                            </span>

                            {{-- Status indicators --}}
                            @if($total > 0)
                                {{-- Mobile (<sm): tiny colored dots in a row below the day number, no overlap --}}
                                <div class="lc-mobile-dots items-center justify-center gap-0.5 mt-1">
                                    @if($approved > 0)
                                        <span class="inline-block h-[5px] w-[5px] rounded-full bg-emerald-500"
                                              title="Approved: {{ $approved }}"></span>
                                    @endif
                                    @if($pending > 0)
                                        <span class="inline-block h-[5px] w-[5px] rounded-full border border-amber-400"
                                              style="background-color:#fef3c7;"
                                              title="Pending: {{ $pending }}"></span>
                                    @endif
                                    @if($rejected > 0)
                                        <span class="inline-block h-[5px] w-[5px] rounded-full bg-rose-400"
                                              title="Rejected: {{ $rejected }}"></span>
                                    @endif
                                </div>

                                {{-- Tablet/Desktop (sm+): numbered corner badges, absolutely positioned --}}
                                <div class="lc-desktop-badges absolute right-1 top-1 lg:right-2 lg:top-2 flex-col gap-0.5">
                                    @if($approved > 0)
                                        <span class="inline-flex h-4 w-4 lg:h-5 lg:w-5 items-center justify-center rounded-full bg-emerald-500 text-[0.55rem] lg:text-[0.62rem] font-bold text-white shadow-sm"
                                              title="Approved: {{ $approved }}">{{ $approved }}</span>
                                    @endif
                                    @if($pending > 0)
                                        <span class="inline-flex h-4 w-4 lg:h-5 lg:w-5 items-center justify-center rounded-full text-[0.55rem] lg:text-[0.62rem] font-bold shadow-sm"
                                              style="background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a;"
                                              title="Pending: {{ $pending }}">{{ $pending }}</span>
                                    @endif
                                    @if($rejected > 0)
                                        <span class="inline-flex h-4 w-4 lg:h-5 lg:w-5 items-center justify-center rounded-full bg-rose-400 text-[0.55rem] lg:text-[0.62rem] font-bold text-white shadow-sm"
                                              title="Rejected: {{ $rejected }}">{{ $rejected }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endfor

                    {{-- Next-month filler cells --}}
                    @php $remaining = (7 - (($firstDayOfWeek + $daysInMonth) % 7)) % 7; @endphp
                    @for ($i = 0; $i < $remaining; $i++)
                        <div class="bg-slate-50 border-r border-b border-slate-100 p-1 lg:p-2 min-h-[48px] sm:min-h-[70px] lg:min-h-[80px] flex items-start justify-center pt-2 lg:pt-4">
                            <span class="inline-flex h-6 w-6 sm:h-7 sm:w-7 lg:h-8 lg:w-8 items-center justify-center rounded-full text-xs sm:text-sm font-semibold text-slate-300">
                                {{ $i + 1 }}
                            </span>
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    </div>

    <x-slot:scripts>
    <script>
    // Full calendar data keyed by YYYY-MM-DD
    const lcCalendarData = @json($calendarData);
    const lcAvailableCounts = @json($availableCounts);

    // Status config
    const lcStatusCfg = {
        1: { label: 'Pending',  cls: 'bg-amber-100 text-amber-800 border-amber-200' },
        2: { label: 'Approved', cls: 'bg-emerald-100 text-emerald-800 border-emerald-200' },
        3: { label: 'Rejected', cls: 'bg-rose-100 text-rose-800 border-rose-200' },
    };

    function lcSelectDate(dateStr, dayNum, dayName, el) {
        // Update header
        document.getElementById('lc-dayNumber').textContent = dayNum;
        document.getElementById('lc-dayName').textContent   = dayName;

        // Reset all cells
        document.querySelectorAll('.lc-day-cell').forEach(c => {
            c.classList.remove('bg-blue-50/70', 'border-blue-300', 'z-10');
            c.classList.add('bg-white', 'border-slate-100');
        });

        // Activate selected cell
        if (!el) el = document.querySelector(`.lc-day-cell[data-date="${dateStr}"]`);
        if (el) {
            el.classList.remove('bg-white', 'border-slate-100');
            el.classList.add('bg-blue-50/70', 'border-blue-300', 'z-10');
        }

        const leaves   = lcCalendarData[dateStr] || [];
        const approved = leaves.filter(l => l.status === 2);
        const pending  = leaves.filter(l => l.status === 1);
        const other    = leaves.filter(l => l.status !== 1 && l.status !== 2);

        // Stats bar — only count approved as "on leave"
        const onLeave    = approved.length + pending.length;
        const available  = lcAvailableCounts[dateStr] !== undefined ? lcAvailableCounts[dateStr] : 0;

        document.getElementById('lc-onLeaveCount').textContent   = onLeave;
        document.getElementById('lc-availableCount').textContent = available;

        const list = document.getElementById('lc-leaveList');
        list.innerHTML = '';

        if (leaves.length === 0) {
            list.innerHTML = `
                <div class="text-center py-8">
                    <i class="ti ti-calendar-check text-3xl text-slate-300 mb-2 block"></i>
                    <p class="text-sm text-slate-400">All employees available.</p>
                </div>`;
            return;
        }

        // Sort: approved first, then pending, then rejected
        const sorted = [...approved, ...pending, ...other];

        sorted.forEach(leave => {
            const cfg  = lcStatusCfg[leave.status] || { label: 'Unknown', cls: 'bg-slate-100 text-slate-700 border-slate-200' };
            const card = document.createElement('div');
            card.className = 'bg-white border border-slate-200 rounded-xl p-3.5 shadow-sm transition hover:shadow-md';
            card.innerHTML = `
                <p class="text-sm font-bold text-[#06112e] leading-tight">${leave.employee}</p>
                <p class="text-[0.72rem] text-slate-500 mt-0.5">${leave.type}</p>
                <div class="mt-2 flex items-center justify-between">
                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[0.65rem] font-bold uppercase tracking-wider ${cfg.cls}">
                        ${cfg.label}
                    </span>
                    <span class="text-[0.7rem] text-slate-400">${leave.days} day${leave.days !== 1 ? 's' : ''}</span>
                </div>`;
            list.appendChild(card);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Auto-select today or the first day of the month
        const todayStr   = '{{ \Carbon\Carbon::now()->toDateString() }}';
        const monthStr   = '{{ $startOfMonth->toDateString() }}';
        const initialDate = lcCalendarData[todayStr] !== undefined ? todayStr : monthStr;
        const initialEl   = document.querySelector(`.lc-day-cell[data-date="${initialDate}"]`);

        if (initialEl) {
            const dayNum  = initialEl.dataset.day;
            const dayName = initialEl.dataset.dayname;
            lcSelectDate(initialDate, dayNum, dayName, initialEl);
        }
    });
    </script>
    </x-slot:scripts>
</x-app-layout>
