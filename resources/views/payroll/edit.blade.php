<x-app-layout>
    <x-slot:title>Edit Payroll - {{ $payRun->name }}</x-slot:title>
    <x-slot:header>Edit Payroll</x-slot:header>

    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Edit Payroll</h1>
            <p class="text-sm text-slate-500">Update payroll details for {{ $payRun->name }}</p>
        </div>
        <a href="{{ route('payroll.show', $payRun) }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">Back</a>
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

    <form method="POST" action="{{ route('payroll.update', $payRun) }}" class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm max-w-2xl">
        @csrf
        @method('PUT')

        <div class="grid gap-6 sm:grid-cols-2">
            <!-- Pay Period Start -->
            <div>
                <label for="period_start" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Period Start *</label>
                <input type="date" id="period_start" name="period_start" value="{{ old('period_start', $payRun->period_start->toDateString()) }}" required class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 transition focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                @error('period_start')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Pay Period End -->
            <div>
                <label for="period_end" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Period End *</label>
                <input type="date" id="period_end" name="period_end" value="{{ old('period_end', $payRun->period_end->toDateString()) }}" required class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 transition focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                @error('period_end')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Pay Date -->
            <div>
                <label for="pay_date" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Pay Date *</label>
                <input type="date" id="pay_date" name="pay_date" value="{{ old('pay_date', $payRun->pay_date->toDateString()) }}" required class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 transition focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                @error('pay_date')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Status -->
            <div>
                <label for="status" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Status *</label>
                <select id="status" name="status" required class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 transition focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                    <option value="">Select status...</option>
                    <option value="1" @selected(old('status', $payRun->status) == 1)>Draft</option>
                    <option value="2" @selected(old('status', $payRun->status) == 2)>Processing</option>
                    <option value="3" @selected(old('status', $payRun->status) == 3)>Completed</option>
                    <option value="4" @selected(old('status', $payRun->status) == 4)>Cancelled</option>
                </select>
                @error('status')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-8 flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                Update Payroll
            </button>
            <a href="{{ route('payroll.show', $payRun) }}" class="rounded-lg border border-slate-300 px-6 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                Cancel
            </a>
        </div>
    </form>
</x-app-layout>
