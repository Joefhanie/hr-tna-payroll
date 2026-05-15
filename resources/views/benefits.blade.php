<x-app-layout>
    <x-slot:title>Benefits</x-slot:title>
    <x-slot:header>Benefits</x-slot:header>

    <div>
        <h1 class="text-2xl font-semibold">Benefits</h1>
        <p class="text-sm text-slate-500">Track employee benefits enrollment and status.</p>
    </div>

    @php
        $benefits = [
            ['name' => 'Health Plan', 'status' => 'Active', 'members' => 132],
            ['name' => 'Dental Plan', 'status' => 'Active', 'members' => 98],
            ['name' => 'Life Insurance', 'status' => 'Pending', 'members' => 25],
        ];
    @endphp

    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Benefit</th>
                    <th class="px-4 py-3">Members</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($benefits as $benefit)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $benefit['name'] }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $benefit['members'] }}</td>
                        <td class="px-4 py-3"><span class="badge {{ $benefit['status'] === 'Active' ? 'badge-green' : 'badge-amber' }}">{{ $benefit['status'] }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
