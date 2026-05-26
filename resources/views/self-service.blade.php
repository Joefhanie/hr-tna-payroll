<x-app-layout>
    <x-slot:title>Self-Service</x-slot:title>
    <x-slot:header>Self-Service</x-slot:header>

    <div class="mb-6 flex items-center justify-between border-b border-slate-200 pb-6">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Self-Service</h1>
            <p class="mt-1 text-sm text-slate-600">Browse employee and supervisor self-service pages.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('self-service') }}" class="mb-6 flex flex-col sm:flex-row sm:items-center gap-3" id="selfServiceFilters">
        <div class="relative w-full sm:max-w-xs rounded-lg bg-white border border-slate-200 sm:border-0">
            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search employees..." class="w-full rounded-lg border-0 py-2.5 sm:py-2 pl-10 pr-3 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500" data-auto-submit="search" />
        </div>
        <div class="grid grid-cols-2 gap-3 sm:flex sm:items-center sm:gap-3 w-full sm:w-auto">
            <select name="type" class="w-full sm:w-auto rounded-lg border border-slate-300 bg-white px-3 py-2.5 sm:py-2 text-sm text-slate-700 focus:outline-none focus:ring-1 focus:ring-blue-500" data-auto-submit="change">
                <option value="">All roles</option>
                @foreach ($requestTypes as $type)
                    <option value="{{ $type }}" @selected($filters['type'] === $type)>{{ $type }}</option>
                @endforeach
            </select>
            <select name="status" class="rounded-lg border border-slate-300 bg-white px-3 py-2.5 sm:py-2 text-sm text-slate-700 focus:outline-none focus:ring-1 focus:ring-blue-500 w-full sm:w-auto" data-auto-submit="change">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <a href="{{ route('self-service', array_filter([...$filters, 'export' => 1])) }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2.5 sm:py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export
        </a>
    </form>

    @php
        $badge = fn($status) => match($status) {
            'Active' => 'badge-green',
            'Probationary' => 'badge-blue',
            'On Leave' => 'badge-amber',
            'Resigned', 'Terminated' => 'badge-red',
            default => 'badge-gray',
        };
    @endphp

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table id="selfServiceTable" class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Code</th>
                        <th class="px-4 py-3">Employee</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3">Hire Date</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($requests as $request)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $request['code'] }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if(!empty($request['profile_picture']))
                                        <div class="h-8 w-8 overflow-hidden rounded-full bg-slate-100">
                                            <img src="{{ route('media.file', ['path' => ltrim($request['profile_picture'], '/')]) }}" alt="{{ $request['employee'] }}" class="h-8 w-8 object-cover">
                                        </div>
                                    @else
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700">
                                            {{ collect(explode(' ', $request['employee']))->map(fn($name) => $name[0])->join('') }}
                                        </div>
                                    @endif
                                    <div>
                                        <p class="font-medium text-slate-900">{{ $request['employee'] }}</p>
                                        <p class="text-xs text-slate-500">{{ $request['email'] }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $request['type'] }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $request['date'] }}</td>
                            <td class="px-4 py-3">
                                <span class="badge {{ $badge($request['status']) }}">{{ $request['status'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="flex items-center justify-center">
                                    <a href="{{ route('self-service.profile', $request['id']) }}" class="text-slate-600 transition hover:text-slate-900">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">No employees matched the current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-table-pagination target="selfServiceTable" itemsPerPage="10" />
    </div>

    <script>
        const selfServiceFilters = document.getElementById('selfServiceFilters');

        if (selfServiceFilters) {
            let searchDebounceTimer = null;

            selfServiceFilters.querySelectorAll('[data-auto-submit="change"]').forEach((field) => {
                field.addEventListener('change', () => {
                    selfServiceFilters.submit();
                });
            });

            selfServiceFilters.querySelectorAll('[data-auto-submit="search"]').forEach((field) => {
                field.addEventListener('input', () => {
                    clearTimeout(searchDebounceTimer);
                    searchDebounceTimer = setTimeout(() => {
                        selfServiceFilters.submit();
                    }, 300);
                });
            });
        }
    </script>
</x-app-layout>
