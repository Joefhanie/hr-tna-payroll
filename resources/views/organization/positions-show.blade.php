<x-app-layout>
    <x-slot:title>{{ $position->title }} - Position</x-slot:title>
    <x-slot:header>{{ $position->title }}</x-slot:header>

    <div class="mb-8">
        <a href="{{ route('organization.departments.index') }}" class="inline-flex items-center gap-2 text-slate-600 hover:text-slate-900 transition">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Back to Departments
        </a>
    </div>

    <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <div class="grid gap-6">
            <div>
                <label class="text-sm font-medium text-slate-700">Position Title</label>
                <p class="mt-2 text-lg font-semibold text-slate-900">{{ $position->title }}</p>
            </div>

            <div>
                <label class="text-sm font-medium text-slate-700">Level</label>
                <p class="mt-2 text-lg font-semibold text-slate-900">{{ $position->level ?? 'N/A' }}</p>
            </div>

            <div>
                <label class="text-sm font-medium text-slate-700">Department</label>
                <p class="mt-2 text-lg font-semibold text-slate-900">{{ $position->department->name ?? 'Unassigned' }}</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-slate-700">Min Salary</label>
                    <p class="mt-2 text-lg font-semibold text-slate-900">{{ $position->min_salary ? '₱' . number_format($position->min_salary, 2) : 'N/A' }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Max Salary</label>
                    <p class="mt-2 text-lg font-semibold text-slate-900">{{ $position->max_salary ? '₱' . number_format($position->max_salary, 2) : 'N/A' }}</p>
                </div>
            </div>

            <div class="flex gap-3 pt-4">
                <a href="{{ route('organization.positions.edit', $position) }}" class="inline-flex items-center gap-2 rounded-lg bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit
                </a>
                <form method="POST" action="{{ route('organization.positions.destroy', $position) }}" onsubmit="return confirm('Delete this position?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-red-300 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-600 transition hover:bg-red-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </section>
</x-app-layout>
