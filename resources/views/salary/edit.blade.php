<x-app-layout>
    <x-slot:title>Edit Salary Record - {{ $employee->full_name }}</x-slot:title>
    <x-slot:header>Edit Salary Record</x-slot:header>

    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Edit Salary Record</h1>
            <p class="text-sm text-slate-500">Update salary information for {{ $employee->full_name }}</p>
        </div>
        <a href="{{ route('salary.show', $employee) }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">Back</a>
    </div>

    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4">
            <p class="font-semibold text-red-700 mb-2">Please fix the following errors:</p>
            <ul class="space-y-1 text-sm text-red-600">
                @foreach ($errors->all() as $error)
                    <li>• {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('salary.update', $salaryRecord) }}" class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
        @csrf
        @method('PUT')

        <div class="grid gap-6 sm:grid-cols-2">
            <!-- Salary Amount -->
            <div>
                <label for="amount" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Salary Amount *</label>
                <input type="number" id="amount" name="amount" step="0.01" min="0" value="{{ old('amount', $salaryRecord->amount) }}" required class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 placeholder-slate-400 transition focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20" placeholder="50000.00">
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
                        <option value="{{ $key }}" @selected(old('pay_frequency', $salaryRecord->pay_frequency) == $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('pay_frequency')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Daily Rate Divisor -->
            <div>
                <label for="daily_divisor" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Daily Rate Divisor</label>
                <input type="number" id="daily_divisor" name="daily_divisor" step="0.0001" min="1" value="{{ old('daily_divisor', $salaryRecord->daily_divisor) }}" required class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 placeholder-slate-400 transition focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20" placeholder="21.8">
                <p class="mt-1 text-xs text-slate-500">Usually 21.8 (5-day week) or 26.1667 (6-day week)</p>
                @error('daily_divisor')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="mb-4">
                    <h2 class="text-sm font-semibold text-slate-800">Attendance Rate Overrides</h2>
                    <p class="mt-1 text-xs text-slate-500">Set employee-specific attendance bonus and deduction multipliers. Leave the defaults unless this employee needs a custom policy.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="attendance_overtime_multiplier" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Overtime Pay Multiplier</label>
                        <input type="number" id="attendance_overtime_multiplier" name="attendance_overtime_multiplier" step="0.0001" min="0" value="{{ old('attendance_overtime_multiplier', $salaryRecord->attendance_overtime_multiplier ?? $global->attendance_overtime_multiplier ?? 1.25) }}" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <p class="mt-1 text-xs text-slate-500">Default 1.2500 = 125% of hourly rate.</p>
                        @error('attendance_overtime_multiplier')
                            <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="attendance_night_differential_multiplier" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Night Differential Multiplier</label>
                        <input type="number" id="attendance_night_differential_multiplier" name="attendance_night_differential_multiplier" step="0.0001" min="0" value="{{ old('attendance_night_differential_multiplier', $salaryRecord->attendance_night_differential_multiplier ?? $global->attendance_night_differential_multiplier ?? 0.10) }}" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <p class="mt-1 text-xs text-slate-500">Default 0.1000 = 10% of hourly rate.</p>
                        @error('attendance_night_differential_multiplier')
                            <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="attendance_late_deduction_multiplier" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Late Deduction Multiplier</label>
                        <input type="number" id="attendance_late_deduction_multiplier" name="attendance_late_deduction_multiplier" step="0.0001" min="0" value="{{ old('attendance_late_deduction_multiplier', $salaryRecord->attendance_late_deduction_multiplier ?? $global->attendance_late_deduction_multiplier ?? 1.00) }}" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <p class="mt-1 text-xs text-slate-500">Default 1.0000 = full late deduction.</p>
                        @error('attendance_late_deduction_multiplier')
                            <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="attendance_undertime_deduction_multiplier" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Undertime Deduction Multiplier</label>
                        <input type="number" id="attendance_undertime_deduction_multiplier" name="attendance_undertime_deduction_multiplier" step="0.0001" min="0" value="{{ old('attendance_undertime_deduction_multiplier', $salaryRecord->attendance_undertime_deduction_multiplier ?? $global->attendance_undertime_deduction_multiplier ?? 1.00) }}" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <p class="mt-1 text-xs text-slate-500">Default 1.0000 = full undertime deduction.</p>
                        @error('attendance_undertime_deduction_multiplier')
                            <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="attendance_absence_deduction_multiplier" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Absence Deduction Multiplier</label>
                        <input type="number" id="attendance_absence_deduction_multiplier" name="attendance_absence_deduction_multiplier" step="0.0001" min="0" value="{{ old('attendance_absence_deduction_multiplier', $salaryRecord->attendance_absence_deduction_multiplier ?? $global->attendance_absence_deduction_multiplier ?? 1.00) }}" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <p class="mt-1 text-xs text-slate-500">Default 1.0000 = full absence deduction.</p>
                        @error('attendance_absence_deduction_multiplier')
                            <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Effective Date -->
            <div>
                <label for="effective_date" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Effective From *</label>
                <input type="date" id="effective_date" name="effective_date" value="{{ old('effective_date', $salaryRecord->effective_date->toDateString()) }}" required class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 transition focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                @error('effective_date')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- End Date -->
            <div>
                <label for="end_date" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">End Date (Optional)</label>
                <input type="date" id="end_date" name="end_date" value="{{ old('end_date', $salaryRecord->end_date?->toDateString()) }}" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 placeholder-slate-400 transition focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                <p class="mt-1 text-xs text-slate-500">Leave empty if this salary is currently active</p>
                @error('end_date')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Reason -->
            <div class="sm:col-span-2">
                <label for="reason" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Reason (Optional)</label>
                <input type="text" id="reason" name="reason" value="{{ old('reason', $salaryRecord->reason) }}" placeholder="e.g., Raise, Promotion, Adjustment" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 placeholder-slate-400 transition focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                @error('reason')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Notes -->
            <div class="sm:col-span-2">
                <label for="notes" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Notes (Optional)</label>
                <textarea id="notes" name="notes" rows="3" placeholder="Additional notes..." class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 placeholder-slate-400 transition focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20">{{ old('notes', $salaryRecord->notes) }}</textarea>
                @error('notes')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Buttons -->
        <div class="mt-8 flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                Update Salary Record
            </button>
            <a href="{{ route('salary.show', $employee) }}" class="rounded-lg border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
                Cancel
            </a>
        </div>
    </form>
</x-app-layout>
