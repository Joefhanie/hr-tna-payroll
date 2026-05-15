<x-app-layout>
    <x-slot:title>Timekeeping</x-slot:title>
    <x-slot:header>Timekeeping</x-slot:header>

    <div>
        <h1 class="text-2xl font-semibold">Timekeeping</h1>
        <p class="text-sm text-slate-500">Monitor clock-ins, clock-outs, and attendance health.</p>
    </div>

    @php
        $entries = [
            ['name' => 'Ana Reyes', 'in' => '08:01', 'out' => '17:07', 'status' => 'Present'],
            ['name' => 'Mark Santos', 'in' => '08:22', 'out' => '17:10', 'status' => 'Late'],
            ['name' => 'Sophia Lim', 'in' => '—', 'out' => '—', 'status' => 'Absent'],
        ];
    @endphp

    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Employee</th>
                    <th class="px-4 py-3">Clock In</th>
                    <th class="px-4 py-3">Clock Out</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($entries as $entry)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $entry['name'] }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $entry['in'] }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $entry['out'] }}</td>
                        <td class="px-4 py-3"><span class="badge {{ $entry['status'] === 'Present' ? 'badge-green' : ($entry['status'] === 'Late' ? 'badge-amber' : 'badge-red') }}">{{ $entry['status'] }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
