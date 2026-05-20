<x-app-layout>
    <x-slot:title>Edit {{ $department->name }} - Department</x-slot:title>
    <x-slot:header>Edit Department</x-slot:header>

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
        <h2 class="text-lg font-semibold text-slate-900 mb-6">Edit Department</h2>

        <form method="POST" action="{{ route('organization.departments.update', $department) }}" class="grid gap-6">
            @csrf
            @method('PUT')

            <div>
                <label for="department_name" class="mb-2 block text-sm font-medium text-slate-700">Department Name</label>
                <input
                    id="department_name"
                    name="name"
                    type="text"
                    value="{{ old('name', $department->name) }}"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-slate-500 focus:ring-slate-500"
                    placeholder="Human Resources"
                >
            </div>

            <div>
                <label for="parent_dept_id" class="mb-2 block text-sm font-medium text-slate-700">Parent Department</label>
                <select
                    id="parent_dept_id"
                    name="parent_dept_id"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-slate-500 focus:ring-slate-500"
                >
                    <option value="">None</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->id }}" @selected(old('parent_dept_id', $department->parent_dept_id) == $dept->id)>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-3 pt-4">
                <button type="submit" class="rounded-lg bg-slate-950 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                    Update Department
                </button>
                <a href="{{ route('organization.departments.show', $department) }}" class="rounded-lg border border-slate-300 bg-white px-6 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Cancel
                </a>
            </div>
        </form>
    </section>
</x-app-layout>
