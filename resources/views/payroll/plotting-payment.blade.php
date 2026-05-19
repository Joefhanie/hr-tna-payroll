<x-app-layout>
    <x-slot:title>Plotting of Payments</x-slot:title>
    <x-slot:header>Plotting of Payments</x-slot:header>

    @if (session('status'))
        <div id="success-toast" class="mb-4 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm transition-all duration-300">
            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                <i class="ti ti-check text-lg"></i>
            </span>
            <p class="font-medium">{{ session('status') }}</p>
            <button type="button" onclick="this.closest('#success-toast').remove()" class="ml-auto text-emerald-400 hover:text-emerald-600 transition">
                <i class="ti ti-x text-base"></i>
            </button>
        </div>
        <script>setTimeout(function(){ var t = document.getElementById('success-toast'); if(t){ t.style.opacity='0'; setTimeout(function(){ if(t) t.remove(); }, 300); }}, 4000);</script>
    @endif

    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <form id="plotting-form" action="{{ route('payroll.plotting-payment.save') }}" method="POST">
            @csrf
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Plotting of Payments</h1>
                    <p class="mt-1 text-sm text-slate-600">Weekly payroll plotting for field employees.</p>
                </div>
            </div>

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
                                    <a href="{{ route('payroll.per-date', ['date' => $dateString]) }}"
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
                                    <a href="{{ route('payroll.plotting-payment.employee', ['employee' => $employee->id]) }}"
                                        class="text-blue-600 hover:text-blue-800 hover:underline">
                                        {{ $employee->first_name }} {{ $employee->last_name }}
                                    </a>
                                </td>
                                @foreach ($dates as $dateString => $dateLabel)
                                    @php
                                        $dayData = $row['days'][$dateString];
                                    @endphp
                                    <td
                                        class="border-b border-r border-slate-200 px-2 py-2 text-center last:border-r-0 relative">
                                        <div class="relative">
                                            <input type="text" inputmode="text" maxlength="10"
                                                name="entries[{{ $employee->id }}][{{ $dateString }}]"
                                                value="{{ $dayData['amount'] > 0 ? number_format($dayData['amount'], 2) : '' }}"
                                                placeholder="0" data-workplace="{{ $dayData['location'] }}"
                                                data-employee="{{ $employee->id }}"
                                                oninput="this.value = this.value.replace(/[^\d,.']/g, '').slice(0, 10)"
                                                class="w-full min-w-0 rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 outline-none ring-blue-200 focus:ring overflow-hidden">
                                            <!-- Comment indicator triangle -->
                                            <div
                                                class="absolute top-1 right-1 w-0 h-0 border-l-3 border-b-3 border-l-transparent border-b-gray-400 pointer-events-none">
                                            </div>

                                            <!-- Comment bubble (Excel/Sheets style) -->
                                            <div
                                                class="workplace-comment hidden absolute top-0 bg-gray-50 border border-gray-300 rounded px-3 py-2 shadow-lg z-50 w-48 text-left">
                                                <div class="space-y-1">
                                                    <div class="text-xs text-slate-700">
                                                        <span class="font-semibold text-slate-900">Work Assignment:</span>
                                                        <a href="{{ route('payroll.work-location-details', ['date' => $dateString, 'workplace' => urlencode($dayData['location'])]) }}"
                                                            class="text-blue-600 hover:text-blue-800 hover:underline">
                                                            {{ $dayData['location'] }}
                                                        </a>
                                                    </div>
                                                    <div class="text-xs text-slate-700">
                                                        <span class="font-semibold text-slate-900">Supervisor:</span>
                                                        <span class="text-slate-600">
                                                            {{ $dayData['supervisor_name'] }}
                                                        </span>
                                                    </div>
                                                    <div class="text-xs text-slate-700">
                                                        <span class="font-semibold text-slate-900">Supervisor's note:</span>
                                                        <span
                                                            class="text-slate-600 italic">{{ $dayData['supervisor_note'] ?? 'No note' }}</span>
                                                    </div>
                                                    <div class="pt-1 mt-1 border-t border-slate-200 flex items-center gap-1">
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="h-3 w-3 text-slate-400 shrink-0" viewBox="0 0 20 20"
                                                            fill="currentColor">
                                                            <path fill-rule="evenodd"
                                                                d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                        <input type="text"
                                                            name="payroll_notes[{{ $employee->id }}][{{ $dateString }}]"
                                                            placeholder="Add a payroll note" autocomplete="off"
                                                            class="w-full bg-transparent border-0 p-0 text-xs text-slate-700 italic placeholder:text-slate-400 placeholder:italic focus:ring-0 focus:outline-none">
                                                    </div>
                                                </div>
                                                <!-- Comment pointer -->
                                                <div
                                                    class="bubble-pointer absolute right-full top-1 -mr-1 w-0 h-0 border-r-4 border-t-4 border-t-transparent border-r-gray-50">
                                                </div>
                                            </div>
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
                    class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 shadow-sm">
                    Save Plotting
                </button>
            </div>
        </form>
    </div>

    <!-- Discard Changes Modal -->
    <div id="discard-modal" style="display: none;"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm">
        <div
            class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl border border-slate-100 transition-all transform scale-95 duration-200">
            <div class="flex items-center gap-3 text-amber-600">
                <span
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-600">
                    <i class="ti ti-alert-triangle text-xl"></i>
                </span>
                <h3 class="text-lg font-semibold text-slate-900">Unsaved Changes</h3>
            </div>
            <p class="mt-3 text-sm text-slate-600 leading-relaxed">You have unsaved changes in your plotting grid. Are
                you sure you want to discard these changes and leave this page?</p>
            <div class="mt-6 flex justify-end gap-3">
                <button id="modal-cancel-btn" type="button"
                    class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-100">
                    Cancel
                </button>
                <button id="modal-discard-btn" type="button"
                    class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20">
                    Discard Changes
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const GAP = 8;
            const BUBBLE_W = 192; // w-48

            document.querySelectorAll('.workplace-comment').forEach(function (bubble) {
                const wrapper = bubble.closest('.relative');
                const input = wrapper ? wrapper.querySelector('input[type="text"]') : null;
                if (!input) return;

                // Move bubble to body to avoid overflow clipping from table container
                document.body.appendChild(bubble);

                function positionBubble() {
                    if (bubble.classList.contains('hidden')) return;

                    const inputRect = input.getBoundingClientRect();
                    const spaceRight = window.innerWidth - inputRect.right;
                    const pointer = bubble.querySelector('.bubble-pointer');

                    const scrollX = window.pageXOffset || document.documentElement.scrollLeft;
                    const scrollY = window.pageYOffset || document.documentElement.scrollTop;

                    let leftPos = 0;
                    let topPos = inputRect.top + scrollY;

                    if (spaceRight < BUBBLE_W + GAP) {
                        // Flip to the LEFT of the cell
                        leftPos = inputRect.left + scrollX - BUBBLE_W - GAP;
                        if (pointer) {
                            pointer.className = 'bubble-pointer absolute left-full top-1 w-0 h-0 border-l-4 border-t-4 border-t-transparent border-l-gray-50';
                            pointer.style.marginLeft = '-1px';
                            pointer.style.marginRight = '';
                        }
                    } else {
                        // Default: RIGHT of the cell
                        leftPos = inputRect.right + scrollX + GAP;
                        if (pointer) {
                            pointer.className = 'bubble-pointer absolute right-full top-1 w-0 h-0 border-r-4 border-t-4 border-t-transparent border-r-gray-50';
                            pointer.style.marginLeft = '';
                            pointer.style.marginRight = '-1px';
                        }
                    }

                    bubble.style.left = leftPos + 'px';
                    bubble.style.top = topPos + 'px';
                    bubble.style.right = 'auto';
                    bubble.style.bottom = 'auto';
                    bubble.style.marginLeft = '0px';
                    bubble.style.marginRight = '0px';

                    // After render, check bottom overflow and shift up if needed
                    requestAnimationFrame(function () {
                        if (bubble.classList.contains('hidden')) return;
                        const bubbleRect = bubble.getBoundingClientRect();
                        const viewportBottom = window.innerHeight;

                        // Compare bounding client rect (viewport relative) with viewport height
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

                // Prevent blur when clicking non-focusable elements inside the bubble
                bubble.addEventListener('mousedown', function (e) {
                    const tag = e.target.tagName;
                    if (tag !== 'INPUT' && tag !== 'TEXTAREA') {
                        e.preventDefault();
                    }
                });
            });

            // Auto-save & confirmation logic:
            const form = document.getElementById('plotting-form');
            const restoreBanner = document.getElementById('restore-banner');
            const restoreConfirmBtn = document.getElementById('restore-confirm-btn');
            const restoreClearBtn = document.getElementById('restore-clear-btn');

            const discardModal = document.getElementById('discard-modal');
            const modalCancelBtn = document.getElementById('modal-cancel-btn');
            const modalDiscardBtn = document.getElementById('modal-discard-btn');

            if (!form) return;

            // Inputs to track: amount text inputs and notes inputs
            const gridInputs = Array.from(form.querySelectorAll('input[name^="entries["], input[name^="payroll_notes["], input[name^="payroll_notes["]'));

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