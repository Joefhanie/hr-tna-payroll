<x-app-layout>
    <x-slot:title>Edit Employee</x-slot:title>
    <x-slot:header>Edit Employee</x-slot:header>

    @if ($errors->any())
        <div class="mb-6 flex gap-3 rounded-xl border border-red-200 bg-red-50 p-4">
            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="mb-1 text-sm font-semibold text-red-700">Please fix the following errors:</p>
                <ul class="space-y-0.5 text-sm text-red-600">
                    @foreach ($errors->all() as $error)
                        <li>• {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('employees.update', $employee) }}" class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
        @csrf
        @method('PUT')
        @include('employees._form', [
            'employee' => $employee,
            'departments' => $departments,
            'positions' => $positions,
            'managers' => $managers,
            'isEdit' => true,
        ])
    </form>
</x-app-layout>
