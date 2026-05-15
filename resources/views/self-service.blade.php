<x-app-layout>
    <x-slot:title>Self-Service</x-slot:title>
    <x-slot:header>Self-Service</x-slot:header>

    <div>
        <h1 class="text-2xl font-semibold">Self-Service</h1>
        <p class="text-sm text-slate-500">Employee-facing requests and profile tasks.</p>
    </div>

    @php
        $tasks = [
            ['label' => 'Update profile', 'status' => 'Enabled'],
            ['label' => 'Download payslips', 'status' => 'Enabled'],
            ['label' => 'Submit leave request', 'status' => 'Enabled'],
            ['label' => 'Change password', 'status' => 'Enabled'],
        ];
    @endphp

    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Task</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($tasks as $task)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $task['label'] }}</td>
                        <td class="px-4 py-3"><span class="badge badge-green">{{ $task['status'] }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
