<x-app-layout>
    <x-slot:title>Benefits</x-slot:title>
    <x-slot:header>Benefits</x-slot:header>

    @php
        $benefits = [
            ['title' => 'HMO – Maxicare Platinum', 'category' => 'Health', 'price' => '₱4,200/mo', 'enrolled' => 138, 'icon' => 'heartbeat'],
            ['title' => 'Group Life Insurance', 'category' => 'Insurance', 'price' => '₱650/mo', 'enrolled' => 144, 'icon' => 'shield'],
            ['title' => 'SSS / PhilHealth / Pag-IBIG', 'category' => 'Government', 'price' => 'Mandatory', 'enrolled' => 144, 'icon' => 'building-bank'],
            ['title' => 'Dental Plan', 'category' => 'Health', 'price' => '₱500/mo', 'enrolled' => 92, 'icon' => 'heart-plus'],
            ['title' => 'Wellness Allowance', 'category' => 'Allowance', 'price' => '₱1,000/mo', 'enrolled' => 144, 'icon' => 'sparkles'],
        ];
    @endphp

    <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-[1.65rem] font-bold text-[#06112e]">Benefits</h1>
            <p class="mt-1 text-sm text-slate-500">Health, insurance, and allowance plans for your team.</p>
        </div>
        <button type="button" class="inline-flex items-center gap-2 rounded-[0.5rem] bg-[#1a56db] px-4 py-2.5 text-sm text-white shadow-sm transition hover:bg-[#1e40af]">
            <i class="ti ti-plus text-base"></i>
            Add Plan
        </button>
    </div>

    <div class="mb-4 h-px w-full bg-slate-200"></div>

    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
        @foreach ($benefits as $plan)
            <div class="flex flex-col justify-between rounded-[0.8rem] border border-slate-200 bg-white p-4 shadow-[0_1px_2px_rgba(0,0,0,0.03)] transition hover:border-slate-300 hover:shadow-md">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[0.6rem] bg-[#e0f2fe] text-[#0369a1]">
                            <i class="ti ti-{{ $plan['icon'] }} text-xl"></i>
                        </div>
                        <div>
                            <h3 class=" text-[#06112e]">{{ $plan['title'] }}</h3>
                            <p class="mt-0.5 text-[0.75rem] text-slate-500">{{ $plan['category'] }}</p>
                        </div>
                    </div>
                    <span class="shrink-0 rounded-full border border-slate-100 bg-slate-50 px-2.5 py-1 text-[0.7rem] text-[#06112e]">
                        {{ $plan['price'] }}
                    </span>
                </div>
                
                <div class="mt-4 flex items-center justify-between">
                    <p class="text-[0.8rem] text-slate-500">{{ $plan['enrolled'] }} enrolled</p>
                    <button type="button" class="rounded-[0.5rem] px-3 py-1.5 text-[0.8rem] font-semibold text-[#06112e] transition hover:bg-[#dbeafe]">
                        Manage
                    </button>
                </div>
            </div>
        @endforeach
    </div>
</x-app-layout>
