<x-app-layout>
    <x-slot:title>Shift Schedule</x-slot:title>
    <x-slot:header>Shift Schedule</x-slot:header>

    <style>
        /* Force styling for checked day pills without relying on Tailwind compiler */
        input[type="checkbox"]:checked + span.day-pill {
            border-color: #3b82f6 !important;
            background-color: #eff6ff !important;
            color: #1d4ed8 !important;
        }
    </style>

    <div class="space-y-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Shift Schedule</h1>
                <p class="mt-1 text-sm text-slate-600">Manage employee shift schedules.</p>
            </div>
            <button type="button" onclick="const m = document.getElementById('addShiftModal'); m.classList.remove('hidden'); m.classList.add('flex');" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                <i class="ti ti-plus"></i>
                Add Shift
            </button>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">CODE</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">EMPLOYEE NAME</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">DEPARTMENT</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">DEFAULT SHIFT</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">WORKING DAYS</th>
                            <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-slate-500">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse($employees as $employee)
                            <tr class="hover:bg-slate-50/50 transition-colors group" id="row-{{ $employee->id }}">
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-500">
                                    {{ $employee->employee_code }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-900">
                                    {{ $employee->full_name }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">
                                    {{ $employee->department->name ?? 'Unassigned' }}
                                </td>
                                
                                <!-- VIEW MODE -->
                                <td class="whitespace-nowrap px-4 py-3 view-mode-{{ $employee->id }}">
                                    <div class="shift-container">
                                        @if($employee->currentShift && $employee->currentShift->shift)
                                            <span class="hidden text-slate-400 italic text-xs no-shift">Not assigned</span>
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700 border border-indigo-100 time-badge">
                                                <i class="ti ti-clock text-indigo-500"></i>
                                                <span class="time-display">
                                                    {{ \Carbon\Carbon::parse($employee->currentShift->shift->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($employee->currentShift->shift->end_time)->format('h:i A') }}
                                                </span>
                                            </span>
                                        @else
                                            <span class="text-slate-400 italic text-xs no-shift">Not assigned</span>
                                            <span class="hidden inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700 border border-indigo-100 time-badge">
                                                <i class="ti ti-clock text-indigo-500"></i>
                                                <span class="time-display"></span>
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-600 view-mode-{{ $employee->id }}">
                                    <div class="flex gap-1 days-display">
                                        @if($employee->currentShift && $employee->currentShift->shift && is_array($employee->currentShift->shift->days_of_week))
                                            @foreach(['Mon'=>'M', 'Tue'=>'T', 'Wed'=>'W', 'Thu'=>'T', 'Fri'=>'F', 'Sat'=>'S', 'Sun'=>'S'] as $day => $label)
                                                @if(in_array($day, $employee->currentShift->shift->days_of_week))
                                                    <span class="flex h-6 w-6 items-center justify-center rounded bg-blue-100 text-xs font-semibold text-blue-700">{{ $label }}</span>
                                                @else
                                                    <span class="flex h-6 w-6 items-center justify-center rounded bg-slate-100 text-xs font-semibold text-slate-400">{{ $label }}</span>
                                                @endif
                                            @endforeach
                                        @else
                                            <span class="text-slate-400 italic text-xs no-days">Not assigned</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-slate-600 view-mode-{{ $employee->id }}">
                                    <button type="button" onclick="toggleEdit({{ $employee->id }})" class="inline-flex items-center justify-center rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-blue-600 transition-colors" title="Edit Shift">
                                        <i class="ti ti-pencil text-lg"></i>
                                    </button>
                                </td>

                                <!-- EDIT MODE -->
                                <td class="whitespace-nowrap px-4 py-3 edit-mode-{{ $employee->id }} hidden">
                                    <div class="flex items-center gap-2">
                                        <input type="time" name="start_time_{{ $employee->id }}" value="{{ $employee->currentShift && $employee->currentShift->shift ? \Carbon\Carbon::parse($employee->currentShift->shift->start_time)->format('H:i') : '' }}" class="w-[105px] rounded border border-slate-300 px-2 py-1 text-xs focus:border-blue-500 focus:outline-none text-slate-700">
                                        <span class="text-slate-400">-</span>
                                        <input type="time" name="end_time_{{ $employee->id }}" value="{{ $employee->currentShift && $employee->currentShift->shift ? \Carbon\Carbon::parse($employee->currentShift->shift->end_time)->format('H:i') : '' }}" class="w-[105px] rounded border border-slate-300 px-2 py-1 text-xs focus:border-blue-500 focus:outline-none text-slate-700">
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-600 edit-mode-{{ $employee->id }} hidden">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day)
                                            <label class="cursor-pointer">
                                                <input type="checkbox" name="days_{{ $employee->id }}[]" value="{{ $day }}" class="peer hidden" {{ ($employee->currentShift && $employee->currentShift->shift && is_array($employee->currentShift->shift->days_of_week) && in_array($day, $employee->currentShift->shift->days_of_week)) ? 'checked' : '' }}>
                                                <span class="day-pill inline-flex items-center rounded border border-slate-200 bg-slate-50 px-2 py-1 text-[10px] font-semibold text-slate-500 transition hover:bg-slate-100 peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700">{{ substr($day, 0, 3) }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-slate-600 edit-mode-{{ $employee->id }} hidden">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" onclick="toggleEdit({{ $employee->id }})" class="inline-flex items-center justify-center rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors" title="Cancel">
                                            <i class="ti ti-x text-lg"></i>
                                        </button>
                                        <button type="button" onclick="createShift({{ $employee->id }})" class="inline-flex items-center justify-center rounded-lg p-1.5 text-blue-600 hover:bg-blue-50 hover:text-blue-700 transition-colors" title="Create Shift">
                                            <i class="ti ti-check text-lg"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                    No employees found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Add Shift Modal -->
    <div id="addShiftModal" class="hidden fixed inset-0 z-30 items-center justify-center bg-black/40 p-4 transition-opacity" style="padding-left: var(--sidebar-width);">
        <div class="w-full max-w-md rounded-xl bg-white shadow-xl relative">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Add Shift Schedule</h3>
                <button onclick="const m = document.getElementById('addShiftModal'); m.classList.add('hidden'); m.classList.remove('flex');" class="text-slate-400 hover:text-slate-600 transition">
                    <i class="ti ti-x text-xl"></i>
                </button>
            </div>
                <form action="#" onsubmit="event.preventDefault(); handleAddShiftSubmit();" class="p-6">
                    @csrf
                    <div class="space-y-5">
                        <div class="relative">
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Assign to Employee</label>
                            <div class="relative">
                                <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="text" id="employee_search_input" style="padding-left: 2.25rem;" autocomplete="off" placeholder="Search employee by name or code..." class="w-full rounded-lg border border-slate-300 pr-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-700" required>
                                <input type="hidden" id="modal_employee_id" required>
                            </div>
                            <div id="employee_suggestions" class="absolute left-0 right-0 top-full z-10 mt-1 max-h-48 overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg hidden">
                                @foreach($employees as $emp)
                                    <button type="button" class="suggestion-item w-full px-4 py-2 text-left text-sm hover:bg-slate-50 focus:bg-slate-50 focus:outline-none transition-colors text-slate-700" 
                                            data-id="{{ $emp->id }}" 
                                            data-name="{{ $emp->full_name }}" 
                                            data-search="{{ strtolower($emp->full_name . ' ' . $emp->employee_code) }}">
                                        {{ $emp->full_name }}
                                    </button>
                                @endforeach
                                <div id="no_suggestions" class="hidden px-4 py-3 text-sm text-slate-500 text-center">No employee found.</div>
                            </div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Shift Schedule</label>
                            <div class="flex items-center gap-3">
                                <input type="time" name="start_time" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-700" required>
                                <span class="text-slate-400 font-medium">to</span>
                                <input type="time" name="end_time" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-700" required>
                            </div>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Working Days</label>
                            <div class="flex flex-wrap gap-2">
                                @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day)
                                    <label class="cursor-pointer">
                                        <input type="checkbox" name="days[]" value="{{ $day }}" class="peer hidden" @if(!in_array($day, ['Sat', 'Sun'])) checked @endif>
                                        <span class="day-pill inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700">{{ $day }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="mt-8 flex justify-end gap-3">
                        <button type="button" onclick="const m = document.getElementById('addShiftModal'); m.classList.add('hidden'); m.classList.remove('flex');" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700"> Create Shift</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toastNotification" class="fixed top-6 right-6 z-[60] flex translate-y-[-150%] opacity-0 items-center gap-3 rounded-lg border border-green-200 bg-white px-4 py-3 text-slate-800 shadow-xl transition-all duration-300 ease-out">
        <i class="ti ti-circle-check-filled text-2xl text-green-500"></i>
        <div>
            <h4 class="text-sm font-bold text-slate-900">Success!</h4>
            <p class="text-xs text-slate-600">Shift schedule successfully created.</p>
        </div>
        <button onclick="hideToast()" class="ml-2 rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
            <i class="ti ti-x text-lg"></i>
        </button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('employee_search_input');
            const suggestionsContainer = document.getElementById('employee_suggestions');
            const suggestionItems = document.querySelectorAll('.suggestion-item');
            const hiddenIdInput = document.getElementById('modal_employee_id');
            const noSuggestions = document.getElementById('no_suggestions');

            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    const term = e.target.value.toLowerCase().trim();
                    hiddenIdInput.value = ''; // Reset on typing
                    
                    if (!term) {
                        suggestionsContainer.classList.add('hidden');
                        return;
                    }
                    
                    suggestionsContainer.classList.remove('hidden');
                    let found = false;
                    
                    suggestionItems.forEach(item => {
                        if (item.getAttribute('data-search').includes(term)) {
                            item.classList.remove('hidden');
                            found = true;
                        } else {
                            item.classList.add('hidden');
                        }
                    });
                    
                    if (found) {
                        noSuggestions.classList.add('hidden');
                    } else {
                        noSuggestions.classList.remove('hidden');
                    }
                });

                suggestionItems.forEach(item => {
                    item.addEventListener('click', function() {
                        searchInput.value = this.getAttribute('data-name');
                        hiddenIdInput.value = this.getAttribute('data-id');
                        suggestionsContainer.classList.add('hidden');
                    });
                });

                document.addEventListener('click', function(e) {
                    if (!searchInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                        suggestionsContainer.classList.add('hidden');
                    }
                });
            }
        });

        function handleAddShiftSubmit() {
            const employeeId = document.getElementById('modal_employee_id').value;
            const startTime = document.querySelector('#addShiftModal input[name="start_time"]').value;
            const endTime = document.querySelector('#addShiftModal input[name="end_time"]').value;
            
            // Get checked days
            const checkedDays = Array.from(document.querySelectorAll('#addShiftModal input[name="days[]"]:checked')).map(cb => cb.value);
            
            // Sync to the row's hidden inputs
            const rowStartTime = document.querySelector(`input[name="start_time_${employeeId}"]`);
            const rowEndTime = document.querySelector(`input[name="end_time_${employeeId}"]`);
            if (rowStartTime && rowEndTime) {
                rowStartTime.value = startTime;
                rowEndTime.value = endTime;
            }
            
            const rowCheckboxes = document.querySelectorAll(`input[name="days_${employeeId}[]"]`);
            rowCheckboxes.forEach(cb => {
                cb.checked = checkedDays.includes(cb.value);
            });
            
            createShift(employeeId, true);

            const m = document.getElementById('addShiftModal');
            m.classList.add('hidden');
            m.classList.remove('flex');
            showToast();
            
            document.querySelector('#addShiftModal form').reset();
        }

        function showToast() {
            const toast = document.getElementById('toastNotification');
            toast.classList.remove('translate-y-[-150%]', 'opacity-0');
            toast.classList.add('translate-y-0', 'opacity-100');
            setTimeout(hideToast, 4000);
        }

        function hideToast() {
            const toast = document.getElementById('toastNotification');
            toast.classList.add('translate-y-[-150%]', 'opacity-0');
            toast.classList.remove('translate-y-0', 'opacity-100');
        }

        function toggleEdit(id) {
            document.querySelectorAll('.view-mode-' + id).forEach(el => el.classList.toggle('hidden'));
            document.querySelectorAll('.edit-mode-' + id).forEach(el => el.classList.toggle('hidden'));
        }

        function createShift(id, fromModal = false) {
            // Get inputs
            const startTimeInput = document.querySelector(`input[name="start_time_${id}"]`);
            const endTimeInput = document.querySelector(`input[name="end_time_${id}"]`);
            const checkboxes = Array.from(document.querySelectorAll(`input[name="days_${id}[]"]:checked`));
            const checkedDays = checkboxes.map(cb => cb.value);

            if (!startTimeInput || !endTimeInput || !startTimeInput.value || !endTimeInput.value) {
                alert('Please select start and end time');
                return;
            }

            // Get CSRF token from meta tag
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            // Perform AJAX request
            fetch('{{ route("timekeeping.shift-schedule.save") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    employee_id: id,
                    start_time: startTimeInput.value,
                    end_time: endTimeInput.value,
                    days: checkedDays
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (startTimeInput && endTimeInput) {
                        const formatTime = (time) => {
                            if (!time) return '';
                            let [h, m] = time.split(':');
                            let ampm = h >= 12 ? 'PM' : 'AM';
                            h = h % 12 || 12;
                            return `${String(h).padStart(2, '0')}:${m} ${ampm}`;
                        };
                        
                        const timeDisplay = document.querySelector(`.view-mode-${id} .time-display`);
                        if (timeDisplay) {
                            timeDisplay.innerText = `${formatTime(startTimeInput.value)} - ${formatTime(endTimeInput.value)}`;
                            const noShift = document.querySelector(`.view-mode-${id} .no-shift`);
                            const timeBadge = document.querySelector(`.view-mode-${id} .time-badge`);
                            if (noShift) noShift.classList.add('hidden');
                            if (timeBadge) timeBadge.classList.remove('hidden');
                        }
                    }
                    
                    // Update days display
                    const daysContainer = document.querySelector(`.view-mode-${id} .days-display`);
                    
                    if (daysContainer) {
                        daysContainer.innerHTML = '';
                        
                        if (checkedDays.length === 0) {
                            const noDaysSpan = document.createElement('span');
                            noDaysSpan.className = 'text-slate-400 italic text-xs no-days';
                            noDaysSpan.innerText = 'Not assigned';
                            daysContainer.appendChild(noDaysSpan);
                        } else {
                            const allDays = [
                                { value: 'Mon', label: 'M' },
                                { value: 'Tue', label: 'T' },
                                { value: 'Wed', label: 'W' },
                                { value: 'Thu', label: 'T' },
                                { value: 'Fri', label: 'F' },
                                { value: 'Sat', label: 'S' },
                                { value: 'Sun', label: 'S' }
                            ];
                            
                            allDays.forEach(day => {
                                const isChecked = checkedDays.includes(day.value);
                                
                                const span = document.createElement('span');
                                span.className = isChecked 
                                    ? 'flex h-6 w-6 items-center justify-center rounded bg-blue-100 text-xs font-semibold text-blue-700'
                                    : 'flex h-6 w-6 items-center justify-center rounded bg-slate-100 text-xs font-semibold text-slate-400';
                                span.innerText = day.label;
                                daysContainer.appendChild(span);
                            });
                        }
                    }

                    // Hide edit mode or ensure view mode
                    if (!fromModal) {
                        toggleEdit(id);
                        showToast();
                    } else {
                        document.querySelectorAll('.view-mode-' + id).forEach(el => el.classList.remove('hidden'));
                        document.querySelectorAll('.edit-mode-' + id).forEach(el => el.classList.add('hidden'));
                    }
                } else {
                    alert('Error saving shift');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving shift');
            });
        }
    </script>
</x-app-layout>
