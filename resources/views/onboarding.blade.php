<x-app-layout :show-global-alerts="false">
    <x-slot:title>Onboarding</x-slot:title>
    <x-slot:header>Onboarding</x-slot:header>

    <div class="mb-4 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <h1 class="text-[1.65rem] font-bold text-[#06112e]">Onboarding</h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ $isEmployeeView ? 'Complete your onboarding requirements here.' : 'Track onboarding progress and assign action items.' }}
            </p>
        </div>
        @if (!$isEmployeeView)
            <form method="GET" action="{{ route('onboarding') }}" class="w-full xl:w-auto" id="onboarding-filters-form">
                <input type="hidden" name="employee" value="{{ $selectedEmployee['id'] ?? '' }}">
                <div class="flex flex-col gap-2 lg:flex-row lg:flex-wrap lg:items-center lg:justify-end">
                    <div class="relative w-full lg:w-[12rem] xl:w-[13rem]">
                        <i class="ti ti-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-base text-slate-400"></i>
                        <input
                            type="search"
                            name="q"
                            value="{{ $filters['q'] ?? '' }}"
                            placeholder="Search name or employee code"
                            class="w-full rounded-lg border border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-700 outline-none transition focus:border-[#1a56db] focus:ring-2 focus:ring-blue-100"
                            data-auto-submit-search
                        >
                    </div>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3 lg:w-auto">
                        <select name="employment_type" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-[#1a56db] focus:ring-2 focus:ring-blue-100 lg:w-[11rem] xl:w-[12rem]" data-auto-submit-filter>
                            <option value="">All types</option>
                            @foreach (($filterOptions['employment_types'] ?? []) as $option)
                                <option value="{{ $option['value'] }}" @selected(($filters['employment_type'] ?? '') === $option['value'])>{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                        <select name="employee_status" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-[#1a56db] focus:ring-2 focus:ring-blue-100 lg:w-[12rem] xl:w-[13rem]" data-auto-submit-filter>
                            <option value="">All employee statuses</option>
                            @foreach (($filterOptions['employee_statuses'] ?? []) as $option)
                                <option value="{{ $option['value'] }}" @selected(($filters['employee_status'] ?? '') === $option['value'])>{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                        <select name="onboarding_status" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-[#1a56db] focus:ring-2 focus:ring-blue-100 lg:w-[12rem] xl:w-[13rem]" data-auto-submit-filter>
                            <option value="">All onboarding statuses</option>
                            @foreach (($filterOptions['onboarding_statuses'] ?? []) as $option)
                                <option value="{{ $option['value'] }}" @selected(($filters['onboarding_status'] ?? '') === $option['value'])>{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>
        @endif
    </div>

    @php
        $selectedEmployeeName = $selectedEmployee['name'] ?? 'the selected employee';
        $statusBadge = fn (string $status) => match ($status) {
            'Completed' => 'bg-[#dcfce7] text-[#166534]',
            'In Progress' => 'bg-[#e0f2fe] text-[#0369a1]',
            default => 'bg-slate-100 text-slate-600',
        };

        $ownerBadge = fn (string $owner) => match ($owner) {
            'Employee' => 'bg-amber-50 text-amber-700',
            'HR' => 'bg-emerald-50 text-emerald-700',
            'Supervisor' => 'bg-sky-50 text-sky-700',
            default => 'bg-slate-100 text-slate-600',
        };

        $taskFormAction = old('task_id')
            ? route('onboarding.tasks.update', old('task_id'))
            : (($selectedEmployee && ($selectedEmployee['has_assignment'] ?? false))
                ? route('onboarding.tasks.store', $selectedEmployee['id'])
                : '#');
        $taskFormMode = old('task_id') ? 'edit' : 'create';
    @endphp

    @if ($isEmployeeView)
        <div class="rounded-[0.8rem] border border-slate-200 bg-white p-5 shadow-[0_1px_2px_rgba(0,0,0,0.03)]">
            @if (!$selectedEmployee)
                <div class="rounded-[0.7rem] border border-dashed border-slate-200 p-6 text-sm text-slate-500">
                    No employee profile is linked to your account yet.
                </div>
            @else
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-[1.05rem] font-bold text-[#06112e]">{{ $selectedEmployee['name'] }} - {{ $selectedEmployee['type'] }}</h2>
                        <p class="mt-1 text-sm text-slate-500">Hire date: {{ $selectedEmployee['hire_date'] }}</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $statusBadge($selectedEmployee['status']) }}">
                        {{ $selectedEmployee['status'] }}
                    </span>
                </div>

                @if (!$selectedEmployee['has_assignment'])
                    <div class="mt-5 rounded-[0.7rem] border border-dashed border-slate-200 p-6 text-sm text-slate-500">
                        HR has not assigned your onboarding tasks yet.
                    </div>
                @else
                    <div class="mt-4">
                        <div class="h-[0.35rem] w-full overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-[#1a56db]" style="width: {{ $selectedEmployee['progress'] }}%"></div>
                        </div>
                        <p class="mt-2 text-[0.75rem] text-slate-500">{{ $selectedEmployee['progress'] }}% complete</p>
                    </div>

                    <div class="mt-5 flex flex-col gap-3">
                        @forelse ($selectedEmployee['tasks'] as $task)
                            <details class="group rounded-[0.8rem] border {{ $task['completed'] ? 'border-emerald-100 bg-emerald-50/30' : 'border-slate-200 bg-white' }} shadow-sm" @if (!$task['completed']) open @endif>
                                <summary class="flex cursor-pointer list-none items-start justify-between gap-3 p-4">
                                    <div class="flex items-start gap-4">
                                        <i class="ti {{ $task['completed'] ? 'ti-circle-check text-[#10b981]' : 'ti-circle text-slate-400' }} mt-0.5 text-2xl"></i>
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="text-[0.9rem] {{ $task['completed'] ? 'font-medium text-slate-400 line-through' : 'font-bold text-[#06112e]' }}">{{ $task['title'] }}</p>
                                                <span class="rounded-full px-2.5 py-1 text-[0.65rem] font-semibold uppercase tracking-wide {{ $ownerBadge($task['assigned_role_label']) }}">
                                                    {{ $task['assigned_role_label'] }}
                                                </span>
                                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[0.65rem] font-semibold uppercase tracking-wide text-slate-600">
                                                    {{ $task['action_type_label'] }}
                                                </span>
                                            </div>
                                            <p class="mt-1 text-[0.75rem] text-slate-500">{{ $task['category'] }}</p>
                                            @if ($task['submitted_at'])
                                                <p class="mt-1 text-xs text-slate-400">Submitted {{ $task['submitted_at'] }}</p>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        @if ($task['completed'])
                                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-[0.75rem] font-semibold text-emerald-700">
                                                Completed
                                            </span>
                                        @else
                                            <span class="rounded-full bg-amber-50 px-3 py-1 text-[0.75rem] font-semibold text-amber-700">
                                                Action needed
                                            </span>
                                        @endif
                                        <i class="ti ti-chevron-down text-lg text-slate-400 transition group-open:rotate-180"></i>
                                    </div>
                                </summary>

                                <div class="border-t border-slate-100 px-4 pb-4 pt-4">
                                    @if ($task['instructions'])
                                        <p class="text-sm text-slate-600">{{ $task['instructions'] }}</p>
                                    @endif
                                    @if ($task['document_type'])
                                        <p class="mt-2 text-xs font-medium uppercase tracking-wide text-slate-400">Document type: {{ $task['document_type'] }}</p>
                                    @endif
                                    @if ($task['company_contract_download_url'])
                                        <div class="mt-3 rounded-lg border border-blue-100 bg-blue-50 px-3 py-3 text-sm text-blue-900">
                                            <p class="font-medium">Latest contract file</p>
                                            <p class="mt-1 text-xs text-blue-700">{{ $task['company_contract_name'] }}</p>
                                            <a href="{{ $task['company_contract_download_url'] }}" class="mt-2 inline-flex items-center gap-2 text-sm font-semibold text-blue-700 hover:text-blue-900">
                                                <i class="ti ti-download text-base"></i>
                                                Download contract
                                            </a>
                                        </div>
                                    @endif
                                    @if ($task['submission_file_name'])
                                        <p class="mt-2 text-sm text-slate-600">Uploaded file: {{ $task['submission_file_name'] }}</p>
                                    @endif
                                    @if ($task['submission_notes'])
                                        <p class="mt-1 text-sm text-slate-500">Notes: {{ $task['submission_notes'] }}</p>
                                    @endif

                                    @if (!$task['completed'])
                                        <div class="mt-4">
                                            @if ($task['action_type'] === 'document_upload')
                                                <form method="POST" action="{{ route('onboarding.tasks.submit', $task['id']) }}" enctype="multipart/form-data" class="space-y-3">
                                                    @csrf
                                                      <div>
                                                          <label class="mb-1 block text-[0.75rem] font-semibold uppercase tracking-wide text-slate-500">Upload file</label>
                                                          <input type="file" name="document_file" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
                                                              class="w-full text-sm text-slate-600 border border-slate-300 rounded-lg cursor-pointer bg-white file:mr-3 file:rounded-md file:border-0 file:bg-blue-50 file:text-blue-700 file:px-3 file:py-2 file:text-xs file:font-medium hover:file:bg-blue-100 transition focus:outline-none focus:ring-2 focus:ring-[#1a56db]">
                                                      </div>
                                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                                        <div>
                                                            <label class="mb-1 block text-[0.75rem] font-semibold uppercase tracking-wide text-slate-500">Expiry date</label>
                                                            <input type="date" name="expiry_date" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                                        </div>
                                                        <div>
                                                            <label class="mb-1 block text-[0.75rem] font-semibold uppercase tracking-wide text-slate-500">Notes</label>
                                                            <input type="text" name="submission_notes" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Optional note for HR">
                                                        </div>
                                                    </div>
                                                    <button type="submit" class="rounded-[0.55rem] bg-[#1a56db] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#1e40af]">
                                                        Submit document
                                                    </button>
                                                </form>
                                            @elseif ($task['action_type'] === 'acknowledgement')
                                                <form method="POST" action="{{ route('onboarding.tasks.submit', $task['id']) }}" class="space-y-3">
                                                    @csrf
                                                    <label class="flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-700">
                                                        <input type="checkbox" name="acknowledged" value="1" class="mt-1 h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]">
                                                        <span>I confirm that I reviewed and accepted this onboarding requirement.</span>
                                                    </label>
                                                    <div>
                                                        <label class="mb-1 block text-[0.75rem] font-semibold uppercase tracking-wide text-slate-500">Notes</label>
                                                        <textarea name="submission_notes" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Optional comment"></textarea>
                                                    </div>
                                                    <button type="submit" class="rounded-[0.55rem] bg-[#1a56db] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#1e40af]">
                                                        Confirm and complete
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('onboarding.tasks.submit', $task['id']) }}" class="space-y-3">
                                                    @csrf
                                                    <div>
                                                        <label class="mb-1 block text-[0.75rem] font-semibold uppercase tracking-wide text-slate-500">Completion note</label>
                                                        <textarea name="submission_notes" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Optional note"></textarea>
                                                    </div>
                                                    <button type="submit" class="rounded-[0.55rem] bg-[#1a56db] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#1e40af]">
                                                        Mark as done
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </details>
                        @empty
                            <div class="rounded-[0.7rem] border border-dashed border-slate-200 p-6 text-sm text-slate-500">
                                No employee actions are assigned yet.
                            </div>
                        @endforelse
                    </div>
                @endif
            @endif
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3 lg:items-start">
            <div class="lg:col-span-1">
                <div class="flex h-[58rem] min-h-0 flex-col overflow-hidden rounded-[0.8rem] border border-slate-200 bg-white p-3 shadow-[0_1px_2px_rgba(0,0,0,0.03)] lg:h-[calc(100vh-13rem)]">
                    <div class="min-h-0 flex-1 overflow-y-scroll pr-1">
                        <div class="flex flex-col gap-3">
                        @forelse ($employees as $emp)
                            <a href="{{ route('onboarding', ['employee' => $emp['id']]) }}" class="rounded-[0.8rem] border {{ ($selectedEmployee['id'] ?? null) === $emp['id'] ? 'border-[#1a56db] bg-blue-50/40' : 'border-slate-200 bg-white' }} p-4 shadow-[0_1px_2px_rgba(0,0,0,0.03)] transition hover:border-slate-300 hover:shadow-md">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <h3 class="font-bold text-[#06112e]">{{ $emp['name'] }}</h3>
                                        <p class="mt-0.5 font-mono text-[0.72rem] uppercase tracking-wide text-slate-400">{{ $emp['employee_code'] ?: 'No Code' }}</p>
                                    </div>
                                    <span class="rounded-full px-2.5 py-0.5 text-[0.7rem] font-bold {{ $statusBadge($emp['status']) }}">{{ $emp['status'] }}</span>
                                </div>
                                <div class="mt-1 flex items-center gap-2">
                                    <p class="text-[0.8rem] text-slate-500">{{ $emp['type'] }}</p>
                                    @if ($emp['is_priority_hire'])
                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-wide text-amber-700">Priority</span>
                                    @endif
                                </div>
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[0.65rem] font-semibold text-slate-600">{{ $emp['employment_type_label'] }}</span>
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[0.65rem] font-semibold text-slate-600">{{ $emp['employee_status_label'] }}</span>
                                </div>
                                <p class="mt-1 text-[0.75rem] text-slate-400">Hire date: {{ $emp['hire_date'] }}</p>

                                <div class="mt-4">
                                    <div class="h-[0.35rem] w-full overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full rounded-full bg-[#1a56db]" style="width: {{ $emp['progress'] }}%"></div>
                                    </div>
                                    <p class="mt-2 text-[0.75rem] text-slate-500">{{ $emp['progress'] }}% complete</p>
                                </div>
                            </a>
                        @empty
                            <div class="rounded-[0.8rem] border border-slate-200 bg-white p-4 text-sm text-slate-500 shadow-[0_1px_2px_rgba(0,0,0,0.03)]">
                                No employees matched the current search and filters.
                            </div>
                        @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2 flex flex-col h-[58rem] min-h-0 lg:h-[calc(100vh-13rem)]">
                <div class="flex h-full flex-col overflow-hidden rounded-[0.8rem] border border-slate-200 bg-white p-5 shadow-[0_1px_2px_rgba(0,0,0,0.03)]">
                    @if ($selectedEmployee)
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-3">
                                    <h2 class="text-[1.05rem] font-bold text-[#06112e]">{{ $selectedEmployee['name'] }} - {{ $selectedEmployee['type'] }}</h2>
                                    @if ($canCreateTasks && $selectedEmployee['has_assignment'])
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-2 rounded-[0.5rem] border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-[#06112e] shadow-sm transition hover:bg-slate-50"
                                            data-open-modal="add-task-modal"
                                        >
                                            <i class="ti ti-plus text-base"></i>
                                            Add Task
                                        </button>
                                    @endif
                                </div>
                                <p class="mt-1 text-sm text-slate-500">Hire date: {{ $selectedEmployee['hire_date'] }}</p>
                            </div>
                            @if ($canAssignOnboarding && !$selectedEmployee['has_assignment'])
                                <form method="POST" action="{{ route('onboarding.start', $selectedEmployee['id']) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-2 rounded-[0.5rem] bg-[#1a56db] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1e40af]">
                                        <i class="ti ti-plus text-base"></i>
                                        Start Onboarding
                                    </button>
                                </form>
                            @endif
                        </div>

                        @if (!$selectedEmployee['has_assignment'])
                            <div class="mt-5 rounded-[0.7rem] border border-dashed border-slate-200 p-6 text-sm text-slate-500">
                                Not yet assigned.
                            </div>
                        @else
                            <div class="mt-4">
                                <div class="h-[0.35rem] w-full overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-[#1a56db]" style="width: {{ $selectedEmployee['progress'] }}%"></div>
                                </div>
                                <p class="mt-2 text-[0.75rem] text-slate-500">{{ $selectedEmployee['progress'] }}% complete</p>
                            </div>

                            <div class="mt-5 flex min-h-0 flex-1 flex-col gap-3 overflow-y-auto pr-1">
                                @foreach ($selectedEmployee['tasks'] as $task)
                                    <div class="rounded-[0.8rem] border {{ $task['completed'] ? 'border-emerald-100 bg-emerald-50/30' : 'border-slate-200 bg-white' }} p-4 shadow-sm">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                            <div class="flex items-start gap-4">
                                                <i class="ti {{ $task['completed'] ? 'ti-circle-check text-[#10b981]' : 'ti-circle text-slate-400' }} mt-0.5 text-2xl"></i>
                                                <div>
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <p class="text-[0.85rem] {{ $task['completed'] ? 'font-medium text-slate-400 line-through' : 'font-bold text-[#06112e]' }}">{{ $task['title'] }}</p>
                                                        <span class="rounded-full px-2.5 py-1 text-[0.65rem] font-semibold uppercase tracking-wide {{ $ownerBadge($task['assigned_role_label']) }}">
                                                            {{ $task['assigned_role_label'] }}
                                                        </span>
                                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[0.65rem] font-semibold uppercase tracking-wide text-slate-600">
                                                            {{ $task['action_type_label'] }}
                                                        </span>
                                                    </div>
                                                    <p class="mt-1 text-[0.75rem] text-slate-500">{{ $task['category'] }}</p>
                                                    @if ($task['instructions'])
                                                        <p class="mt-2 text-sm text-slate-600">{{ $task['instructions'] }}</p>
                                                    @endif
                                                    @if ($task['document_type'])
                                                        <p class="mt-1 text-xs text-slate-400">Document type: {{ $task['document_type'] }}</p>
                                                    @endif
                                                    @if ($task['submission_file_name'])
                                                        <p class="mt-1 text-sm text-slate-600">Submitted file: {{ $task['submission_file_name'] }}</p>
                                                    @endif
                                                    @if ($task['submission_notes'])
                                                        <p class="mt-1 text-sm text-slate-500">Notes: {{ $task['submission_notes'] }}</p>
                                                    @endif
                                                </div>
                                            </div>

                                            @if ($task['completed'])
                                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-[0.75rem] font-semibold text-emerald-700">
                                                    Completed
                                                </span>
                                            @elseif ($canManageTasks)
                                                <div class="flex flex-col items-end gap-2">
                                                    <div class="flex items-center gap-2">
                                                        @if ($canCreateTasks)
                                                            <button
                                                                type="button"
                                                                title="Edit task"
                                                                aria-label="Edit task"
                                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-sky-200 bg-sky-50 text-sky-700 shadow-sm transition hover:bg-sky-100"
                                                                data-task-edit
                                                                data-task-id="{{ $task['id'] }}"
                                                                data-task-title="{{ $task['title'] }}"
                                                                data-task-category="{{ $task['category'] }}"
                                                                data-task-instructions="{{ $task['instructions'] ?? '' }}"
                                                                data-task-owner="{{ $task['assigned_role'] }}"
                                                                data-task-action="{{ $task['action_type'] }}"
                                                                data-task-document="{{ $task['document_type'] ?? '' }}"
                                                                data-task-documents='@json($task['company_document_ids'] ?? [])'
                                                            >
                                                                <i class="ti ti-edit text-lg"></i>
                                                            </button>

                                                            <form method="POST" action="{{ route('onboarding.tasks.destroy', $task['id']) }}" data-confirm="Delete this onboarding task?" data-confirm-title="Delete Task">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" title="Delete task" aria-label="Delete task" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-700 shadow-sm transition hover:bg-rose-100">
                                                                    <i class="ti ti-x text-lg"></i>
                                                                </button>
                                                            </form>
                                                        @endif

                                                        <form method="POST" action="{{ route('onboarding.tasks.complete', $task['id']) }}">
                                                            @csrf
                                                            <button type="submit" title="Mark done" aria-label="Mark done" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 shadow-sm transition hover:bg-emerald-100">
                                                                <i class="ti ti-check text-lg"></i>
                                                            </button>
                                                        </form>
                                                    </div>

                                                    @if (!empty($task['attached_documents']) || $task['company_contract_download_url'])
                                                        <div class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-800 shadow-sm">
                                                            Attached:
                                                            @foreach ($task['attached_documents'] as $document)
                                                                <a href="{{ $document['download_url'] }}" class="ml-1 inline-flex items-center gap-1 text-blue-700 hover:text-blue-900">
                                                                    <i class="ti ti-download text-[0.8rem]"></i>
                                                                    <span>{{ $document['file_name'] }}</span>
                                                                </a>
                                                            @endforeach
                                                            @if (empty($task['attached_documents']) && $task['company_contract_download_url'])
                                                                <a href="{{ $task['company_contract_download_url'] }}" class="ml-1 inline-flex items-center gap-1 text-blue-700 hover:text-blue-900">
                                                                    <i class="ti ti-download text-[0.8rem]"></i>
                                                                    <span>{{ $task['company_contract_name'] }}</span>
                                                                </a>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @else
                        <div class="rounded-[0.7rem] border border-dashed border-slate-200 p-6 text-sm text-slate-500">
                            Select an employee to view onboarding details.
                        </div>
                    @endif
                </div>

            </div>
        </div>
    @endif

    @if (!$isEmployeeView && $selectedEmployee && $canCreateTasks && $selectedEmployee['has_assignment'])
        <div
            id="add-task-modal"
            class="fixed inset-0 z-50 hidden bg-slate-950/45 p-4 overflow-y-auto justify-center items-center"
            data-modal-backdrop="add-task-modal"
        >
            <div class="my-auto max-h-[calc(100vh-2rem)] sm:max-h-[90vh] w-full max-w-5xl overflow-y-auto rounded-[1.1rem] border border-slate-200 bg-white p-5 shadow-2xl sm:p-6">
                <div class="mb-4 flex items-start justify-between gap-4">
                    <div>
                        <h3 id="task-modal-title" class="text-lg font-bold text-[#06112e]">{{ $taskFormMode === 'edit' ? 'Edit Task' : 'Add Task' }}</h3>
                        <p id="task-modal-subtitle" class="mt-1 text-sm text-slate-500">
                            {{ $taskFormMode === 'edit'
                                ? 'Update the onboarding task details for ' . $selectedEmployee['name'] . '.'
                                : 'Create employee, HR, or supervisor onboarding tasks for ' . $selectedEmployee['name'] . '.' }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                        data-close-modal="add-task-modal"
                    >
                        <i class="ti ti-x text-lg"></i>
                    </button>
                </div>

                <form method="POST" action="{{ $taskFormAction }}" class="space-y-4" id="onboarding-task-form" data-create-action="{{ route('onboarding.tasks.store', $selectedEmployee['id']) }}">
                    @csrf
                    <input type="hidden" name="task_id" id="task_form_task_id" value="{{ old('task_id') }}">
                    <div id="task-form-method-spoof">
                        @if ($taskFormMode === 'edit')
                            @method('PUT')
                        @endif
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-[0.75rem] font-semibold uppercase tracking-wide text-slate-500">Task title</label>
                            <input type="text" name="title" id="task_form_title" value="{{ old('title') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Assist employee">
                        </div>
                        <div>
                            <label class="mb-1 block text-[0.75rem] font-semibold uppercase tracking-wide text-slate-500">Category</label>
                            <select name="category" id="task_form_category" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <option value="">Select category</option>
                                @foreach ($selectedEmployee['category_options'] as $option)
                                    <option value="{{ $option['value'] }}" @selected(old('category') === $option['value'])>{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-[0.75rem] font-semibold uppercase tracking-wide text-slate-500">Instructions</label>
                        <textarea name="instructions" id="task_form_instructions" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Describe what needs to happen">{{ old('instructions') }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-[0.75rem] font-semibold uppercase tracking-wide text-slate-500">Task owner</label>
                            <select name="assigned_role" id="task_form_assigned_role" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" data-assigned-role>
                                @foreach ($selectedEmployee['task_owner_options'] as $option)
                                    <option value="{{ $option['value'] }}" @selected(old('assigned_role') === $option['value'])>{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div data-employee-action-group class="sm:col-span-1">
                            <label class="mb-1 block text-[0.75rem] font-semibold uppercase tracking-wide text-slate-500">Employee action</label>
                            <select name="action_type" id="task_form_action_type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                @foreach ($selectedEmployee['employee_action_options'] as $option)
                                    <option value="{{ $option['value'] }}" @selected(old('action_type') === $option['value'])>{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div data-document-type-group class="sm:col-span-1">
                            <label class="mb-1 block text-[0.75rem] font-semibold uppercase tracking-wide text-slate-500">Attach documents</label>
                            <div class="relative">
                                <button type="button" id="company_documents_toggle" class="flex w-full items-center justify-between rounded-lg border border-slate-300 bg-white px-3 py-2 text-left text-sm text-slate-700 shadow-sm transition hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-[#1a56db]">
                                    <span id="company_documents_label" class="text-sm text-slate-700">Select one or more documents</span>
                                    <i class="ti ti-chevron-down text-base text-slate-500"></i>
                                </button>
                                <div id="company_documents_dropdown" class="absolute left-0 right-0 z-20 hidden mt-1 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                                    <div class="max-h-56 overflow-y-auto p-2">
                                        @foreach ($selectedEmployee['company_document_options'] as $option)
                                            <label class="flex cursor-pointer items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-50">
                                                <input
                                                    type="checkbox"
                                                    name="company_document_ids[]"
                                                    value="{{ $option['value'] }}"
                                                    class="h-4 w-4 rounded border-slate-300 text-[#1a56db] focus:ring-[#1a56db]"
                                                    @checked(collect(old('company_document_ids', []))->contains((string) $option['value']))
                                                >
                                                <span>{{ $option['label'] }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-slate-500">Select one or more company documents for this task.</p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button
                            type="button"
                            class="rounded-[0.55rem] border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                            data-task-cancel
                            data-close-modal="add-task-modal"
                        >
                            Cancel
                        </button>
                        <button type="submit" id="task-form-submit" class="rounded-[0.55rem] bg-[#1a56db] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#1e40af]">
                            {{ $taskFormMode === 'edit' ? 'Save changes' : 'Create task' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <script>
        (() => {
            const filterForm = document.getElementById('onboarding-filters-form');
            const searchField = filterForm?.querySelector('[data-auto-submit-search]');
            let filterSubmitTimer = null;

            searchField?.addEventListener('input', () => {
                if (!filterForm) {
                    return;
                }

                window.clearTimeout(filterSubmitTimer);
                filterSubmitTimer = window.setTimeout(() => {
                    filterForm.submit();
                }, 250);
            });

            filterForm?.querySelectorAll('[data-auto-submit-filter]').forEach((field) => {
                field.addEventListener('change', () => {
                    filterForm.submit();
                });
            });

            const form = document.getElementById('onboarding-task-form');
            const openModal = (modalId) => {
                const modal = document.getElementById(modalId);

                if (!modal) {
                    return;
                }

                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            };

            const closeModal = (modalId) => {
                const modal = document.getElementById(modalId);

                if (!modal) {
                    return;
                }

                modal.classList.remove('flex');
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            };

            document.querySelectorAll('[data-open-modal]').forEach((button) => {
                button.addEventListener('click', () => {
                    openModal(button.dataset.openModal);
                });
            });

            document.querySelectorAll('[data-close-modal]').forEach((button) => {
                button.addEventListener('click', () => {
                    closeModal(button.dataset.closeModal);
                });
            });

            document.querySelectorAll('[data-modal-backdrop]').forEach((modal) => {
                modal.addEventListener('click', (event) => {
                    if (event.target.id !== modal.id) {
                        return;
                    }

                    closeModal(modal.id);
                });
            });

            if (!form) {
                return;
            }

            const ownerSelect = form.querySelector('[data-assigned-role]');
            const actionGroup = form.querySelector('[data-employee-action-group]');
            const documentTypeGroup = form.querySelector('[data-document-type-group]');
            const actionSelect = actionGroup ? actionGroup.querySelector('select[name="action_type"]') : null;
            const taskIdInput = document.getElementById('task_form_task_id');
            const methodSpoof = document.getElementById('task-form-method-spoof');
            const titleField = document.getElementById('task_form_title');
            const categoryField = document.getElementById('task_form_category');
            const instructionsField = document.getElementById('task_form_instructions');
            const ownerField = document.getElementById('task_form_assigned_role');
            const actionField = document.getElementById('task_form_action_type');
            const companyDocumentsToggle = document.getElementById('company_documents_toggle');
            const companyDocumentsDropdown = document.getElementById('company_documents_dropdown');
            const companyDocumentsLabel = document.getElementById('company_documents_label');
            const companyDocumentCheckboxes = Array.from(form.querySelectorAll('input[name="company_document_ids[]"]'));
            const modalTitle = document.getElementById('task-modal-title');
            const modalSubtitle = document.getElementById('task-modal-subtitle');
            const submitButton = document.getElementById('task-form-submit');
            const createAction = form.dataset.createAction;

            const setTaskFormMode = (mode, task = null) => {
                if (mode === 'edit' && task) {
                    form.action = `/onboarding/tasks/${task.id}`;
                    taskIdInput.value = task.id;
                    methodSpoof.innerHTML = '<input type="hidden" name="_method" value="PUT">';
                    modalTitle.textContent = 'Edit Task';
                    modalSubtitle.textContent = `Update the onboarding task details for {{ $selectedEmployeeName }}.`;
                    submitButton.textContent = 'Save changes';
                    titleField.value = task.title || '';
                    categoryField.value = task.category || '';
                    instructionsField.value = task.instructions || '';
                    ownerField.value = task.owner || 'employee';
                    actionField.value = task.action || 'checklist';

                    const selectedDocuments = task.documents ? JSON.parse(task.documents) : [];
                    companyDocumentCheckboxes.forEach((checkbox) => {
                        checkbox.checked = selectedDocuments.includes(checkbox.value) || selectedDocuments.includes(Number(checkbox.value));
                    });
                } else {
                    form.action = createAction;
                    taskIdInput.value = '';
                    methodSpoof.innerHTML = '';
                    modalTitle.textContent = 'Add Task';
                    modalSubtitle.textContent = 'Create employee, HR, or supervisor onboarding tasks for {{ $selectedEmployeeName }}.';
                    submitButton.textContent = 'Create task';
                    form.reset();

                    companyDocumentCheckboxes.forEach((checkbox) => {
                        checkbox.checked = false;
                    });
                }

                updateCompanyDocumentsLabel();
                closeCompanyDocumentsDropdown();
                syncTaskForm();
            };

            const updateCompanyDocumentsLabel = () => {
                if (!companyDocumentsLabel) {
                    return;
                }

                const selected = companyDocumentCheckboxes
                    .filter((checkbox) => checkbox.checked)
                    .map((checkbox) => checkbox.nextElementSibling?.textContent?.trim())
                    .filter(Boolean);

                if (selected.length === 0) {
                    companyDocumentsLabel.textContent = 'Select one or more documents';
                } else if (selected.length === 1) {
                    companyDocumentsLabel.textContent = selected[0];
                } else {
                    companyDocumentsLabel.textContent = `${selected.length} documents selected`;
                }
            };

            const syncTaskForm = () => {
                const isEmployeeTask = ownerSelect && ownerSelect.value === 'employee';
                // show attach documents for any action other than simple checklist
                const needsDocumentType = actionSelect && actionSelect.value !== 'checklist';

                if (actionGroup) {
                    actionGroup.classList.toggle('hidden', !isEmployeeTask);
                }

                if (documentTypeGroup) {
                    documentTypeGroup.classList.toggle('hidden', !needsDocumentType);
                }
            };

            const toggleCompanyDocumentsDropdown = () => {
                if (!companyDocumentsDropdown) {
                    return;
                }

                companyDocumentsDropdown.classList.toggle('hidden');
            };

            const closeCompanyDocumentsDropdown = () => {
                if (companyDocumentsDropdown && !companyDocumentsDropdown.classList.contains('hidden')) {
                    companyDocumentsDropdown.classList.add('hidden');
                }
            };

            companyDocumentsToggle?.addEventListener('click', (event) => {
                event.preventDefault();
                toggleCompanyDocumentsDropdown();
            });

            document.addEventListener('click', (event) => {
                if (!companyDocumentsDropdown || !companyDocumentsToggle) {
                    return;
                }

                if (!companyDocumentsDropdown.contains(event.target) && !companyDocumentsToggle.contains(event.target)) {
                    closeCompanyDocumentsDropdown();
                }
            });

            companyDocumentCheckboxes.forEach((checkbox) => {
                checkbox.addEventListener('change', updateCompanyDocumentsLabel);
            });

            ownerSelect?.addEventListener('change', syncTaskForm);
            actionSelect?.addEventListener('change', syncTaskForm);
            syncTaskForm();
            updateCompanyDocumentsLabel();

            document.querySelectorAll('[data-task-edit]').forEach((button) => {
                button.addEventListener('click', () => {
                    setTaskFormMode('edit', {
                        id: button.dataset.taskId,
                        title: button.dataset.taskTitle,
                        category: button.dataset.taskCategory,
                        instructions: button.dataset.taskInstructions,
                        owner: button.dataset.taskOwner,
                        action: button.dataset.taskAction,
                        document: button.dataset.taskDocument,
                        documents: button.dataset.taskDocuments,
                    });

                    openModal('add-task-modal');
                });
            });

            document.querySelectorAll('[data-open-modal="add-task-modal"]').forEach((button) => {
                button.addEventListener('click', () => {
                    setTaskFormMode('create');
                });
            });

            document.querySelectorAll('[data-task-cancel]').forEach((button) => {
                button.addEventListener('click', () => {
                    setTaskFormMode('create');
                });
            });

            @if ($errors->any())
                openModal('add-task-modal');
            @endif

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') {
                    return;
                }

                closeModal('add-task-modal');
            });
        })();
    </script>
</x-app-layout>
