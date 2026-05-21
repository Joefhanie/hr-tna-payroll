<x-app-layout>
    <x-slot:title>Manage Benefit Plan</x-slot:title>
    <x-slot:header>Manage Plan</x-slot:header>

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 p-4 text-sm text-green-700">
            <i class="ti ti-check-circle mr-1"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-700">
            <i class="ti ti-alert-circle mr-1"></i> {{ session('error') }}
        </div>
    @endif

    {{-- Back to Plans & Title --}}
    <div class="mb-6">
        <a href="{{ route('benefits') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-[#1a56db] transition">
            <i class="ti ti-arrow-left text-base"></i> Back to Plans
        </a>
        <div class="mt-2 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-[#e0f2fe] text-[#0369a1]">
                    <i class="ti ti-{{ $plan->icon }} text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-[#06112e]">{{ $plan->name }}</h1>
                    <p class="text-sm text-slate-500">{{ $plan->benefit_type }} Plan &bull; {{ $plan->provider ?? 'No provider' }}</p>
                </div>
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $plan->is_active ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-800' }}">
                {{ $plan->is_active ? 'Active' : 'Inactive' }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        
        {{-- LEFT COLUMN: Plan Details & Edit Form --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-[0_1px_2px_rgba(0,0,0,0.03)]">
                <h3 class="text-sm font-bold text-[#06112e] uppercase tracking-wide mb-4">Edit Plan Details</h3>
                
                <form method="POST" action="{{ route('benefits.update', $plan->id) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Plan Name</label>
                        <input type="text" name="name" value="{{ old('name', $plan->name) }}" required
                            @disabled(!auth()->user()->hasPermission('benefits.edit'))
                            class="w-full rounded-[0.5rem] border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#1a56db]/30 disabled:bg-slate-50 disabled:text-slate-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Category / Type</label>
                        <select name="benefit_type" required
                            @disabled(!auth()->user()->hasPermission('benefits.edit'))
                            class="w-full rounded-[0.5rem] border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#1a56db]/30 disabled:bg-slate-50 disabled:text-slate-500">
                            <option value="Health" {{ old('benefit_type', $plan->benefit_type) === 'Health' ? 'selected' : '' }}>Health</option>
                            <option value="Insurance" {{ old('benefit_type', $plan->benefit_type) === 'Insurance' ? 'selected' : '' }}>Insurance</option>
                            <option value="Government" {{ old('benefit_type', $plan->benefit_type) === 'Government' ? 'selected' : '' }}>Government</option>
                            <option value="Allowance" {{ old('benefit_type', $plan->benefit_type) === 'Allowance' ? 'selected' : '' }}>Allowance</option>
                            <option value="Other" {{ old('benefit_type', $plan->benefit_type) === 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Provider</label>
                        <input type="text" name="provider" value="{{ old('provider', $plan->provider) }}"
                            @disabled(!auth()->user()->hasPermission('benefits.edit'))
                            class="w-full rounded-[0.5rem] border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#1a56db]/30 disabled:bg-slate-50 disabled:text-slate-500">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Employee Cost (PHP)</label>
                            <input type="number" step="0.01" name="employee_cost" value="{{ old('employee_cost', $plan->employee_cost) }}"
                                @disabled(!auth()->user()->hasPermission('benefits.edit'))
                                class="w-full rounded-[0.5rem] border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#1a56db]/30 disabled:bg-slate-50 disabled:text-slate-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Employer Cost (PHP)</label>
                            <input type="number" step="0.01" name="employer_cost" value="{{ old('employer_cost', $plan->employer_cost) }}"
                                @disabled(!auth()->user()->hasPermission('benefits.edit'))
                                class="w-full rounded-[0.5rem] border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#1a56db]/30 disabled:bg-slate-50 disabled:text-slate-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Coverage Details</label>
                        <textarea name="coverage_details" rows="3"
                            @disabled(!auth()->user()->hasPermission('benefits.edit'))
                            class="w-full rounded-[0.5rem] border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 resize-none focus:outline-none focus:ring-2 focus:ring-[#1a56db]/30 disabled:bg-slate-50 disabled:text-slate-500">{{ old('coverage_details', $plan->coverage_details) }}</textarea>
                    </div>

                    <div class="flex items-center gap-2 py-1">
                        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $plan->is_active) ? 'checked' : '' }}
                            @disabled(!auth()->user()->hasPermission('benefits.edit'))
                            class="h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]/30 disabled:opacity-50">
                        <label for="is_active" class="text-xs font-semibold text-slate-700">Active and available for enrollment</label>
                    </div>

                    <div class="pt-2 flex justify-between gap-2">
                        @if(auth()->user()->hasPermission('benefits.edit'))
                        <button type="submit" class="w-full rounded-[0.5rem] bg-[#1a56db] py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-[#1e40af]">
                            Save Changes
                        </button>
                        @endif
                    </div>
                </form>

                @if(auth()->user()->hasPermission('benefits.delete'))
                <div class="mt-4 border-t border-slate-100 pt-4">
                    <form method="POST" action="{{ route('benefits.destroy', $plan->id) }}" data-confirm="Are you sure you want to delete <strong class='font-bold text-slate-900'>{{ $plan->name }}</strong>? All enrollment data will be deleted." data-confirm-title="Delete Benefit Plan">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full rounded-[0.5rem] border border-red-200 bg-red-50 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100">
                            Delete Plan
                        </button>
                    </form>
                </div>
                @endif
            </div>
        </div>

        {{-- RIGHT COLUMN: Enrollment Management --}}
        <div class="lg:col-span-2 space-y-6">
            
            {{-- Enrolled List --}}
            <div class="rounded-xl border border-slate-200 bg-white shadow-[0_1px_2px_rgba(0,0,0,0.03)] overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/50 px-5 py-4">
                    <h3 class="text-sm font-bold text-[#06112e] uppercase tracking-wide">
                        Enrolled Employees ({{ $enrolled->count() }})
                    </h3>
                    @if(auth()->user()->hasPermission('benefits.edit') && $plan->is_active)
                        <button type="button" id="openEnrollModal" class="inline-flex items-center gap-1.5 rounded-[0.5rem] bg-[#1a56db] px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-[#1e40af]">
                            <i class="ti ti-plus text-sm"></i>
                            Enroll Employee
                        </button>
                    @endif
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-left text-[0.75rem] font-bold text-slate-500 border-b border-slate-100">
                            <tr>
                                <th class="px-5 py-3 font-bold">Employee</th>
                                <th class="px-5 py-3 font-bold">Department</th>
                                <th class="px-5 py-3 font-bold">Position</th>
                                <th class="px-5 py-3 font-bold text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($enrolled as $emp)
                                <tr class="transition hover:bg-slate-50">
                                    <td class="px-5 py-3.5 flex items-center gap-3">
                                        @php
                                            $initials = strtoupper(substr($emp->first_name, 0, 1) . substr($emp->last_name, 0, 1));
                                        @endphp
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-600 font-bold text-xs">
                                            {{ $initials }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-[#06112e]">{{ $emp->full_name }}</p>
                                            <p class="text-[0.7rem] text-slate-400 mt-0.5">{{ $emp->employee_code }}</p>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 text-slate-600">
                                        {{ $emp->department?->name ?? 'No department' }}
                                    </td>
                                    <td class="px-5 py-3.5 text-slate-600">
                                        {{ $emp->position?->title ?? 'No position' }}
                                    </td>
                                    <td class="px-5 py-3.5 text-right">
                                        @if(auth()->user()->hasPermission('benefits.edit'))
                                            <form method="POST" action="{{ route('benefits.disenroll', [$plan->id, $emp->id]) }}" class="inline" data-confirm="Are you sure you want to disenroll <strong class='font-bold text-slate-900'>{{ $emp->full_name }}</strong> from this plan?" data-confirm-title="Confirm Disenrollment">
                                                @csrf
                                                <button type="submit" class="rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100">
                                                    Disenroll
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-slate-300">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-12 text-center text-slate-400">
                                        <i class="ti ti-users text-3xl block mb-2"></i>
                                        No employees enrolled in this plan yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    {{-- =========================================================
         ENROLL EMPLOYEE MODAL
    ========================================================= --}}
    @if(auth()->user()->hasPermission('benefits.edit') && $plan->is_active)
    <div id="enrollModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/40 p-4 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Enroll Employee</h3>
                    <p class="mt-0.5 text-xs text-slate-500">Enroll an employee in {{ $plan->name }}</p>
                </div>
                <button type="button" id="closeEnrollModal" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50">
                    <i class="ti ti-x text-sm"></i>
                </button>
            </div>

            @if($notEnrolled->isNotEmpty())
                <form method="POST" action="{{ route('benefits.enroll', $plan->id) }}" class="px-6 py-5 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Select Employee</label>
                        <select name="employee_id" required
                            class="w-full rounded-[0.5rem] border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#1a56db]/30">
                            <option value="">— Select —</option>
                            @foreach($notEnrolled as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Coverage Start Date</label>
                        <input type="date" name="coverage_start" value="{{ now()->toDateString() }}" required
                            class="w-full rounded-[0.5rem] border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#1a56db]/30">
                        <input type="hidden" name="enrollment_date" value="{{ now()->toDateString() }}">
                    </div>

                    <div class="flex justify-end gap-3 pt-1">
                        <button type="button" id="cancelEnroll" class="rounded-[0.5rem] border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-[0.5rem] bg-[#1a56db] px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1e40af]">
                            Enroll Employee
                        </button>
                    </div>
                </form>
            @else
                <div class="px-6 py-8 text-center">
                    <p class="text-sm text-slate-500 italic"><i class="ti ti-info-circle text-lg mr-1 align-middle"></i> All active employees are already enrolled in this plan.</p>
                    <div class="mt-4 flex justify-center">
                        <button type="button" id="cancelEnrollEmpty" class="rounded-[0.5rem] border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                            Close
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
    @endif

    <x-slot:scripts>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const openBtn = document.getElementById('openEnrollModal');
                const closeBtn = document.getElementById('closeEnrollModal');
                const cancelBtn = document.getElementById('cancelEnroll');
                const cancelBtnEmpty = document.getElementById('cancelEnrollEmpty');
                const modal = document.getElementById('enrollModal');

                function openM() {
                    modal?.classList.remove('hidden');
                    modal?.classList.add('flex');
                }

                function closeM() {
                    modal?.classList.remove('flex');
                    modal?.classList.add('hidden');
                }

                openBtn?.addEventListener('click', openM);
                closeBtn?.addEventListener('click', closeM);
                cancelBtn?.addEventListener('click', closeM);
                cancelBtnEmpty?.addEventListener('click', closeM);

                modal?.addEventListener('click', function (e) {
                    if (e.target === modal) closeM();
                });

                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') closeM();
                });
            });
        </script>
    </x-slot:scripts>
</x-app-layout>
