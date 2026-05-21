<x-app-layout :show-global-alerts="false">
    <x-slot:title>Onboarding</x-slot:title>
    <x-slot:header>Onboarding</x-slot:header>

    <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-[1.65rem] font-bold text-[#06112e]">Onboarding</h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ $isEmployeeView ? 'Complete your onboarding requirements here.' : 'Track onboarding progress and assign action items.' }}
            </p>
        </div>
        @if ($warningMessage)
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-700">
                {{ $warningMessage }}
            </div>
        @endif
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-semibold">Please check the onboarding form and try again.</p>
            <ul class="mt-2 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-4 h-px w-full bg-slate-200"></div>

    @php
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
                                                        <input type="file" name="document_file" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
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
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="flex flex-col gap-3 lg:col-span-1">
                @forelse ($employees as $emp)
                    <a href="{{ route('onboarding', ['employee' => $emp['id']]) }}" class="rounded-[0.8rem] border {{ ($selectedEmployee['id'] ?? null) === $emp['id'] ? 'border-[#1a56db] bg-blue-50/40' : 'border-slate-200 bg-white' }} p-4 shadow-[0_1px_2px_rgba(0,0,0,0.03)] transition hover:border-slate-300 hover:shadow-md">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="font-bold text-[#06112e]">{{ $emp['name'] }}</h3>
                            <span class="rounded-full px-2.5 py-0.5 text-[0.7rem] font-bold {{ $statusBadge($emp['status']) }}">{{ $emp['status'] }}</span>
                        </div>
                        <div class="mt-1 flex items-center gap-2">
                            <p class="text-[0.8rem] text-slate-500">{{ $emp['type'] }}</p>
                            @if ($emp['is_priority_hire'])
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-wide text-amber-700">Priority</span>
                            @endif
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
                        No employees with hire dates are available for onboarding.
                    </div>
                @endforelse
            </div>

            <div class="space-y-4 lg:col-span-2">
                <div class="rounded-[0.8rem] border border-slate-200 bg-white p-5 shadow-[0_1px_2px_rgba(0,0,0,0.03)]">
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

                            <div class="mt-5 flex flex-col gap-3">
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
                                                <form method="POST" action="{{ route('onboarding.tasks.complete', $task['id']) }}">
                                                    @csrf
                                                    <button type="submit" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[0.75rem] font-bold text-[#06112e] shadow-sm transition hover:bg-slate-50">
                                                        Mark done
                                                    </button>
                                                </form>
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
            class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/45 p-4"
            data-modal-backdrop="add-task-modal"
        >
            <div class="max-h-[90vh] w-full max-w-5xl overflow-y-auto rounded-[1.1rem] border border-slate-200 bg-white p-5 shadow-2xl sm:p-6">
                <div class="mb-4 flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-[#06112e]">Add Task</h3>
                        <p class="mt-1 text-sm text-slate-500">Create employee, HR, or supervisor onboarding tasks for {{ $selectedEmployee['name'] }}.</p>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                        data-close-modal="add-task-modal"
                    >
                        <i class="ti ti-x text-lg"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('onboarding.tasks.store', $selectedEmployee['id']) }}" class="space-y-4" id="onboarding-task-form">
                    @csrf
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-[0.75rem] font-semibold uppercase tracking-wide text-slate-500">Task title</label>
                            <input type="text" name="title" value="{{ old('title') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Assist employee">
                        </div>
                        <div>
                            <label class="mb-1 block text-[0.75rem] font-semibold uppercase tracking-wide text-slate-500">Category</label>
                            <select name="category" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <option value="">Select category</option>
                                @foreach ($selectedEmployee['category_options'] as $option)
                                    <option value="{{ $option['value'] }}" @selected(old('category') === $option['value'])>{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-[0.75rem] font-semibold uppercase tracking-wide text-slate-500">Instructions</label>
                        <textarea name="instructions" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Describe what needs to happen">{{ old('instructions') }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-[0.75rem] font-semibold uppercase tracking-wide text-slate-500">Task owner</label>
                            <select name="assigned_role" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" data-assigned-role>
                                @foreach ($selectedEmployee['task_owner_options'] as $option)
                                    <option value="{{ $option['value'] }}" @selected(old('assigned_role') === $option['value'])>{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div data-employee-action-group class="sm:col-span-1">
                            <label class="mb-1 block text-[0.75rem] font-semibold uppercase tracking-wide text-slate-500">Employee action</label>
                            <select name="action_type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                @foreach ($selectedEmployee['employee_action_options'] as $option)
                                    <option value="{{ $option['value'] }}" @selected(old('action_type') === $option['value'])>{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div data-document-type-group class="sm:col-span-1">
                            <label class="mb-1 block text-[0.75rem] font-semibold uppercase tracking-wide text-slate-500">Document type</label>
                            <select name="document_type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <option value="">Select document type</option>
                                @foreach ($selectedEmployee['document_type_options'] as $option)
                                    <option value="{{ $option['value'] }}" @selected(old('document_type') === $option['value'])>{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button
                            type="button"
                            class="rounded-[0.55rem] border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                            data-close-modal="add-task-modal"
                        >
                            Cancel
                        </button>
                        <button type="submit" class="rounded-[0.55rem] bg-[#1a56db] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#1e40af]">
                            Create task
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <script>
        (() => {
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

            const syncTaskForm = () => {
                const isEmployeeTask = ownerSelect && ownerSelect.value === 'employee';
                const needsDocumentType = isEmployeeTask && actionSelect && actionSelect.value === 'document_upload';

                if (actionGroup) {
                    actionGroup.classList.toggle('hidden', !isEmployeeTask);
                }

                if (documentTypeGroup) {
                    documentTypeGroup.classList.toggle('hidden', !needsDocumentType);
                }
            };

            ownerSelect?.addEventListener('change', syncTaskForm);
            actionSelect?.addEventListener('change', syncTaskForm);
            syncTaskForm();

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
