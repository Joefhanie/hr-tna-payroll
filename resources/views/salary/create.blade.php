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
                <label for="salary_type" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Pay Frequency *</label>
                <select id="salary_type" name="salary_type" required class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 transition focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                    <option value="">Select frequency...</option>
                    @foreach ($payFrequencies as $key => $label)
                        <option value="{{ $key }}" @selected(old('salary_type') == $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('salary_type')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
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
        <div class="mt-8 flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                Create Salary Record
            </button>
            <a href="{{ route('salary.show', $employee) }}" class="rounded-lg border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
                Cancel
            </a>
        </div>
    </form>
</x-app-layout>
