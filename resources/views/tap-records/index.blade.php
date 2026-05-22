<x-app-layout>
    <x-slot:title>Tap Records</x-slot:title>
    <x-slot:header>Tap Records</x-slot:header>

    <div class="mb-6 flex flex-col gap-3 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Tap Records</h1>
            <p class="mt-1 text-sm text-slate-600">Temporary view of the tap_records table.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <div class="rounded-lg bg-slate-100 px-4 py-2 text-sm text-slate-600">
                Total rows: <span class="font-semibold text-slate-900">{{ $tapRecords->total() }}</span>
            </div>
            <form method="POST" action="{{ route('tap-records.sync') }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    Sync Attendance
                </button>
            </form>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4">
            <h2 class="text-lg font-semibold text-slate-900">Add Tap Record</h2>
            <p class="text-sm text-slate-500">Temporary manual entry form for the tap_records table.</p>
        </div>

        <form method="POST" action="{{ route('tap-records.store') }}" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @csrf

            <label class="block">
                <span class="mb-1 block text-sm font-medium text-slate-700">Employee</span>
                <select name="employee_id" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Select employee</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>
                            {{ $employee->full_name }} ({{ $employee->employee_code }})
                        </option>
                    @endforeach
                </select>
                @error('employee_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </label>

            <label class="block">
                <span class="mb-1 block text-sm font-medium text-slate-700">Machine ID</span>
                <input type="number" name="machine_id" value="{{ old('machine_id', 1) }}" min="1" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('machine_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </label>

            <label class="block">
                <span class="mb-1 block text-sm font-medium text-slate-700">Company ID</span>
                <input type="number" name="company_id" value="{{ old('company_id', 1) }}" min="1" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('company_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </label>

            <label class="block">
                <span class="mb-1 block text-sm font-medium text-slate-700">Tap Time</span>
                <input type="datetime-local" name="tap_time" value="{{ old('tap_time', now()->format('Y-m-d\TH:i')) }}" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('tap_time')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </label>

            <label class="block">
                <span class="mb-1 block text-sm font-medium text-slate-700">Function Code</span>
                <input type="number" name="function" value="{{ old('function', 0) }}" min="0" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('function')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </label>

            <label class="block">
                <span class="mb-1 block text-sm font-medium text-slate-700">Status Code</span>
                <input type="number" name="status" value="{{ old('status', 1) }}" min="0" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('status')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </label>

            <div class="md:col-span-2 lg:col-span-3 flex justify-end">
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                    Add Tap Record
                </button>
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="whitespace-nowrap px-4 py-3 font-semibold">Employee</th>
                        <th class="whitespace-nowrap px-4 py-3 font-semibold">Code</th>
                        <th class="whitespace-nowrap px-4 py-3 font-semibold">Masterlist ID</th>
                        <th class="whitespace-nowrap px-4 py-3 font-semibold">Machine ID</th>
                        <th class="whitespace-nowrap px-4 py-3 font-semibold">Company ID</th>
                        <th class="whitespace-nowrap px-4 py-3 font-semibold">Time</th>
                        <th class="whitespace-nowrap px-4 py-3 font-semibold">Function</th>
                        <th class="whitespace-nowrap px-4 py-3 font-semibold">Status</th>
                        <th class="whitespace-nowrap px-4 py-3 font-semibold">Created By</th>
                        <th class="whitespace-nowrap px-4 py-3 font-semibold">Updated By</th>
                        <th class="whitespace-nowrap px-4 py-3 font-semibold">Deleted By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($tapRecords as $record)
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-4 py-3 align-top text-slate-700">{{ $record->employee_label }}</td>
                            <td class="whitespace-nowrap px-4 py-3 align-top text-slate-700">{{ $record->employee_code_label }}</td>
                            <td class="whitespace-nowrap px-4 py-3 align-top text-slate-700">{{ $record->masterlist_id ?? 'NULL' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 align-top text-slate-700">{{ $record->machine_id ?? 'NULL' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 align-top text-slate-700">{{ $record->company_id ?? 'NULL' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 align-top text-slate-700">{{ $record->time_label }}</td>
                            <td class="whitespace-nowrap px-4 py-3 align-top text-slate-700">{{ $record->function_label }}</td>
                            <td class="whitespace-nowrap px-4 py-3 align-top text-slate-700">{{ $record->status_label }}</td>
                            <td class="whitespace-nowrap px-4 py-3 align-top text-slate-700">{{ $record->created_by_label }}</td>
                            <td class="whitespace-nowrap px-4 py-3 align-top text-slate-700">{{ $record->updated_by_label }}</td>
                            <td class="whitespace-nowrap px-4 py-3 align-top text-slate-700">{{ $record->deleted_by_label }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-4 py-10 text-center text-slate-500">
                                No tap records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 px-4 py-4">
            {{ $tapRecords->links() }}
        </div>
    </div>
</x-app-layout>
