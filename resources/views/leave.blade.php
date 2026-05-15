<x-app-layout>
    <x-slot:title>Leave</x-slot:title>
    <x-slot:header>Leave</x-slot:header>

    <div>
        <h1 class="text-2xl font-semibold">Leave</h1>
        <p class="text-sm text-slate-500">Leave requests, approvals, and balances.</p>
    </div>

    @php
        $balances = [
            ['type' => 'Vacation Leave', 'used' => 4, 'total' => 15],
            ['type' => 'Sick Leave', 'used' => 2, 'total' => 10],
            ['type' => 'Emergency Leave', 'used' => 0, 'total' => 3],
            ['type' => 'Bereavement', 'used' => 0, 'total' => 5],
        ];
        $requests = [
            ['name' => 'Karl Mendoza', 'type' => 'Sick Leave', 'from' => 'May 12', 'to' => 'May 16', 'days' => 5, 'status' => 'Approved'],
            ['name' => 'Mia Villanueva', 'type' => 'Vacation', 'from' => 'May 20', 'to' => 'May 24', 'days' => 5, 'status' => 'Pending'],
            ['name' => 'Sophia Lim', 'type' => 'Emergency', 'from' => 'May 14', 'to' => 'May 14', 'days' => 1, 'status' => 'Pending'],
            ['name' => 'Mark Santos', 'type' => 'Vacation', 'from' => 'Jun 02', 'to' => 'Jun 06', 'days' => 5, 'status' => 'Approved'],
        ];
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($balances as $balance)
            <div class="card p-5">
                <p class="text-sm text-slate-500">{{ $balance['type'] }}</p>
                <p class="mt-1 text-2xl font-semibold">{{ $balance['total'] - $balance['used'] }}<span class="text-base font-normal text-slate-400"> / {{ $balance['total'] }}</span></p>
                <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-indigo-600" style="width: {{ ($balance['used'] / $balance['total']) * 100 }}%"></div>
                </div>
                <p class="mt-1 text-xs text-slate-500">{{ $balance['used'] }} used</p>
            </div>
        @endforeach
    </div>

    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Employee</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">From</th>
                    <th class="px-4 py-3">To</th>
                    <th class="px-4 py-3">Days</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($requests as $request)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $request['name'] }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $request['type'] }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $request['from'] }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $request['to'] }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $request['days'] }}</td>
                        <td class="px-4 py-3"><span class="badge {{ $request['status'] === 'Approved' ? 'badge-green' : 'badge-amber' }}">{{ $request['status'] }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
