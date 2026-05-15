<x-app-layout>
    <x-slot:title>Onboarding</x-slot:title>
    <x-slot:header>Onboarding</x-slot:header>

    <div>
        <h1 class="text-2xl font-semibold">Onboarding</h1>
        <p class="text-sm text-slate-500">Track new hires through their first weeks.</p>
    </div>

    @php
        $items = [
            ['name' => 'Ana Reyes', 'step' => 'Equipment setup', 'status' => 'In progress'],
            ['name' => 'Jules Tan', 'step' => 'Policy review', 'status' => 'Pending'],
            ['name' => 'Mia Villanueva', 'step' => 'Orientation', 'status' => 'Completed'],
        ];
    @endphp

    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Employee</th>
                    <th class="px-4 py-3">Step</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($items as $item)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $item['name'] }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $item['step'] }}</td>
                        <td class="px-4 py-3"><span class="badge {{ $item['status'] === 'Completed' ? 'badge-green' : ($item['status'] === 'In progress' ? 'badge-blue' : 'badge-amber') }}">{{ $item['status'] }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
