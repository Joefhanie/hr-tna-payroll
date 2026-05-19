<x-app-layout>
    <x-slot:title>Plotting of Payments</x-slot:title>
    <x-slot:header>Plotting of Payments</x-slot:header>

    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Plotting of Payments</h1>
                <p class="mt-1 text-sm text-slate-600">Spreadsheet-style payroll plotting with names on the left and weekly date headers across the top.</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                Payroll
            </span>
        </div>

        <div class="mt-6 overflow-hidden rounded-lg border border-slate-200 relative">
            <table class="min-w-full border-separate border-spacing-0 text-sm">
                <thead>
                    <tr>
                        <th class="sticky left-0 z-10 w-44 border-b border-r border-slate-200 bg-slate-50 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 text-center">
                            Name
                        </th>
                        @foreach ($weekData as $day)
                            <th class="border-b border-r border-slate-200 bg-slate-50 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500 last:border-r-0">
                                {{ $day['date'] }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($employees as $employeeIndex => $employee)
                        <tr class="bg-white">
                            <td class="sticky left-0 z-10 border-b border-r border-slate-200 bg-white px-4 py-3 font-medium text-slate-900">
                                <a href="{{ route('payroll.plotting-payment.employee', ['employee' => urlencode($employee['name'])]) }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                                    {{ $employee['name'] }}
                                </a>
                            </td>
                            @foreach ($weekData as $dayIndex => $day)
                                <td class="border-b border-r border-slate-200 px-2 py-2 text-center last:border-r-0 relative">
                                    <div class="relative">
                                        <input
                                            type="text"
                                            inputmode="text"
                                            maxlength="10"
                                            name="entries[{{ $employee['name'] }}][{{ $dayIndex }}]"
                                            placeholder="0"
                                            data-workplace="{{ $day['workplace'] }}"
                                            data-employee="{{ $employee['name'] }}"
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
                                                        <span class="font-semibold text-slate-900">Note:</span>
                                                        <span class="text-slate-600 italic">No description</span>
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
                <p class="text-xs text-slate-500">Enter each employee's amount per date. Hover/focus cells to see supervisor-inherited locations.</p>
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

        function positionBubble() {
            const inputRect  = input.getBoundingClientRect();
            const spaceRight = window.innerWidth - inputRect.right;
            const pointer    = bubble.querySelector('.bubble-pointer');

            // Reset inline styles
            bubble.style.left = bubble.style.right =
            bubble.style.top  = bubble.style.bottom =
            bubble.style.marginLeft = bubble.style.marginRight = '';

            if (spaceRight < BUBBLE_W + GAP) {
                // Flip to the LEFT of the cell
                bubble.style.right      = '100%';
                bubble.style.left       = 'auto';
                bubble.style.marginRight = GAP + 'px';
                if (pointer) {
                    pointer.className = 'bubble-pointer absolute left-full top-1 w-0 h-0 border-l-4 border-t-4 border-t-transparent border-l-gray-50';
                    pointer.style.marginLeft = '-1px';
                }
            } else {
                // Default: RIGHT of the cell
                bubble.style.left      = '100%';
                bubble.style.right     = 'auto';
                bubble.style.marginLeft = GAP + 'px';
                if (pointer) {
                    pointer.className = 'bubble-pointer absolute right-full top-1 w-0 h-0 border-r-4 border-t-4 border-t-transparent border-r-gray-50';
                    pointer.style.marginLeft = '';
                    pointer.style.marginRight = '-1px';
                }
            }

            // Default: align top with the input
            bubble.style.top    = '0';
            bubble.style.bottom = 'auto';

            // After render, check bottom overflow and shift up if needed
            requestAnimationFrame(function () {
                const bubbleRect = bubble.getBoundingClientRect();
                if (bubbleRect.bottom > window.innerHeight - GAP) {
                    const overflow = bubbleRect.bottom - window.innerHeight + GAP;
                    bubble.style.top = (-overflow) + 'px';
                }
            });
        }

        input.addEventListener('focus', function () {
            bubble.classList.remove('hidden');
            positionBubble();
        });

        input.addEventListener('blur', function () {
            bubble.classList.add('hidden');
        });
    });
});
</script>
</x-app-layout>
