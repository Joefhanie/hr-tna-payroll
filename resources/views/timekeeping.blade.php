<x-app-layout>
    <x-slot:title>Timekeeping</x-slot:title>
    <x-slot:header>Timekeeping</x-slot:header>

    <div>
        <h1 class="text-2xl font-semibold">Timekeeping</h1>
        <p class="text-sm text-slate-500">Monitor clock-ins, clock-outs, and attendance health.</p>
    </div>

    @php
        $entries = [
            ['name' => 'Ana Reyes', 'in' => '08:01', 'out' => '17:07', 'status' => 'Present'],
            ['name' => 'Mark Santos', 'in' => '08:22', 'out' => '17:10', 'status' => 'Late'],
            ['name' => 'Sophia Lim', 'in' => '—', 'out' => '—', 'status' => 'Absent'],
        ];
    @endphp

    <div class="card mb-6 p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1 min-w-0">
                <label class="mb-1 block text-xs font-medium text-slate-600">Search</label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        type="search"
                        id="timekeeping-search"
                        placeholder="Search employee, clock in, clock out, status..."
                        autocomplete="off"
                        class="w-full rounded-lg border border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-700 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                    >
                </div>
            </div>

            <button
                type="button"
                id="timekeeping-clear"
                class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                Clear
            </button>
        </div>
    </div>

    <div class="card overflow-hidden">
        <table id="timekeeping-table" class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Employee</th>
                    <th class="px-4 py-3">Clock In</th>
                    <th class="px-4 py-3">Clock Out</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($entries as $entry)
                    <tr class="timekeeping-row" data-filter-hidden="false">
                        <td class="px-4 py-3 font-medium">{{ $entry['name'] }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $entry['in'] }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $entry['out'] }}</td>
                        <td class="px-4 py-3"><span class="badge {{ $entry['status'] === 'Present' ? 'badge-green' : ($entry['status'] === 'Late' ? 'badge-amber' : 'badge-red') }}">{{ $entry['status'] }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <script>
        (() => {
            const searchInput = document.getElementById('timekeeping-search');
            const clearButton = document.getElementById('timekeeping-clear');
            const rows = document.querySelectorAll('#timekeeping-table .timekeeping-row');

            function filterRows() {
                const term = (searchInput?.value || '').trim().toLowerCase();

                rows.forEach((row) => {
                    const searchable = row.textContent.toLowerCase();
                    const isVisible = !term || searchable.includes(term);

                    row.setAttribute('data-filter-hidden', isVisible ? 'false' : 'true');
                    row.classList.toggle('hidden', !isVisible);
                });
            }

            searchInput?.addEventListener('input', filterRows);
            searchInput?.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                }
            });

            clearButton?.addEventListener('click', () => {
                if (!searchInput) {
                    return;
                }

                searchInput.value = '';
                filterRows();
                searchInput.focus();
            });

            filterRows();
        })();
    </script>
</x-app-layout>
