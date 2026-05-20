<x-app-layout>
    <x-slot:title>Edit {{ $position->title }} - Position</x-slot:title>
    <x-slot:header>Edit Position</x-slot:header>

    <div class="mb-8">
        <a href="{{ route('organization.departments.index') }}" class="inline-flex items-center gap-2 text-slate-600 hover:text-slate-900 transition">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Back to Departments
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4">
            <ul class="space-y-1 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm max-w-2xl">
        <h2 class="text-lg font-semibold text-slate-900 mb-6">Edit Position</h2>

        <form method="POST" action="{{ route('organization.positions.update', $position) }}" class="grid gap-6">
            @csrf
            @method('PUT')

            <div>
                <label for="position_title" class="mb-2 block text-sm font-medium text-slate-700">Position Title</label>
                <input
                    id="position_title"
                    name="title"
                    type="text"
                    value="{{ old('title', $position->title) }}"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-slate-500 focus:ring-slate-500"
                    placeholder="Payroll Officer"
                >
            </div>

            <div>
                <label for="position_level" class="mb-2 block text-sm font-medium text-slate-700">Level</label>
                <input
                    id="position_level"
                    name="level"
                    type="text"
                    value="{{ old('level', $position->level) }}"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-slate-500 focus:ring-slate-500"
                    placeholder="Senior"
                >
            </div>

            <div>
                <label for="position_department_id" class="mb-2 block text-sm font-medium text-slate-700">Department</label>
                <select
                    id="position_department_id"
                    name="department_id"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-slate-500 focus:ring-slate-500"
                >
                    <option value="">None</option>
                    @foreach ($departmentOptions as $department)
                        <option value="{{ $department['id'] }}" @selected(old('department_id', $position->department_id) == $department['id'])>{{ $department['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="min_salary" class="mb-2 block text-sm font-medium text-slate-700">Min Salary</label>
                    <input
                        id="min_salary"
                        name="min_salary"
                        type="number"
                        step="0.01"
                        min="0"
                        value="{{ old('min_salary', $position->min_salary) }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-slate-500 focus:ring-slate-500"
                    >
                </div>
                <div>
                    <label for="max_salary" class="mb-2 block text-sm font-medium text-slate-700">Max Salary</label>
                    <input
                        id="max_salary"
                        name="max_salary"
                        type="number"
                        step="0.01"
                        min="0"
                        value="{{ old('max_salary', $position->max_salary) }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-slate-500 focus:ring-slate-500"
                    >
                </div>
            </div>

            <div class="flex gap-3 pt-4">
                <button type="submit" class="rounded-lg bg-slate-950 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                    Update Position
                </button>
                <a href="{{ route('organization.positions.show', $position) }}" class="rounded-lg border border-slate-300 bg-white px-6 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Cancel
                </a>
            </div>
        </form>
    </section>
</x-app-layout>
