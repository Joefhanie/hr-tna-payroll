<x-app-layout>
    <x-slot:title>Add Salary Record - {{ $employee->full_name }}</x-slot:title>
    <x-slot:header>Add Salary Record</x-slot:header>

    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Add Salary Record</h1>
            <p class="text-sm text-slate-500">Create a new salary record for {{ $employee->full_name }}</p>
        </div>
        <a href="{{ route('salary.show', $employee) }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">Back</a>
    </div>

    <form method="POST" action="{{ route('salary.store', $employee) }}" class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
        @csrf

        <div class="grid gap-6 sm:grid-cols-2">
            <!-- Salary Amount -->
            <div>
                <label for="amount" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Salary Amount *</label>
                <input type="number" id="amount" name="amount" step="0.01" min="0" value="{{ old('amount') }}" required class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 placeholder-slate-400 transition focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20" placeholder="50000.00">
                @error('amount')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Pay Frequency -->
            <div>
                <label for="pay_frequency" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Pay Frequency *</label>
                <select id="pay_frequency" name="pay_frequency" required class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 transition focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                    <option value="">Select frequency...</option>
                    @foreach ($payFrequencies as $key => $label)
                        <option value="{{ $key }}" @selected(old('pay_frequency') == $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('pay_frequency')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Daily Rate Divisor -->
            <div>
                <label for="daily_divisor" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Daily Rate Divisor</label>
                <input type="number" id="daily_divisor" name="daily_divisor" step="0.01" min="1" value="{{ old('daily_divisor', '21.80') }}" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 placeholder-slate-400 transition focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20" placeholder="21.80">
                <p class="mt-1 text-xs text-slate-500">Usually 21.80 (5-day week) or 26.17 (6-day week). <strong>Leave blank for Fixed Rate (no bonuses or deductions).</strong></p>
                @error('daily_divisor')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Attendance Rate Overrides toggle --}}
            <div class="sm:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <label class="flex cursor-pointer items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-800">Attendance Rate Overrides</p>
                        <p class="mt-0.5 text-xs text-slate-500">Set employee-specific multipliers. Leave the defaults unless this employee needs a custom policy.</p>
                    </div>
                    <input type="checkbox" id="attendance_overrides_toggle"
                           class="h-4 w-4 cursor-pointer rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                           @if(old('attendance_overtime_multiplier') !== null) checked @endif
                           onchange="document.getElementById('attendance_overrides_fields').classList.toggle('hidden', !this.checked)">
                </label>

                <div id="attendance_overrides_fields"
                     class="mt-4 grid gap-4 sm:grid-cols-2 {{ old('attendance_overtime_multiplier') !== null ? '' : 'hidden' }}">
                    <div>
                        <label for="attendance_overtime_multiplier" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Overtime Pay Multiplier</label>
                        <input type="number" id="attendance_overtime_multiplier" name="attendance_overtime_multiplier" step="0.01" min="0" value="{{ old('attendance_overtime_multiplier', isset($global->attendance_overtime_multiplier) ? number_format($global->attendance_overtime_multiplier, 2, '.', '') : '1.25') }}" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <p class="mt-1 text-xs text-slate-500">Default 1.25 = 125% of hourly rate.</p>
                        @error('attendance_overtime_multiplier')
                            <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="attendance_night_differential_multiplier" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Night Differential Multiplier</label>
                        <input type="number" id="attendance_night_differential_multiplier" name="attendance_night_differential_multiplier" step="0.01" min="0" value="{{ old('attendance_night_differential_multiplier', isset($global->attendance_night_differential_multiplier) ? number_format($global->attendance_night_differential_multiplier, 2, '.', '') : '0.10') }}" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <p class="mt-1 text-xs text-slate-500">Default 0.10 = 10% of hourly rate.</p>
                        @error('attendance_night_differential_multiplier')
                            <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="attendance_late_deduction_multiplier" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Late Deduction Multiplier</label>
                        <input type="number" id="attendance_late_deduction_multiplier" name="attendance_late_deduction_multiplier" step="0.01" min="0" value="{{ old('attendance_late_deduction_multiplier', isset($global->attendance_late_deduction_multiplier) ? number_format($global->attendance_late_deduction_multiplier, 2, '.', '') : '1.00') }}" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <p class="mt-1 text-xs text-slate-500">Default 1.00 = full late deduction.</p>
                        @error('attendance_late_deduction_multiplier')
                            <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="attendance_undertime_deduction_multiplier" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Undertime Deduction Multiplier</label>
                        <input type="number" id="attendance_undertime_deduction_multiplier" name="attendance_undertime_deduction_multiplier" step="0.01" min="0" value="{{ old('attendance_undertime_deduction_multiplier', isset($global->attendance_undertime_deduction_multiplier) ? number_format($global->attendance_undertime_deduction_multiplier, 2, '.', '') : '1.00') }}" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <p class="mt-1 text-xs text-slate-500">Default 1.00 = full undertime deduction.</p>
                        @error('attendance_undertime_deduction_multiplier')
                            <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="attendance_absence_deduction_multiplier" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Absence Deduction Multiplier</label>
                        <input type="number" id="attendance_absence_deduction_multiplier" name="attendance_absence_deduction_multiplier" step="0.01" min="0" value="{{ old('attendance_absence_deduction_multiplier', isset($global->attendance_absence_deduction_multiplier) ? number_format($global->attendance_absence_deduction_multiplier, 2, '.', '') : '1.00') }}" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <p class="mt-1 text-xs text-slate-500">Default 1.00 = full absence deduction.</p>
                        @error('attendance_absence_deduction_multiplier')
                            <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Effective Date -->
            <div>
                <label for="effective_date" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Effective From *</label>
                <input type="date" id="effective_date" name="effective_date" value="{{ old('effective_date', now()->toDateString()) }}" required class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 transition focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                @error('effective_date')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- End Date -->
            <div>
                <label for="end_date" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">End Date (Optional)</label>
                <input type="date" id="end_date" name="end_date" value="{{ old('end_date') }}" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 placeholder-slate-400 transition focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                <p class="mt-1 text-xs text-slate-500">Leave empty if this salary is currently active</p>
                @error('end_date')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Reason -->
            <div class="sm:col-span-2">
                <label for="reason" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Reason (Optional)</label>
                <input type="text" id="reason" name="reason" value="{{ old('reason') }}" placeholder="e.g., Raise, Promotion, Adjustment" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 placeholder-slate-400 transition focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                @error('reason')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Notes -->
            <div class="sm:col-span-2">
                <label for="notes" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Notes (Optional)</label>
                <textarea id="notes" name="notes" rows="3" placeholder="Additional notes..." class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 placeholder-slate-400 transition focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20">{{ old('notes') }}</textarea>
                @error('notes')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Buttons -->
        <div class="mt-8 flex items-center gap-3 justify-end">
            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                Create Salary Record
            </button>
            <a href="{{ route('salary.show', $employee) }}" class="rounded-lg border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
                Cancel
            </a>
        </div>
    </form>
</x-app-layout>
