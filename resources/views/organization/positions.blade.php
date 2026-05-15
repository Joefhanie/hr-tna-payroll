<x-app-layout>
    <x-slot:title>Positions</x-slot:title>
    <x-slot:header>Positions</x-slot:header>

    <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-[1.65rem] font-bold text-[#06112e]">Positions</h1>
            <p class="mt-1 text-sm text-slate-500">Manage position records and department assignments.</p>
        </div>
        <button type="button" onclick="document.getElementById('positionFormModal').classList.replace('hidden', 'flex')" class="inline-flex items-center gap-2 rounded-[0.5rem] bg-[#1a56db] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1e40af]">
            <i class="ti ti-plus text-base"></i>
            Add Position
        </button>
    </div>

    <div class="mb-4 h-px w-full bg-slate-200"></div>

    @if (session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <section class="card p-6">
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Title</th>
                        <th class="px-4 py-3">Department</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($positions as $position)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $position->title }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $position->department->name ?? 'Unassigned' }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('organization.positions.show', $position) }}" class="text-slate-600 hover:text-slate-900 transition">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                    <a href="{{ route('organization.positions.edit', $position) }}" class="text-slate-600 hover:text-slate-900 transition">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('organization.positions.destroy', $position) }}" onsubmit="return confirm('Delete this position?');" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 transition">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-sm text-slate-500">No positions yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- Add Position Modal -->
    <div id="positionFormModal" class="{{ $errors->any() ? 'flex' : 'hidden' }} fixed inset-0 z-30 items-center justify-center bg-black/40 p-4 transition-opacity" style="padding-left: var(--sidebar-width);">
        <div class="w-full max-w-md rounded-2xl border border-slate-300 bg-white shadow-[0_25px_50px_-12px_rgba(0,0,0,0.45)] ring-4 ring-black/5">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h3 class="text-lg font-bold text-[#06112e]">Add Position</h3>
                <button type="button" onclick="document.getElementById('positionFormModal').classList.replace('flex', 'hidden')" class="text-slate-400 transition hover:text-slate-600">
                    <i class="ti ti-x text-xl"></i>
                </button>
            </div>
            
            <form method="POST" action="{{ route('organization.positions.store') }}" class="p-6">
                @csrf
                
                @if ($errors->any())
                    <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-3">
                        <ul class="space-y-1 text-[0.8rem] text-red-700">
                            @foreach ($errors->all() as $error)
                                <li>• {{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                <div class="grid gap-5">
                    <div>
                        <label for="position_title" class="mb-1.5 block text-[0.8rem] font-bold text-[#06112e]">Position Title</label>
                        <input id="position_title" name="title" type="text" value="{{ old('title') }}" class="w-full rounded-[0.5rem] border border-slate-300 bg-white px-3 py-2 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]" placeholder="e.g. Payroll Officer">
                    </div>
                    <div>
                        <label for="position_level" class="mb-1.5 block text-[0.8rem] font-bold text-[#06112e]">Level</label>
                        <input id="position_level" name="level" type="text" value="{{ old('level') }}" class="w-full rounded-[0.5rem] border border-slate-300 bg-white px-3 py-2 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]" placeholder="e.g. Senior">
                    </div>
                    <div>
                        <label for="position_department_id" class="mb-1.5 block text-[0.8rem] font-bold text-[#06112e]">Department</label>
                        <select id="position_department_id" name="department_id" class="w-full rounded-[0.5rem] border border-slate-300 bg-white px-3 py-2 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]">
                            <option value="">None (Unassigned)</option>
                            @foreach ($topLevelDepartments as $department)
                                <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="min_salary" class="mb-1.5 block text-[0.8rem] font-bold text-[#06112e]">Min Salary</label>
                            <input id="min_salary" name="min_salary" type="text" inputmode="decimal" maxlength="10" oninput="this.value = this.value.replace(/[^0-9.]/g, '')" value="{{ old('min_salary') }}" class="w-full rounded-[0.5rem] border border-slate-300 bg-white px-3 py-2 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]">
                        </div>
                        <div>
                            <label for="max_salary" class="mb-1.5 block text-[0.8rem] font-bold text-[#06112e]">Max Salary</label>
                            <input id="max_salary" name="max_salary" type="text" inputmode="decimal" maxlength="10" oninput="this.value = this.value.replace(/[^0-9.]/g, '')" value="{{ old('max_salary') }}" class="w-full rounded-[0.5rem] border border-slate-300 bg-white px-3 py-2 text-sm transition focus:border-[#1a56db] focus:outline-none focus:ring-1 focus:ring-[#1a56db]">
                        </div>
                    </div>
                </div>
                
                <div class="mt-8 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('positionFormModal').classList.replace('flex', 'hidden')" class="rounded-[0.5rem] border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-[#06112e] shadow-sm transition hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-[0.5rem] bg-[#1a56db] px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-[#1e40af]">Save Position</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
