<x-app-layout>
    <x-slot:title>Edit Employee</x-slot:title>
    <x-slot:header>Edit Employee</x-slot:header>



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
