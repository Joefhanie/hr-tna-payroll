<x-app-layout>
    <x-slot:title>Plotting of Payments</x-slot:title>
    <x-slot:header>Plotting of Payments</x-slot:header>


    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 pb-6 border-b border-slate-100">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Plotting of Payments</h1>
                <p class="mt-1 text-sm text-slate-600">Weekly payroll plotting for field employees.</p>
            </div>
        </div>

        <!-- Date Range Filter Form -->
        <form id="filter-form" method="GET" action="{{ route('payroll.plotting-payment') }}" class="mt-6 p-4 rounded-xl border border-slate-100 bg-slate-50/50">
            <div class="flex flex-wrap items-end gap-4">
                <div class="w-full sm:w-auto min-w-[150px]">
                    <label for="from-date" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">From Date</label>
                    <input type="date" name="from_date" id="from-date" value="{{ $resolvedFromDate }}" onchange="this.form.submit()"
                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="w-full sm:w-auto min-w-[150px]">
                    <label for="to-date" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">To Date</label>
                    <input type="date" name="to_date" id="to-date" value="{{ $resolvedToDate }}" onchange="this.form.submit()"
                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex gap-2 w-full sm:w-auto">
                    <a href="{{ route('payroll.plotting-payment') }}" class="px-4 py-2 rounded-lg border border-slate-200 text-sm text-slate-600 hover:bg-slate-50 transition bg-white font-medium flex items-center justify-center">
                        Clear
                    </a>
                    <button type="button" onclick="openMissedPlottingsModal()" class="px-4 py-2 rounded-lg bg-indigo-600 text-sm text-white hover:bg-indigo-700 transition font-medium flex items-center justify-center gap-2 shadow-sm">
                        <i class="ti ti-search text-base"></i> Find Missed
                    </button>
                </div>
            </div>
        </form>

        <form id="plotting-form" action="{{ route('payroll.plotting-payment.save') }}" method="POST">
            @csrf
            <input type="hidden" name="from_date" value="{{ $resolvedFromDate }}">
            <input type="hidden" name="to_date" value="{{ $resolvedToDate }}">

            <!-- Restore Banner -->
            <div id="restore-banner" style="display: none;"
                class="my-6 flex flex-col sm:flex-row items-center justify-between gap-4 rounded-xl border border-blue-100 bg-blue-50/50 p-4 text-sm text-blue-800 shadow-sm backdrop-blur-sm">
                <div class="flex items-center gap-3">
                    <span
                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                        <i class="ti ti-notebook text-lg"></i>
                    </span>
                    <div>
                        <p class="font-semibold">Unsaved plotting draft found!</p>
                        <p class="text-xs text-blue-600 mt-0.5">We found changes from a previous session that weren't
                            saved.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                    <button id="restore-clear-btn" type="button"
                        class="w-full sm:w-auto rounded-lg px-3 py-1.5 text-xs font-semibold text-blue-600 transition hover:bg-blue-100/50">
                        Discard Draft
                    </button>
                    <button id="restore-confirm-btn" type="button"
                        class="w-full sm:w-auto rounded-lg bg-blue-600 px-3.5 py-1.5 text-xs font-semibold text-white transition hover:bg-blue-700 shadow-sm">
                        Restore Changes
                    </button>
                </div>
            </div>

            @php
                $hasEditableFields = false;
                foreach ($gridData as $row) {
                    foreach ($dates as $dateString => $dateLabel) {
                        foreach ($row['days'][$dateString] as $dayData) {
                            if (empty($dayData['posted'])) {
                                $hasEditableFields = true;
                                break 3;
                            }
                        }
                    }
                }
            @endphp

            <div class="mt-6 overflow-hidden rounded-lg border border-slate-200 relative">
                <table class="min-w-full border-separate border-spacing-0 text-sm">
                    <thead>
                        <tr>
                            <th
                                class="sticky left-0 z-10 w-44 border-b border-r border-slate-200 bg-slate-50 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Name
                            </th>
                            @foreach ($dates as $dateString => $dateLabel)
                                <th
                                    class="border-b border-r border-slate-200 bg-slate-50 text-center text-xs font-semibold uppercase tracking-wide last:border-r-0 hover:bg-slate-100 transition-colors">
                                    <a href="{{ route('payroll.per-date', ['date' => $dateString, 'from_date' => $resolvedFromDate, 'to_date' => $resolvedToDate]) }}"
                                        class="block w-full px-4 py-3 text-blue-600 hover:text-blue-800 hover:underline">
                                        {{ $dateLabel }}
                                    </a>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($gridData as $row)
                            @php
                                $employee = $row['employee'];
                            @endphp
                            <tr class="bg-white">
                                <td
                                    class="sticky left-0 z-10 border-b border-r border-slate-200 bg-white px-4 py-3 font-medium text-slate-900">
                                    <a href="{{ route('payroll.plotting-payment.employee', ['employee' => $employee->id, 'from_date' => $resolvedFromDate, 'to_date' => $resolvedToDate]) }}"
                                        class="text-blue-600 hover:text-blue-800 hover:underline">
                                        {{ $employee->first_name }} {{ $employee->last_name }}
                                    </a>
                                </td>
                                @foreach ($dates as $dateString => $dateLabel)
                                    @php
                                        $dayEntries = $row['days'][$dateString];
                                    @endphp
                                    <td
                                        class="border-b border-r border-slate-200 px-2 py-2 text-center last:border-r-0 relative">
                                        <div class="flex flex-col gap-1.5">
                                            @foreach ($dayEntries as $entryIndex => $dayData)
                                                <div class="relative">
                                                    <input type="text" inputmode="text" maxlength="10"
                                                        name="entries[{{ $employee->id }}][{{ $dayData['location'] }}][{{ $dateString }}]"
                                                        value="{{ $dayData['amount'] > 0 ? number_format($dayData['amount'], 2) : '' }}"
                                                        placeholder="0" data-workplace="{{ $dayData['location'] }}"
                                                        data-employee="{{ $employee->id }}"
                                                        oninput="this.value = this.value.replace(/[^\d,.']/g, '').slice(0, 10); var tri = this.parentElement.querySelector('.payment-triangle'); if(tri){ if(this.value.trim()){ tri.style.borderRightColor='#16a34a'; tri.title='Paid'; } else { tri.style.borderRightColor='#ef4444'; tri.title='Unpaid'; } }"
                                                        @if(!empty($dayData['posted'])) readonly @endif
                                                        class="w-full min-w-0 rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 outline-none ring-blue-200 focus:ring overflow-hidden @if(!empty($dayData['posted'])) bg-slate-100 text-slate-500 cursor-not-allowed font-medium @endif">
                                                    {{-- Payment status triangle: red = unpaid, green = has amount --}}
                                                    <div class="payment-triangle absolute top-0 right-0 w-0 h-0 pointer-events-none"
                                                        style="border-style:solid; border-width:0 8px 8px 0; border-color:transparent {{ ($dayData['amount'] > 0) ? '#16a34a' : '#ef4444' }} transparent transparent;"
                                                        title="{{ ($dayData['amount'] > 0) ? 'Paid' : 'Unpaid' }}"></div>

                                                    <!-- Payroll note indicator triangle (Excel-style, top-left, blue) -->
                                                    <div
                                                        class="note-indicator hidden absolute top-0 left-0 w-0 h-0 pointer-events-none"
                                                        style="border-style:solid; border-width:8px 8px 0 0; border-color:#3b82f6 transparent transparent transparent;">
                                                    </div>

                                                    <!-- Hidden payroll note value (submitted with form) -->
                                                    <input type="hidden"
                                                        name="payroll_notes[{{ $employee->id }}][{{ $dayData['location'] }}][{{ $dateString }}]"
                                                        class="payroll-note-input"
                                                        value="">

                                                    <!-- Workplace comment bubble (shows on amount-input focus) -->
                                                    <div
                                                        class="workplace-comment hidden absolute top-0 bg-gray-50 border border-gray-300 rounded px-3 py-2 shadow-lg z-50 w-52 text-left">
                                                        <!-- Work info rows -->
                                                        <div class="space-y-1">
                                                            <div class="text-xs text-slate-700">
                                                                <span class="font-semibold text-slate-900">Work Assignment:</span>
                                                                @if(!empty(trim($dayData['location'])))
                                                                    <a href="{{ route('payroll.work-location-details', ['date' => $dateString, 'workplace' => urlencode($dayData['location']), 'from_date' => $resolvedFromDate, 'to_date' => $resolvedToDate]) }}"
                                                                        class="text-blue-600 hover:text-blue-800 hover:underline">
                                                                        {{ $dayData['location'] }}
                                                                    </a>
                                                                @else
                                                                    <span class="text-slate-400 italic">Unassigned</span>
                                                                @endif
                                                            </div>
                                                            <div class="text-xs text-slate-700">
                                                                <span class="font-semibold text-slate-900">Supervisor:</span>
                                                                <span class="text-slate-600">{{ $dayData['supervisor_name'] }}</span>
                                                            </div>
                                                            <div class="text-xs text-slate-700">
                                                                <span class="font-semibold text-slate-900">Supervisor ID:</span>
                                                                <span class="text-slate-600">{{ $dayData['supervisor_code'] ?? '—' }}</span>
                                                            </div>
                                                            <div class="text-xs text-slate-700">
                                                                <span class="font-semibold text-slate-900 block mb-0.5">Supervisor's notes:</span>
                                                                @if(!empty($dayData['supervisor_note']))
                                                                    <div class="flex flex-col mt-0.5 space-y-0.5">
                                                                        @foreach(explode("\n", $dayData['supervisor_note']) as $noteLine)
                                                                            @if(trim($noteLine) !== '')
                                                                                <span class="text-slate-600 italic">"{{ trim($noteLine) }}"</span>
                                                                            @endif
                                                                        @endforeach
                                                                    </div>
                                                                @else
                                                                    <span class="text-slate-600 italic">No note</span>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <!-- Divider + payroll note toggle row -->
                                                        <div class="pt-1.5 mt-1.5 border-t border-slate-200 flex items-center justify-between gap-2">
                                                            <span class="text-xs text-slate-400 italic note-preview-text">No payroll note</span>
                                                            <button type="button"
                                                                class="note-add-btn shrink-0 flex items-center justify-center w-5 h-5 rounded-full bg-slate-200 text-slate-500 hover:bg-blue-100 hover:text-blue-600 transition-colors text-sm font-bold leading-none"
                                                                title="Add payroll note">+</button>
                                                        </div>

                                                        <!-- Inline payroll note panel (expands below on "+" click) -->
                                                        <div class="note-inline-panel hidden pt-2 mt-1 border-t border-blue-100">
                                                            <p class="text-xs font-semibold text-blue-700 mb-1.5 flex items-center gap-1">
                                                                <i class="ti ti-pencil text-xs"></i> Payroll Note
                                                            </p>
                                                            <textarea rows="3"
                                                                class="note-bubble-textarea w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-300 resize-none"
                                                                placeholder="Type a note…"></textarea>
                                                            <div class="mt-1.5 flex justify-end gap-1.5">
                                                                <button type="button"
                                                                    class="note-cancel-btn rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50 transition">Cancel</button>
                                                                <button type="button"
                                                                    class="note-save-btn rounded-lg bg-blue-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-blue-700 transition shadow-sm">Submit</button>
                                                            </div>
                                                        </div>

                                                        <!-- Comment pointer -->
                                                        <div
                                                            class="bubble-pointer absolute right-full top-1 -mr-1 w-0 h-0 border-r-4 border-t-4 border-t-transparent border-r-gray-50">
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex items-center justify-between gap-3">
                <p class="text-xs text-slate-500">Enter each employee's amount per date. Hover/focus cells to see
                    supervisor's note and work location.</p>
                <button type="submit"
                    data-confirm="Are you sure you want to submit and save the current plotting payments?"
                    data-confirm-title="Submit Plotting Payments"
                    @if(!$hasEditableFields) disabled @endif
                    class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    Submit Plotting
                </button>
            </div>
        </form>
    </div>

    <!-- Discard Confirmation Modal -->
    <div id="discard-modal" style="display: none;"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm px-4">
        <div class="w-full max-w-sm transform overflow-hidden rounded-2xl bg-white p-6 shadow-2xl transition-all scale-95 duration-200 text-center">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-100 text-red-600">
                <i class="ti ti-alert-triangle text-2xl"></i>
            </div>
            <h3 class="mb-2 text-lg font-bold text-slate-900">Discard Changes?</h3>
            <p class="mb-6 text-sm text-slate-500">You have unsaved values. Leaving this page will discard them. Are you sure you want to leave?</p>
            
            <div class="flex flex-col sm:flex-row gap-3">
                <button id="modal-cancel-btn" type="button" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    Cancel
                </button>
                <button id="modal-discard-btn" type="button" class="w-full rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
                    Discard & Leave
                </button>
            </div>
        </div>
    </div>

    <!-- Missed Plottings Modal -->
    <div id="missed-plottings-modal" style="display: none;" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm px-4">
        <div class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white shadow-2xl transition-all scale-95 duration-200">
            <div class="border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                    <i class="ti ti-search text-indigo-600"></i> Find Missed Plottings
                </h3>
                <button type="button" onclick="closeMissedPlottingsModal()" class="text-slate-400 hover:text-slate-600 transition">
                    <i class="ti ti-x text-xl"></i>
                </button>
            </div>
            <div class="p-6">
                <p class="text-sm text-slate-600 mb-4">Select a target date to find all past field records that have not been plotted or paid yet.</p>
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Target Date</label>
                    <input type="date" id="missed-target-date" value="{{ date('Y-m-d') }}"
                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <button type="button" onclick="fetchMissedPlottings()" class="w-full py-2.5 rounded-lg bg-indigo-600 text-white font-semibold shadow-sm hover:bg-indigo-700 transition flex items-center justify-center gap-2">
                    <i class="ti ti-search"></i> Search Missed Dates
                </button>

                <div id="missed-results-container" class="mt-6 hidden">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2 border-b border-slate-100 pb-2">Results</h4>
                    <div id="missed-results-list" class="max-h-48 overflow-y-auto space-y-1.5 pr-2">
                        <!-- JS injected list here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openMissedPlottingsModal() {
            const modal = document.getElementById('missed-plottings-modal');
            modal.style.display = 'flex';
            setTimeout(() => modal.querySelector('div').classList.replace('scale-95', 'scale-100'), 10);
            document.getElementById('missed-results-container').classList.add('hidden');
        }

        function closeMissedPlottingsModal() {
            const modal = document.getElementById('missed-plottings-modal');
            modal.querySelector('div').classList.replace('scale-100', 'scale-95');
            setTimeout(() => modal.style.display = 'none', 150);
        }

        async function fetchMissedPlottings() {
            const targetDate = document.getElementById('missed-target-date').value;
            const btn = document.querySelector('#missed-plottings-modal button[onclick="fetchMissedPlottings()"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="ti ti-loader animate-spin text-base"></i> Searching...';
            btn.disabled = true;

            try {
                const response = await fetch(`{{ route('payroll.plotting-payment.missed') }}?target_date=${targetDate}`);
                if (!response.ok) {
                    throw new Error(`Request failed with status ${response.status}`);
                }
                const data = await response.json();
                
                const listContainer = document.getElementById('missed-results-list');
                listContainer.innerHTML = '';

                if (data.dates && data.dates.length > 0) {
                    data.dates.forEach(date => {
                        const link = document.createElement('a');
                        link.href = `{{ route('payroll.plotting-payment') }}?from_date=${date}&to_date=${date}`;
                        link.className = "flex items-center justify-between px-3 py-2 rounded-lg bg-red-50 text-red-700 hover:bg-red-100 transition group border border-red-100";
                        link.innerHTML = `
                            <span class="font-medium text-sm"><i class="ti ti-calendar mr-1"></i> ${date}</span>
                            <span class="text-xs font-semibold uppercase tracking-wider bg-white rounded px-2 py-0.5 border border-red-200 group-hover:bg-red-600 group-hover:text-white transition shadow-sm">Plot Now &rarr;</span>
                        `;
                        listContainer.appendChild(link);
                    });
                } else {
                    listContainer.innerHTML = '<p class="text-sm text-slate-500 text-center py-4">No missed plottings found before this date! 🎉</p>';
                }

                document.getElementById('missed-results-container').classList.remove('hidden');
            } catch (err) {
                console.error(err);
                alert("Failed to fetch missed dates.");
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const GAP = 8;
            const BUBBLE_W = 208; // w-52

            // ── Payroll note inline panel (expands inside the workplace bubble) ──
            // NOTE: This block MUST run before the workplace-comment block below,
            // because that block moves .workplace-comment to document.body, which
            // breaks .closest('.relative') lookups for btns inside it.
            document.querySelectorAll('.note-add-btn').forEach(function (btn) {
                // Capture all refs while .workplace-comment is still inside .relative
                const workplaceBubble = btn.closest('.workplace-comment');
                const wrapper         = workplaceBubble ? workplaceBubble.closest('.relative') : null;
                const notePanel       = workplaceBubble ? workplaceBubble.querySelector('.note-inline-panel') : null;
                const noteInput       = wrapper ? wrapper.querySelector('.payroll-note-input') : null;
                const indicator       = wrapper ? wrapper.querySelector('.note-indicator') : null;
                const previewText     = workplaceBubble ? workplaceBubble.querySelector('.note-preview-text') : null;
                if (!notePanel || !noteInput) return;

                const textarea  = notePanel.querySelector('.note-bubble-textarea');
                const saveBtn   = notePanel.querySelector('.note-save-btn');
                const cancelBtn = notePanel.querySelector('.note-cancel-btn');

                function closePanel() {
                    notePanel.classList.add('hidden');
                    btn.classList.remove('bg-blue-200', 'text-blue-700');
                    btn.textContent = '+';
                }

                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const isOpen = !notePanel.classList.contains('hidden');
                    if (isOpen) {
                        closePanel();
                    } else {
                        textarea.value = noteInput.value;
                        notePanel.classList.remove('hidden');
                        btn.classList.add('bg-blue-200', 'text-blue-700');
                        btn.textContent = '−';
                        setTimeout(() => textarea.focus(), 30);
                    }
                });

                saveBtn.addEventListener('click', function () {
                    noteInput.value = textarea.value.trim();
                    if (noteInput.value) {
                        if (indicator) indicator.classList.remove('hidden');
                        if (previewText) {
                            previewText.textContent = noteInput.value;
                            previewText.classList.remove('text-slate-400', 'italic');
                            previewText.classList.add('text-slate-600', 'font-medium');
                        }
                        btn.title = 'Edit payroll note';
                        btn.classList.add('bg-green-100', 'text-green-700');
                        btn.classList.remove('bg-slate-200', 'text-slate-500');
                    } else {
                        if (indicator) indicator.classList.add('hidden');
                        if (previewText) {
                            previewText.textContent = 'No payroll note';
                            previewText.classList.add('text-slate-400', 'italic');
                            previewText.classList.remove('text-slate-600', 'font-medium');
                        }
                        btn.title = 'Add payroll note';
                        btn.classList.remove('bg-green-100', 'text-green-700');
                        btn.classList.add('bg-slate-200', 'text-slate-500');
                    }
                    closePanel();
                    const form = document.getElementById('plotting-form');
                    if (form) form.dispatchEvent(new Event('input', { bubbles: true }));
                });

                cancelBtn.addEventListener('click', closePanel);
            });

            // ── Workplace comment bubble (shows on amount-input focus) ──────────
            // NOTE: This runs AFTER note-add-btn setup so that .workplace-comment
            // elements are still inside .relative when note-add-btn captures refs above.
            document.querySelectorAll('.workplace-comment').forEach(function (bubble) {
                const wrapper = bubble.closest('.relative');
                const input = wrapper ? wrapper.querySelector('input[name^="entries["]') : null;
                if (!input) return;

                document.body.appendChild(bubble);

                function positionBubble() {
                    if (bubble.classList.contains('hidden')) return;
                    const inputRect = input.getBoundingClientRect();
                    const spaceRight = window.innerWidth - inputRect.right;
                    const pointer = bubble.querySelector('.bubble-pointer');
                    const scrollX = window.pageXOffset || document.documentElement.scrollLeft;
                    const scrollY = window.pageYOffset || document.documentElement.scrollTop;
                    let leftPos = 0, topPos = inputRect.top + scrollY;

                    if (spaceRight < BUBBLE_W + GAP) {
                        leftPos = inputRect.left + scrollX - BUBBLE_W - GAP;
                        if (pointer) {
                            pointer.className = 'bubble-pointer absolute left-full top-1 w-0 h-0 border-l-4 border-t-4 border-t-transparent border-l-gray-50';
                            pointer.style.marginLeft = '-1px'; pointer.style.marginRight = '';
                        }
                    } else {
                        leftPos = inputRect.right + scrollX + GAP;
                        if (pointer) {
                            pointer.className = 'bubble-pointer absolute right-full top-1 w-0 h-0 border-r-4 border-t-4 border-t-transparent border-r-gray-50';
                            pointer.style.marginLeft = ''; pointer.style.marginRight = '-1px';
                        }
                    }
                    bubble.style.left = leftPos + 'px'; bubble.style.top = topPos + 'px';
                    bubble.style.right = 'auto'; bubble.style.bottom = 'auto';
                    bubble.style.marginLeft = '0px'; bubble.style.marginRight = '0px';

                    requestAnimationFrame(function () {
                        if (bubble.classList.contains('hidden')) return;
                        const bubbleRect = bubble.getBoundingClientRect();
                        const viewportBottom = window.innerHeight;
                        if (bubbleRect.bottom > viewportBottom - GAP) {
                            const overflow = bubbleRect.bottom - viewportBottom + GAP;
                            bubble.style.top = (topPos - overflow) + 'px';
                            if (pointer) pointer.style.top = (4 + overflow) + 'px';
                        } else {
                            if (pointer) pointer.style.top = '4px';
                        }
                    });
                }

                function checkFocus() {
                    setTimeout(function () {
                        const active = document.activeElement;
                        if (active !== input && !bubble.contains(active)) {
                            bubble.classList.add('hidden');
                            window.removeEventListener('scroll', positionBubble, true);
                            window.removeEventListener('resize', positionBubble);
                        }
                    }, 10);
                }

                input.addEventListener('focus', function () {
                    bubble.classList.remove('hidden');
                    positionBubble();
                    window.addEventListener('scroll', positionBubble, true);
                    window.addEventListener('resize', positionBubble);
                });
                input.addEventListener('blur', checkFocus);
                bubble.addEventListener('focusout', checkFocus);
                bubble.addEventListener('mousedown', function (e) {
                    const tag = e.target.tagName;
                    if (tag !== 'INPUT' && tag !== 'TEXTAREA' && tag !== 'BUTTON') e.preventDefault();
                });
            });
            const form = document.getElementById('plotting-form');
            const restoreBanner = document.getElementById('restore-banner');
            const restoreConfirmBtn = document.getElementById('restore-confirm-btn');
            const restoreClearBtn = document.getElementById('restore-clear-btn');

            const discardModal = document.getElementById('discard-modal');
            const modalCancelBtn = document.getElementById('modal-cancel-btn');
            const modalDiscardBtn = document.getElementById('modal-discard-btn');

            if (!form) return;

            // Inputs to track: amount text inputs and notes inputs
            const gridInputs = Array.from(form.querySelectorAll('input[name^="entries["], input[name^="payroll_notes["]'));

            // Capture initial database values
            const originalValues = {};
            gridInputs.forEach(input => {
                originalValues[input.name] = input.value;
            });

            // Check if the current form has any dirty/unsaved changes
            function hasUnsavedChanges() {
                return gridInputs.some(input => {
                    const currentVal = input.value;
                    const originalVal = originalValues[input.name] || '';
                    return currentVal !== originalVal;
                });
            }

            // Save changes to localStorage
            function saveDraftToLocalStorage() {
                const draft = {};
                gridInputs.forEach(input => {
                    if (input.value !== (originalValues[input.name] || '')) {
                        draft[input.name] = input.value;
                    }
                });

                if (Object.keys(draft).length > 0) {
                    localStorage.setItem('plotting_payment_draft', JSON.stringify(draft));
                } else {
                    localStorage.removeItem('plotting_payment_draft');
                }
            }

            // Load draft from localStorage on page load
            function checkLocalStorageDraft() {
                const savedDraft = localStorage.getItem('plotting_payment_draft');
                if (savedDraft) {
                    try {
                        const draft = JSON.parse(savedDraft);
                        if (Object.keys(draft).length > 0) {
                            restoreBanner.style.display = 'flex';
                        }
                    } catch (e) {
                        console.error('Error parsing draft from localStorage', e);
                    }
                }
            }

            // Listen to changes to update localStorage and page dirty status
            form.addEventListener('input', function (e) {
                if (e.target.matches('input[name^="entries["], input[name^="payroll_notes["]')) {
                    saveDraftToLocalStorage();
                }
            });

            const filterForm = document.getElementById('filter-form');
            if (filterForm) {
                filterForm.addEventListener('submit', function (e) {
                    if (hasUnsavedChanges()) {
                        e.preventDefault();
                        pendingNavigationUrl = filterForm.action + '?' + new URLSearchParams(new FormData(filterForm)).toString();
                        showDiscardModal();
                    }
                });
            }

            // Handle "Restore Changes" button click
            if (restoreConfirmBtn) {
                restoreConfirmBtn.addEventListener('click', function () {
                    const savedDraft = localStorage.getItem('plotting_payment_draft');
                    if (savedDraft) {
                        try {
                            const draft = JSON.parse(savedDraft);
                            Object.entries(draft).forEach(([name, value]) => {
                                const input = form.querySelector(`input[name="${CSS.escape(name)}"]`);
                                if (input) {
                                    input.value = value;
                                    // Trigger visual feedback (subtle temporary highlight)
                                    input.classList.add('border-blue-500', 'bg-blue-50/30');
                                    setTimeout(() => {
                                        input.classList.remove('border-blue-500', 'bg-blue-50/30');
                                    }, 1500);
                                }
                            });
                            // Re-save draft to keep local storage synced (though it's already there)
                            saveDraftToLocalStorage();
                        } catch (e) {
                            console.error(e);
                        }
                    }
                    restoreBanner.style.display = 'none';
                });
            }

            // Handle "Discard Draft" button click
            if (restoreClearBtn) {
                restoreClearBtn.addEventListener('click', function () {
                    localStorage.removeItem('plotting_payment_draft');
                    restoreBanner.style.display = 'none';
                });
            }

            // 1. Browser-level reload / close confirmation (named so we can remove it)
            function onBeforeUnload(e) {
                if (hasUnsavedChanges()) {
                    e.preventDefault();
                    e.returnValue = '';
                    return '';
                }
            }
            window.addEventListener('beforeunload', onBeforeUnload);

            // Clear localStorage upon form submission & remove beforeunload warning
            form.addEventListener('submit', function () {
                window.removeEventListener('beforeunload', onBeforeUnload);
                localStorage.removeItem('plotting_payment_draft');
            });

            // The global confirm modal calls form.submit() which bypasses the 'submit' event.
            // We need to also hook into the global confirmProceed button to remove the warning.
            const globalConfirmProceed = document.getElementById('confirmProceed');
            if (globalConfirmProceed) {
                globalConfirmProceed.addEventListener('click', function () {
                    window.removeEventListener('beforeunload', onBeforeUnload);
                    localStorage.removeItem('plotting_payment_draft');
                });
            }

            // 2. Intercept page-wide link clicks for internal navigation
            let pendingNavigationUrl = null;

            document.addEventListener('click', function (e) {
                const link = e.target.closest('a');
                if (!link) return;

                // Skip button elements, javascript targets, external targets, hash anchors
                if (
                    !link.href ||
                    link.href.startsWith('javascript:') ||
                    link.hash ||
                    link.getAttribute('target') === '_blank'
                ) {
                    return;
                }

                // Verify if it is in the same origin (same app)
                try {
                    const url = new URL(link.href, window.location.href);
                    if (url.origin !== window.location.origin) {
                        return; // Let browser handle external links
                    }

                    // Exclude links that have custom comment links or popups inside the page
                    if (link.closest('.workplace-comment')) {
                        return; // Let workplace comment link open normally
                    }

                    // Check if user has unsaved changes
                    if (hasUnsavedChanges()) {
                        e.preventDefault();
                        pendingNavigationUrl = link.href;
                        showDiscardModal();
                    }
                } catch (err) {
                    console.error('Invalid URL checked', err);
                }
            });

            function showDiscardModal() {
                if (!discardModal) return;
                discardModal.style.display = 'flex';
                // Simple animation/fade-in
                const modalContent = discardModal.querySelector('div');
                if (modalContent) {
                    modalContent.classList.remove('scale-95');
                    modalContent.classList.add('scale-100');
                }
            }

            function hideDiscardModal() {
                if (!discardModal) return;
                const modalContent = discardModal.querySelector('div');
                if (modalContent) {
                    modalContent.classList.remove('scale-100');
                    modalContent.classList.add('scale-95');
                }
                setTimeout(() => {
                    discardModal.style.display = 'none';
                }, 150);
                pendingNavigationUrl = null;
            }

            if (modalCancelBtn) {
                modalCancelBtn.addEventListener('click', hideDiscardModal);
            }

            if (modalDiscardBtn) {
                modalDiscardBtn.addEventListener('click', function () {
                    // Clear local storage and allow navigation
                    localStorage.removeItem('plotting_payment_draft');
                    if (pendingNavigationUrl) {
                        window.location.href = pendingNavigationUrl;
                    } else {
                        hideDiscardModal();
                    }
                });
            }

            // Run localStorage check on load
            checkLocalStorageDraft();
        });
    </script>
</x-app-layout>
