<x-app-layout>
    <x-slot:title>Create Employee</x-slot:title>
    <x-slot:header>Create Employee</x-slot:header>

    @if (! empty($pendingUser))
        <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
            Creating an employee record for {{ $pendingUser->display_name }}. Saving this form will link the new employee profile to that user account.
        </div>
    @endif

    @if (! empty($pendingUser))
        <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
            Creating an employee record for {{ $pendingUser->display_name }}. Saving this form will link the new employee profile to that user account.
        </div>
    @endif

    <form method="POST" action="{{ route('employees.store') }}" class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
        @csrf
        @include('employees._form', [
            'departments' => $departments,
            'positions' => $positions,
            'managers' => $managers,
            'isEdit' => false,
            'pendingUser' => $pendingUser ?? null,
        ])
    </form>
</x-app-layout>
