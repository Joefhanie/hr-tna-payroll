<x-app-layout>
    <x-slot:title>Plotting of Payments</x-slot:title>
    <x-slot:header>Plotting of Payments</x-slot:header>

    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Plotting of Payments</h1>
                <p class="mt-1 text-sm text-slate-600">Weekly payroll plotting for field employees.</p>
            </div>
        </div>

        <div class="mt-6 overflow-hidden rounded-lg border border-slate-200 relative">
            <table class="min-w-full border-separate border-spacing-0 text-sm">
                <thead>
                    <tr>
                        <th class="sticky left-0 z-10 w-44 border-b border-r border-slate-200 bg-slate-50 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 text-center">
                            Name
                        </th>
                        @foreach ($dates as $dateString => $dateLabel)
                            <th class="border-b border-r border-slate-200 bg-slate-50 text-center text-xs font-semibold uppercase tracking-wide last:border-r-0 hover:bg-slate-100 transition-colors">
                                <a href="{{ route('payroll.per-date', ['date' => $dateString]) }}" class="block w-full px-4 py-3 text-blue-600 hover:text-blue-800 hover:underline">
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
                            <td class="sticky left-0 z-10 border-b border-r border-slate-200 bg-white px-4 py-3 font-medium text-slate-900">
                                <a href="{{ route('payroll.plotting-payment.employee', ['employee' => $employee->id]) }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                                    {{ $employee->first_name }} {{ $employee->last_name }}
                                </a>
                            </td>
                            @foreach ($dates as $dateString => $dateLabel)
                                @php
                                    $dayData = $row['days'][$dateString];
                                @endphp
                                <td class="border-b border-r border-slate-200 px-2 py-2 text-center last:border-r-0 relative">
                                    <div class="relative">
                                        <input
                                            type="text"
                                            inputmode="text"
                                            maxlength="10"
                                            name="entries[{{ $employee->id }}][{{ $dateString }}]"
                                            value="{{ $dayData['amount'] > 0 ? number_format($dayData['amount'], 2) : '' }}"
                                            placeholder="0"
                                            data-workplace="{{ $dayData['location'] }}"
                                            data-employee="{{ $employee->id }}"
                                            oninput="this.value = this.value.replace(/[^\d,.']/g, '').slice(0, 10)"
                                            class="w-full min-w-0 rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 outline-none ring-blue-200 focus:ring overflow-hidden"
                                        >
                                        <!-- Comment indicator triangle -->
                                        <div class="absolute top-1 right-1 w-0 h-0 border-l-3 border-b-3 border-l-transparent border-b-gray-400 pointer-events-none"></div>

                                            <!-- Comment bubble (Excel/Sheets style) -->
                                            <div class="workplace-comment hidden absolute top-0 bg-gray-50 border border-gray-300 rounded px-3 py-2 shadow-lg z-50 w-48 text-left">
                                                <div class="space-y-1">
                                                    <div class="text-xs text-slate-700">
                                                        <span class="font-semibold text-slate-900">Work Assignment:</span>
                                                        <a href="{{ route('payroll.work-location-details', ['date' => $dateString, 'workplace' => urlencode($dayData['location'])]) }}" class="text-blue-600 hover:text-blue-800 hover:underline">
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
                                                        <span class="text-slate-600 italic">{{ $dayData['supervisor_note'] ?? 'No note' }}</span>
                                                    </div>
                                                    <div class="pt-1 mt-1 border-t border-slate-200 flex items-center gap-1">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-slate-400 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                                        </svg>
                                                        <input type="text" 
                                                            name="payroll_notes[{{ $employee->id }}][{{ $dateString }}]" 
                                                            placeholder="Add a payroll note" 
                                                            autocomplete="off"
                                                            class="w-full bg-transparent border-0 p-0 text-xs text-slate-700 italic placeholder:text-slate-400 placeholder:italic focus:ring-0 focus:outline-none"
                                                        >
                                                    </div>
                                                </div>
                                                <!-- Comment pointer -->
                                                <div class="bubble-pointer absolute right-full top-1 -mr-1 w-0 h-0 border-r-4 border-t-4 border-t-transparent border-r-gray-50"></div>
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
                <p class="text-xs text-slate-500">Enter each employee's amount per date. Hover/focus cells to see supervisor's note and work location.</p>
                <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 shadow-sm">
                    Save Plotting
                </button>
            </div>
        </form>
    </div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const GAP = 8;
    const BUBBLE_W = 192; // w-48

    document.querySelectorAll('.workplace-comment').forEach(function (bubble) {
        const wrapper = bubble.closest('.relative');
        const input   = wrapper ? wrapper.querySelector('input[type="text"]') : null;
        if (!input) return;

        // Move bubble to body to avoid overflow clipping from table container
        document.body.appendChild(bubble);

        function positionBubble() {
            if (bubble.classList.contains('hidden')) return;
            
            const inputRect  = input.getBoundingClientRect();
            const spaceRight = window.innerWidth - inputRect.right;
            const pointer    = bubble.querySelector('.bubble-pointer');
            
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
            setTimeout(function() {
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
});
</script>
</x-app-layout>
