<x-app-layout>
    <x-slot:title>Work Assignment</x-slot:title>
    <x-slot:header>Work Assignment</x-slot:header>

    {{-- ── Page Header ── --}}
    <div class="mb-6 flex items-center justify-between pb-6 border-b border-slate-200">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Work Assignment</h1>
            <p class="mt-1 text-sm text-slate-600">View and manage employee supervisor and work assignment records.</p>
        </div>
        <button type="button" id="openWaModal"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
            <i class="ti ti-briefcase text-base"></i>
            Work Assignment
        </button>
    </div>

    {{-- ── Toolbar ── --}}
    <div class="mb-6 flex items-center gap-3">
        <div class="relative flex-1 max-w-xs bg-white rounded-lg">
            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input id="wa-search" type="search" placeholder="Search employees…"
                   class="w-full rounded-lg border border-slate-200 py-2 pl-10 pr-3 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 bg-white" />
        </div>
        <button class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
            Filter
        </button>
        <button class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export
        </button>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- ── Table ── --}}
    <div class="card overflow-hidden">
        @if ($employees->count() > 0)
            <table id="wa-table" class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Code</th>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Position</th>
                        <th class="px-4 py-3">Department</th>
                        <th class="px-4 py-3">Supervisor</th>
                        <th class="px-4 py-3">Work Assignment</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($employees as $employee)
                        @php
                            $waLabels = [1 => 'Full-time', 2 => 'Part-time', 3 => 'Contractual', 4 => 'Intern'];
                            $waCode   = (int) ($employee->employment_type ?? 0);
                            $waLabel  = $waLabels[$waCode] ?? 'N/A';
                            $waColors = [1 => 'badge-green', 2 => 'badge-amber', 3 => 'badge-indigo', 4 => 'badge-purple'];
                            $waBadge  = $waColors[$waCode] ?? 'badge-gray';
                        @endphp
                        <tr class="wa-row hover:bg-slate-50 transition">
                            <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $employee->employee_code }}</td>

                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-semibold">
                                        {{ collect(explode(' ', $employee->full_name))->map(fn($n) => $n[0] ?? '')->join('') }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-900 wa-name">{{ $employee->full_name }}</p>
                                        <p class="text-xs text-slate-500">{{ $employee->email }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-3 text-slate-600">{{ $employee->position->title ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $employee->department->name ?? 'N/A' }}</td>

                            {{-- Supervisor --}}
                            <td class="px-4 py-3">
                                @if ($employee->manager)
                                    <div class="flex items-center gap-2">
                                        <div class="h-6 w-6 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-[10px] font-semibold">
                                            {{ collect(explode(' ', $employee->manager->full_name))->map(fn($n) => $n[0] ?? '')->join('') }}
                                        </div>
                                        <span class="text-slate-700 text-sm">{{ $employee->manager->full_name }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-400 italic text-xs">No supervisor</span>
                                @endif
                            </td>

                            {{-- Work Assignment --}}
                            <td class="px-4 py-3">
                                <span class="badge {{ $waBadge }}">{{ $waLabel }}</span>
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-3 text-sm">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('employees.show', $employee) }}"
                                       class="text-slate-600 hover:text-slate-900 transition" title="View">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                    {{-- Per-row assign button --}}
                                    <button type="button" title="Assign Work"
                                            class="text-blue-500 hover:text-blue-700 transition"
                                            onclick="openModal(
                                                {{ $employee->id }},
                                                '{{ addslashes($employee->full_name) }}',
                                                {{ $employee->manager_id ?? 'null' }},
                                                {{ $employee->employment_type ?? 'null' }}
                                            )">
                                        <i class="ti ti-briefcase text-lg"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="bg-white px-6 py-4 border-t border-slate-200 uppercase">
                {{ $employees->links() }}
            </div>
        @else
            <div class="px-6 py-12 text-center text-sm text-slate-500">No employees found.</div>
        @endif
    </div>

    {{-- ── Work Assignment Modal ── --}}
    <div id="waModal"
         class="hidden fixed inset-0 z-30 items-center justify-center bg-black/40 p-4"
         style="padding-left: var(--sidebar-width, 0);">

        <div class="w-full max-w-md rounded-xl bg-white shadow-xl">

            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Work Assignment</h3>
                    <p id="waModalSubtitle" class="text-xs text-slate-500 mt-0.5">Assign supervisor and work type</p>
                </div>
                <button type="button" onclick="closeModal()" class="text-slate-400 hover:text-slate-600 transition">
                    <i class="ti ti-x text-xl"></i>
                </button>
            </div>

            {{-- Form --}}
            <form id="waForm" method="POST" action="" class="p-6 space-y-5">
                @csrf
                @method('PATCH')

                {{-- Employee search (header button mode only) --}}
                <div id="waEmployeeSearchWrap">
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">
                        Employee <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" id="waEmpSearch" autocomplete="off"
                               placeholder="Search by name or code…"
                               class="w-full rounded-lg border border-slate-300 py-2 pr-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-700"
                               style="padding-left:2.25rem">
                        <input type="hidden" id="waEmpId" name="employee_id">
                        <div id="waEmpSuggestions"
                             class="hidden absolute left-0 right-0 top-full z-20 mt-1 max-h-48 overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg">
                        @foreach ($employees as $emp)
                            <button type="button" class="wa-suggestion w-full px-4 py-2 text-left text-sm hover:bg-slate-50 text-slate-700 transition"
                                    data-id="{{ $emp->id }}"
                                    data-name="{{ $emp->full_name }}"
                                    data-search="{{ strtolower($emp->full_name . ' ' . $emp->employee_code) }}"
                                    data-manager="{{ $emp->manager_id ?? '' }}"
                                    data-type="{{ $emp->employment_type ?? '' }}">
                                <span class="font-medium">{{ $emp->full_name }}</span>
                                <span class="ml-2 font-mono text-xs text-slate-400">{{ $emp->employee_code }}</span>
                            </button>
                        @endforeach
                        <div id="waNoSuggestions" class="hidden px-4 py-3 text-sm text-slate-500 text-center">No employee found.</div>
                        </div>{{-- #waEmpSuggestions --}}
                    </div>{{-- .relative --}}
                </div>{{-- #waEmployeeSearchWrap --}}

                {{-- Supervisor --}}
                <div>
                    <label for="waSupervisor" class="mb-1.5 block text-sm font-medium text-slate-700">Supervisor</label>
                    <select id="waSupervisor" name="manager_id"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-700">
                        <option value="">— No Supervisor —</option>
                        @foreach ($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->employee_code }})</option>
                        @endforeach
                    </select>
                </div>

                {{-- Work Assignment Type --}}
                <div>
                    <label for="waType" class="mb-1.5 block text-sm font-medium text-slate-700">
                        Work Assignment <span class="text-red-500">*</span>
                    </label>
                    <select id="waType" name="employment_type" required
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-700">
                        <option value="">— Select type —</option>
                        <option value="1">Full-time</option>
                        <option value="2">Part-time</option>
                        <option value="3">Contractual</option>
                        <option value="4">Intern</option>
                    </select>
                </div>

                {{-- Footer --}}
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeModal()"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                        Save Assignment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const saveBaseUrl = '{{ url('/employees') }}';

        /* ── open from header button (no pre-filled employee) ── */
        document.getElementById('openWaModal').addEventListener('click', function () {
            resetModal();
            document.getElementById('waEmployeeSearchWrap').classList.remove('hidden');
            document.getElementById('waModalSubtitle').textContent = 'Search an employee, then assign supervisor and work type';
            showModal();
        });

        /* ── open from per-row briefcase button ── */
        function openModal(id, name, managerId, empType) {
            resetModal();
            // Hide employee search — we already know the employee
            document.getElementById('waEmployeeSearchWrap').classList.add('hidden');
            document.getElementById('waModalSubtitle').textContent = name;
            document.getElementById('waEmpId').value = id;
            document.getElementById('waForm').action = saveBaseUrl + '/' + id + '/work-assignment';

            if (managerId) {
                document.getElementById('waSupervisor').value = managerId;
            }
            if (empType) {
                document.getElementById('waType').value = empType;
            }
            showModal();
        }

        function showModal() {
            const m = document.getElementById('waModal');
            m.classList.remove('hidden');
            m.classList.add('flex');
        }

        function closeModal() {
            const m = document.getElementById('waModal');
            m.classList.add('hidden');
            m.classList.remove('flex');
        }

        function resetModal() {
            document.getElementById('waEmpSearch').value  = '';
            document.getElementById('waEmpId').value      = '';
            document.getElementById('waSupervisor').value = '';
            document.getElementById('waType').value       = '';
            document.getElementById('waForm').action      = '';
            document.getElementById('waEmpSuggestions').classList.add('hidden');
        }

        /* ── Employee search autocomplete ── */
        const searchInput  = document.getElementById('waEmpSearch');
        const suggestBox   = document.getElementById('waEmpSuggestions');
        const noSuggest    = document.getElementById('waNoSuggestions');
        const hiddenId     = document.getElementById('waEmpId');
        const items        = document.querySelectorAll('.wa-suggestion');

        searchInput.addEventListener('input', function () {
            const term = this.value.toLowerCase().trim();
            hiddenId.value = '';
            document.getElementById('waForm').action = '';

            if (!term) { suggestBox.classList.add('hidden'); return; }

            suggestBox.classList.remove('hidden');
            let found = false;
            items.forEach(item => {
                const match = item.dataset.search.includes(term);
                item.classList.toggle('hidden', !match);
                if (match) found = true;
            });
            noSuggest.classList.toggle('hidden', found);
        });

        items.forEach(item => {
            item.addEventListener('click', function () {
                searchInput.value  = this.dataset.name;
                hiddenId.value     = this.dataset.id;
                document.getElementById('waForm').action = saveBaseUrl + '/' + this.dataset.id + '/work-assignment';

                // Pre-fill selects from row data
                if (this.dataset.manager) document.getElementById('waSupervisor').value = this.dataset.manager;
                if (this.dataset.type)    document.getElementById('waType').value       = this.dataset.type;

                suggestBox.classList.add('hidden');
            });
        });

        // Close suggestions on outside click
        document.addEventListener('click', function (e) {
            if (!searchInput.contains(e.target) && !suggestBox.contains(e.target)) {
                suggestBox.classList.add('hidden');
            }
        });

        /* ── Client-side table search ── */
        document.getElementById('wa-search').addEventListener('input', function () {
            const term = this.value.toLowerCase();
            document.querySelectorAll('#wa-table .wa-row').forEach(row => {
                const name = row.querySelector('.wa-name')?.textContent.toLowerCase() ?? '';
                const code = row.querySelector('td:first-child')?.textContent.toLowerCase() ?? '';
                row.style.display = (name.includes(term) || code.includes(term)) ? '' : 'none';
            });
        });
    </script>

</x-app-layout>
