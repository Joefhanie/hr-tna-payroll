<x-app-layout :show-global-alerts="false">
    <x-slot:title>Onboarding</x-slot:title>
    <x-slot:header>Onboarding</x-slot:header>

    <div class="flex items-center justify-between pb-6 border-b border-slate-200">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Onboarding</h1>
            <p class="mt-1 text-sm text-slate-600">
                {{ $isEmployeeView ? 'Complete your onboarding requirements here.' : 'Track onboarding progress and assign action items.' }}
            </p>
        </div>
    </div>

    @if (!$isEmployeeView)
        <form method="GET" action="{{ route('onboarding') }}" class="w-full" id="onboarding-filters-form">
            <input type="hidden" name="employee" value="{{ $selectedEmployee['id'] ?? '' }}">
            <input type="hidden" name="export" value="0" id="onboarding-export-input">

            <div class="rounded-[0.8rem] border border-slate-200 bg-white p-4 shadow-[0_1px_2px_rgba(0,0,0,0.03)]">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div class="flex-1 min-w-0">
                        <div class="relative">
                            <i class="ti ti-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-base text-slate-400"></i>
                            <input
                                type="search"
                                name="q"
                                value="{{ $filters['q'] ?? '' }}"
                                placeholder="Search by name, code, hire date..."
                                autocomplete="off"
                                class="w-full rounded-lg border border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-700 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                id="onboarding-search"
                            >
                        </div>
                    </div>

                    <div class="mt-2 flex w-full flex-wrap items-center gap-2 md:mt-0 md:w-auto">
                        <select name="onboarding_status" id="onboarding-status" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 md:w-[12rem]">
                            <option value="">All Statuses</option>
                            @foreach (($filterOptions['onboarding_statuses'] ?? []) as $option)
                                <option value="{{ $option['value'] }}" @selected(($filters['onboarding_status'] ?? '') === $option['value'])>{{ $option['label'] }}</option>
                            @endforeach
                        </select>

                        <select name="department" id="onboarding-department" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 md:w-[12rem]">
                            <option value="">All Departments</option>
                            @foreach (($filterOptions['departments'] ?? []) as $option)
                                <option value="{{ $option['value'] }}" @selected(($filters['department'] ?? '') === $option['value'])>{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-2 flex items-center gap-2 md:mt-0">
                        <button type="button" id="onboarding-clear" class="rounded-[0.5rem] border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-50">Clear</button>
                        <button type="button" id="onboarding-export" class="inline-flex items-center gap-2 rounded-[0.5rem] border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            <i class="ti ti-file-text"></i>
                            Export CSV
                        </button>
                    </div>
                </div>
            </div>
        </form>
    @endif

    @php
        $selectedEmployeeName = $selectedEmployee['name'] ?? 'the selected employee';
        $statusBadge = fn (string $status) => match ($status) {
            'Completed' => 'bg-emerald-50 text-emerald-700',
            'In Progress' => 'bg-sky-50 text-sky-700',
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
                            <div class="h-full rounded-full bg-indigo-600" style="width: {{ $selectedEmployee['progress'] }}%"></div>
                        </div>
                        <p class="mt-2 text-[0.75rem] text-slate-500">{{ $selectedEmployee['progress'] }}% complete</p>
                    </div>

                    <div class="mt-5 flex flex-col gap-3">
                        @forelse ($selectedEmployee['tasks'] as $task)
                            <details class="group rounded-[0.8rem] border {{ $task['completed'] ? 'border-emerald-100 bg-emerald-50/30' : 'border-slate-200 bg-white' }} shadow-sm" @if (!$task['completed']) open @endif>
                                <summary class="flex cursor-pointer list-none items-start justify-between gap-3 p-4">
                                    <div class="flex items-start gap-4">
                                            <i class="ti {{ $task['completed'] ? 'ti-circle-check text-emerald-600' : 'ti-circle text-slate-400' }} mt-0.5 text-2xl"></i>
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="text-[0.9rem] {{ $task['completed'] ? 'font-medium text-slate-400 line-through' : 'font-bold text-slate-900' }}">{{ $task['title'] }}</p>
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
                                                      <div data-onboarding-upload="{{ $task['id'] }}">
                                                          <label class="mb-1 block text-[0.75rem] font-semibold uppercase tracking-wide text-slate-500">Upload file</label>
                                                          <label for="onboardingFile_{{ $task['id'] }}" id="onboardingDropZone_{{ $task['id'] }}"
                                                              class="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-slate-300 px-4 py-5 text-center transition hover:border-indigo-400 hover:bg-indigo-50/40">
                                                              <svg class="h-8 w-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                                              </svg>
                                                              <p class="mt-1.5 text-sm text-slate-700">
                                                                  <span class="font-medium text-indigo-600">Click to upload</span> or drag and drop
                                                              </p>
                                                              <p class="text-xs text-slate-400">PDF, JPG, JPEG, PNG, DOC, DOCX, XLS, XLSX</p>
                                                          </label>
                                                          <input type="file" id="onboardingFile_{{ $task['id'] }}" name="document_file" required
                                                              accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" class="sr-only">

                                                          {{-- Selected file pill --}}
                                                          <div id="onboardingFilePill_{{ $task['id'] }}" class="hidden mt-2 flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm">
                                                              <svg class="h-4 w-4 shrink-0 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                                              </svg>
                                                              <span id="onboardingFileName_{{ $task['id'] }}" class="flex-1 truncate font-medium text-indigo-700 text-xs"></span>
                                                              <button type="button" data-onboarding-remove="{{ $task['id'] }}"
                                                                  class="ml-1 rounded p-0.5 text-indigo-400 hover:bg-indigo-100 hover:text-indigo-700 transition" title="Remove file">
                                                                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                                  </svg>
                                                              </button>
                                                          </div>
                                                          <p id="onboardingFileError_{{ $task['id'] }}" class="hidden mt-1.5 text-xs text-red-600 font-medium"></p>
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
                                                    <button type="submit" class="rounded-[0.55rem] bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                                                        Submit document
                                                    </button>
                                                </form>
                                            @elseif ($task['action_type'] === 'acknowledgement')
                                                <form method="POST" action="{{ route('onboarding.tasks.submit', $task['id']) }}" class="space-y-3">
                                                    @csrf
                                                    <label class="flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-700">
                                                        <input type="checkbox" name="acknowledged" value="1" class="mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                                        <span>I confirm that I reviewed and accepted this onboarding requirement.</span>
                                                    </label>
                                                    <div>
                                                        <label class="mb-1 block text-[0.75rem] font-semibold uppercase tracking-wide text-slate-500">Notes</label>
                                                        <textarea name="submission_notes" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Optional comment"></textarea>
                                                    </div>
                                                    <button type="submit" class="rounded-[0.55rem] bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
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
                                                    <button type="submit" class="rounded-[0.55rem] bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
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
                <div class="flex h-[58rem] min-h-0 flex-col overflow-hidden rounded-[0.8rem] border border-slate-200 bg-white p-3 shadow-[0_1px_2px_rgba(0,0,0,0.03)] lg:h-[calc(100vh-17.5rem)]">
                    <div class="min-h-0 flex-1 overflow-y-scroll pr-1">
                        <div class="flex flex-col gap-3">
                        @forelse ($employees as $emp)
                            <a href="{{ route('onboarding', ['employee' => $emp['id']]) }}" class="onboarding-employee-card rounded-[0.8rem] border {{ ($selectedEmployee['id'] ?? null) === $emp['id'] ? 'border-indigo-600 bg-indigo-50/40' : 'border-slate-200 bg-white' }} p-4 shadow-[0_1px_2px_rgba(0,0,0,0.03)] transition hover:border-slate-300 hover:shadow-md" data-filter-hidden="false" data-status-key="{{ $emp['status_key'] }}" data-department-id="{{ $emp['department_id'] ?? '' }}">
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
                                        <div class="h-full rounded-full bg-indigo-600" style="width: {{ $emp['progress'] }}%"></div>
                                    </div>
                                    <p class="mt-2 text-[0.75rem] text-slate-500">{{ $emp['progress'] }}% complete</p>
                                </div>
                            </a>
                        @empty
                            <div class="rounded-[0.8rem] border border-slate-200 bg-white p-4 text-sm text-slate-500 shadow-[0_1px_2px_rgba(0,0,0,0.03)]">
                                No employees matched the current search and filters.
                            </div>
                        @endforelse
                        <div id="onboarding-client-empty" class="hidden rounded-[0.8rem] border border-dashed border-slate-200 bg-white p-4 text-sm text-slate-500 shadow-[0_1px_2px_rgba(0,0,0,0.03)]">
                            No employees matched the current search.
                        </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2 flex flex-col h-[58rem] min-h-0 lg:h-[calc(100vh-17.5rem)]">
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
                                    <button type="submit" class="inline-flex items-center gap-2 rounded-[0.5rem] bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
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
                                    <div class="h-full rounded-full bg-indigo-600" style="width: {{ $selectedEmployee['progress'] }}%"></div>
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
                                                                class="action-icon inline-flex h-9 w-9 items-center justify-center rounded-lg border border-sky-200 bg-sky-50 text-sky-700 shadow-sm transition hover:bg-sky-100"
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

                                                            <form method="POST" action="{{ route('onboarding.tasks.destroy', $task['id']) }}" data-confirm="Delete this onboarding task?" data-confirm-title="Delete Task" data-confirm-type="danger" data-confirm-text="Delete">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" title="Delete task" aria-label="Delete task" class="action-icon inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-700 shadow-sm transition hover:bg-rose-100">
                                                                    <i class="ti ti-x text-lg"></i>
                                                                </button>
                                                            </form>
                                                        @endif

                                                        <form method="POST" action="{{ route('onboarding.tasks.complete', $task['id']) }}">
                                                            @csrf
                                                            <button type="submit" title="Mark done" aria-label="Mark done" class="action-icon inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 shadow-sm transition hover:bg-emerald-100">
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

                <form method="POST" action="{{ $taskFormAction }}" class="space-y-4" id="onboarding-task-form" data-create-action="{{ route('onboarding.tasks.store', $selectedEmployee['id']) }}" data-update-action="{{ route('onboarding.tasks.update', ['task' => '__TASK__']) }}">
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
                                <button type="button" id="company_documents_toggle" class="flex w-full items-center justify-between rounded-lg border border-slate-300 bg-white px-3 py-2 text-left text-sm text-slate-700 shadow-sm transition hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
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
                                                    class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
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
                        <button type="submit" id="task-form-submit" class="rounded-[0.55rem] bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
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
            const searchField = document.getElementById('onboarding-search');
            const statusSelect = document.getElementById('onboarding-status');
            const deptSelect = document.getElementById('onboarding-department');
            const clearButton = document.getElementById('onboarding-clear');
            const exportButton = document.getElementById('onboarding-export');
            const exportInput = document.getElementById('onboarding-export-input');

            function filterOnboardingEmployees() {
                const term = (searchField?.value || '').trim().toLowerCase();
                const statusVal = statusSelect?.value || '';
                const deptVal = deptSelect?.value || '';
                const cards = document.querySelectorAll('.onboarding-employee-card');
                const emptyState = document.getElementById('onboarding-client-empty');

                let visibleCount = 0;

                cards.forEach((card) => {
                    const searchable = card.textContent.toLowerCase();
                    const cardStatus = card.getAttribute('data-status-key') || '';
                    const cardDept = card.getAttribute('data-department-id') || '';

                    const matchesSearch = !term || searchable.includes(term);
                    const matchesStatus = !statusVal || cardStatus === statusVal;
                    const matchesDept = !deptVal || cardDept === deptVal;

                    const isVisible = matchesSearch && matchesStatus && matchesDept;
                    card.setAttribute('data-filter-hidden', isVisible ? 'false' : 'true');
                    card.classList.toggle('hidden', !isVisible);

                    if (isVisible) {
                        visibleCount++;
                    }
                });

                if (emptyState) {
                    emptyState.classList.toggle('hidden', visibleCount !== 0);
                }
            }

            searchField?.addEventListener('input', filterOnboardingEmployees);
            statusSelect?.addEventListener('change', filterOnboardingEmployees);
            deptSelect?.addEventListener('change', filterOnboardingEmployees);

            searchField?.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                }
            });

            clearButton?.addEventListener('click', () => {
                if (searchField) searchField.value = '';
                if (statusSelect) statusSelect.value = '';
                if (deptSelect) deptSelect.value = '';
                filterOnboardingEmployees();
            });

            exportButton?.addEventListener('click', () => {
                if (!filterForm || !exportInput) {
                    return;
                }

                exportInput.value = '1';
                filterForm.submit();
                exportInput.value = '0';
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

            if (form) {

            filterOnboardingEmployees();

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
            const updateAction = form.dataset.updateAction;

            const setTaskFormMode = (mode, task = null) => {
                if (mode === 'edit' && task) {
                    form.action = updateAction.replace('__TASK__', task.id);
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
            }

            // ===== Onboarding Document Upload File Pickers =====
            document.querySelectorAll('[data-onboarding-upload]').forEach((wrapper) => {
                const id = wrapper.dataset.onboardingUpload;
                const fileInput = document.getElementById(`onboardingFile_${id}`);
                const dropZone = document.getElementById(`onboardingDropZone_${id}`);
                const filePill = document.getElementById(`onboardingFilePill_${id}`);
                const fileName = document.getElementById(`onboardingFileName_${id}`);
                const fileRemove = wrapper.querySelector(`[data-onboarding-remove="${id}"]`);
                const fileError = document.getElementById(`onboardingFileError_${id}`);

                if (!fileInput) return;

                const ACCEPTED_EXTS = ['.pdf', '.jpg', '.jpeg', '.png', '.doc', '.docx', '.xls', '.xlsx'];

                const getExt = (name) => {
                    const match = name.toLowerCase().match(/\.[^.]+$/);
                    return match ? match[0] : '';
                };

                const showError = (msg) => {
                    if (!fileError) return;
                    fileError.textContent = msg;
                    fileError.classList.remove('hidden');
                };

                const clearError = () => {
                    if (!fileError) return;
                    fileError.textContent = '';
                    fileError.classList.add('hidden');
                };

                const showPill = (file) => {
                    if (fileName) fileName.textContent = file.name;
                    filePill?.classList.remove('hidden');
                    filePill?.classList.add('flex');
                    dropZone?.classList.add('border-indigo-400', 'bg-indigo-50/60');
                };

                const clearPill = () => {
                    if (fileName) fileName.textContent = '';
                    filePill?.classList.add('hidden');
                    filePill?.classList.remove('flex');
                    dropZone?.classList.remove('border-indigo-400', 'bg-indigo-50/60');
                };

                const applyFile = (file) => {
                    clearError();
                    const ext = getExt(file.name);
                    if (!ACCEPTED_EXTS.includes(ext)) {
                        showError(`Invalid file type "${ext || file.name}". Accepted: ${ACCEPTED_EXTS.join(', ')}`);
                        fileInput.value = '';
                        clearPill();
                        return;
                    }
                    showPill(file);
                };

                if (dropZone) {
                    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach((ev) => {
                        dropZone.addEventListener(ev, (e) => { e.preventDefault(); e.stopPropagation(); });
                    });
                    ['dragenter', 'dragover'].forEach((ev) => {
                        dropZone.addEventListener(ev, () => dropZone.classList.add('border-indigo-400', 'bg-indigo-50'));
                    });
                    ['dragleave', 'drop'].forEach((ev) => {
                        dropZone.addEventListener(ev, () => {
                            if (!fileInput.files?.length) dropZone.classList.remove('border-indigo-400', 'bg-indigo-50');
                        });
                    });
                    dropZone.addEventListener('drop', (e) => {
                        const files = e.dataTransfer?.files;
                        if (!files || files.length === 0) return;
                        fileInput.files = files;
                        applyFile(files[0]);
                    });
                }

                fileInput.addEventListener('change', () => {
                    const file = fileInput.files && fileInput.files[0];
                    if (file) applyFile(file);
                    else { clearPill(); clearError(); }
                });

                fileRemove?.addEventListener('click', (e) => {
                    e.preventDefault();
                    fileInput.value = '';
                    clearPill();
                    clearError();
                });

                // AJAX Form Submission to retain form inputs & selected file on validation error
                const form = fileInput?.closest('form');
                form?.addEventListener('submit', async (e) => {
                    e.preventDefault();

                    const submitBtn = form.querySelector('button[type="submit"]');
                    const originalBtnText = submitBtn ? submitBtn.innerHTML : '';
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = `
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Uploading...
                        `;
                    }

                    clearError();

                    try {
                        const formData = new FormData(form);
                        const response = await fetch(form.action, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (response.ok) {
                            window.location.href = response.url || window.location.href;
                        } else {
                            const data = await response.json();
                            const errMsg = data.message || (data.errors ? Object.values(data.errors).flat().join(', ') : 'Upload failed. Please check form values.');
                            showError(errMsg);
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = originalBtnText;
                            }
                        }
                    } catch (error) {
                        showError('An unexpected network error occurred.');
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalBtnText;
                        }
                    }
                });
            });
        })();
    </script>
</x-app-layout>
