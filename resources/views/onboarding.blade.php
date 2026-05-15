<x-app-layout>
    <x-slot:title>Onboarding</x-slot:title>
    <x-slot:header>Onboarding</x-slot:header>

    @php
        $employees = [
            ['name' => 'Jules Tan', 'type' => 'Engineering Onboarding', 'status' => 'In Progress', 'progress' => 65],
            ['name' => 'Patricia Ong', 'type' => 'General Onboarding', 'status' => 'In Progress', 'progress' => 20],
            ['name' => 'Rico Bautista', 'type' => 'Intern Onboarding', 'status' => 'Completed', 'progress' => 100],
            ['name' => 'Hannah Lee', 'type' => 'General Onboarding', 'status' => 'Not Started', 'progress' => 0],
        ];

        $tasks = [
            ['title' => 'Sign employment contract', 'category' => 'Documents', 'completed' => true],
            ['title' => 'Submit government IDs', 'category' => 'Documents', 'completed' => true],
            ['title' => 'Laptop & accessories setup', 'category' => 'IT Setup', 'completed' => true],
            ['title' => 'Email & system access', 'category' => 'IT Setup', 'completed' => false],
            ['title' => 'Orientation with HR', 'category' => 'HR', 'completed' => false],
            ['title' => 'Team introduction', 'category' => 'Training', 'completed' => false],
        ];
    @endphp

    <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-[1.65rem] font-bold text-[#06112e]">Onboarding</h1>
            <p class="mt-1 text-sm text-slate-500">Track new hires and onboarding tasks.</p>
        </div>
        <button type="button" class="inline-flex items-center gap-2 rounded-[0.5rem] bg-[#1a56db] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1e40af]">
            <i class="ti ti-plus text-base"></i>
            Start Onboarding
        </button>
    </div>

    <div class="mb-4 h-px w-full bg-slate-200"></div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <!-- Left Column: Employees List -->
        <div class="flex flex-col gap-3 lg:col-span-1">
            @foreach ($employees as $emp)
                <div class="cursor-pointer rounded-[0.8rem] border border-slate-200 bg-white p-4 shadow-[0_1px_2px_rgba(0,0,0,0.03)] transition hover:border-slate-300 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <h3 class="font-bold text-[#06112e]">{{ $emp['name'] }}</h3>
                        <span class="rounded-full px-2.5 py-0.5 text-[0.7rem] font-bold {{
                            $emp['status'] === 'Completed' ? 'bg-[#dcfce7] text-[#166534]' :
                            ($emp['status'] === 'In Progress' ? 'bg-[#e0f2fe] text-[#0369a1]' : 'bg-slate-100 text-slate-600')
                        }}">{{ $emp['status'] }}</span>
                    </div>
                    <p class="mt-1 text-[0.8rem] text-slate-500">{{ $emp['type'] }}</p>
                    
                    <div class="mt-4">
                        <div class="h-[0.35rem] w-full overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-[#1a56db]" style="width: {{ $emp['progress'] }}%"></div>
                        </div>
                        <p class="mt-2 text-[0.75rem] text-slate-500">{{ $emp['progress'] }}% complete</p>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Right Column: Task Details -->
        <div class="lg:col-span-2">
            <div class="rounded-[0.8rem] border border-slate-200 bg-white p-5 shadow-[0_1px_2px_rgba(0,0,0,0.03)]">
                <h2 class="text-[1.05rem] font-bold text-[#06112e]">Jules Tan — Engineering Onboarding</h2>
                
                <div class="mt-5 flex flex-col gap-2">
                    @foreach ($tasks as $task)
                        @if ($task['completed'])
                            <div class="flex items-center justify-between rounded-[0.7rem] border border-slate-100 p-3 transition hover:bg-slate-50">
                                <div class="flex items-center gap-4">
                                    <i class="ti ti-circle-check text-2xl text-[#10b981]"></i>
                                    <div>
                                        <p class="text-[0.85rem] font-medium text-slate-400 line-through">{{ $task['title'] }}</p>
                                        <p class="text-[0.75rem] text-slate-400">{{ $task['category'] }}</p>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="flex items-center justify-between rounded-[0.7rem] border border-slate-200 bg-white p-3 shadow-sm transition hover:shadow-md">
                                <div class="flex items-center gap-4">
                                    <i class="ti ti-circle text-2xl text-slate-400"></i>
                                    <div>
                                        <p class="text-[0.85rem] font-bold text-[#06112e]">{{ $task['title'] }}</p>
                                        <p class="text-[0.75rem] text-slate-500">{{ $task['category'] }}</p>
                                    </div>
                                </div>
                                <button type="button" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[0.75rem] font-bold text-[#06112e] shadow-sm transition hover:bg-slate-50">
                                    Mark done
                                </button>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
