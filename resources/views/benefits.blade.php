<x-app-layout>
    <x-slot:title>Benefits</x-slot:title>
    <x-slot:header>Benefits</x-slot:header>

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

    <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-[1.65rem] font-bold text-[#06112e]">Benefits</h1>
            <p class="mt-1 text-sm text-slate-500">Health, insurance, and allowance plans for your team.</p>
        </div>
        @if(auth()->user()->hasPermission('benefits.create'))
        <button type="button" id="openAddPlanModal" class="inline-flex items-center gap-2 rounded-[0.5rem] bg-[#1a56db] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1e40af]">
            <i class="ti ti-plus text-base"></i>
            Add Plan
        </button>
        @endif
    </div>

    <div class="mb-4 h-px w-full bg-slate-200"></div>

    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
        @forelse ($plans as $plan)
            <div class="flex flex-col justify-between rounded-[0.8rem] border border-slate-200 bg-white p-4 shadow-[0_1px_2px_rgba(0,0,0,0.03)] transition hover:border-slate-300 hover:shadow-md">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[0.6rem] bg-[#e0f2fe] text-[#0369a1]">
                            <i class="ti ti-{{ $plan->icon }} text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-[#06112e]">{{ $plan->name }}</h3>
                            <p class="mt-0.5 text-[0.75rem] text-slate-500">{{ $plan->benefit_type }}</p>
                        </div>
                    </div>
                    <span class="shrink-0 rounded-full border border-slate-100 bg-slate-50 px-2.5 py-1 text-[0.7rem] font-semibold text-[#06112e]">
                        @if ($plan->benefit_type === 'Government' || !$plan->employee_cost || $plan->employee_cost == 0)
                            Mandatory
                        @else
                            ₱{{ number_format($plan->employee_cost) }}/mo
                        @endif
                    </span>
                </div>
                
                <div class="mt-4 flex items-center justify-between">
                    <p class="text-[0.8rem] text-slate-500">{{ $plan->enrolled_count }} enrolled</p>
                    <a href="{{ route('benefits.show', $plan->id) }}" class="rounded-[0.5rem] px-3 py-1.5 text-[0.8rem] font-semibold text-[#06112e] transition hover:bg-[#dbeafe] border border-transparent hover:border-slate-200">
                        Manage
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-2 rounded-[0.8rem] border border-dashed border-slate-200 bg-white p-8 text-center text-slate-400">
                <i class="ti ti-gift text-3xl block mb-2"></i>
                No benefit plans found. Click "Add Plan" to create one.
            </div>
        @endforelse
    </div>

    {{-- =========================================================
         ADD PLAN MODAL
    ========================================================= --}}
    @if(auth()->user()->hasPermission('benefits.create'))
    <div id="addPlanModal" class="fixed inset-0 z-50 hidden bg-slate-950/40 p-4 backdrop-blur-sm justify-center items-start sm:items-center overflow-y-auto">
        <div class="my-auto w-full max-w-lg rounded-2xl border border-slate-200 bg-white shadow-2xl max-h-[calc(100vh-2rem)] sm:max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Add Benefit Plan</h3>
                    <p class="mt-0.5 text-xs text-slate-500">Create a new health, insurance, or allowance plan.</p>
                </div>
                <button type="button" id="closeAddPlanModal" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50">
                    <i class="ti ti-x text-sm"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('benefits.store') }}" class="px-6 py-5 space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Plan Name</label>
                    <input type="text" name="name" required placeholder="e.g. Maxicare Gold"
                        class="w-full rounded-[0.5rem] border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#1a56db]/30">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Category / Type</label>
                        <select name="benefit_type" required
                            class="w-full rounded-[0.5rem] border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#1a56db]/30">
                            <option value="Health">Health</option>
                            <option value="Insurance">Insurance</option>
                            <option value="Government">Government</option>
                            <option value="Allowance">Allowance</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Provider</label>
                        <input type="text" name="provider" placeholder="e.g. Maxicare"
                            class="w-full rounded-[0.5rem] border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#1a56db]/30">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Monthly Employee Cost (PHP)</label>
                        <input type="number" step="0.01" name="employee_cost" placeholder="0.00"
                            class="w-full rounded-[0.5rem] border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#1a56db]/30">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Monthly Employer Cost (PHP)</label>
                        <input type="number" step="0.01" name="employer_cost" placeholder="0.00"
                            class="w-full rounded-[0.5rem] border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#1a56db]/30">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Coverage Details</label>
                    <textarea name="coverage_details" rows="3" placeholder="Describe the plan benefits, coverage, details…"
                        class="w-full rounded-[0.5rem] border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 resize-none focus:outline-none focus:ring-2 focus:ring-[#1a56db]/30"></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-1">
                    <button type="button" id="cancelAddPlan" class="rounded-[0.5rem] border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit" class="rounded-[0.5rem] bg-[#1a56db] px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1e40af]">
                        Save Plan
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <x-slot:scripts>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const openBtn = document.getElementById('openAddPlanModal');
                const closeBtn = document.getElementById('closeAddPlanModal');
                const cancelBtn = document.getElementById('cancelAddPlan');
                const modal = document.getElementById('addPlanModal');

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
