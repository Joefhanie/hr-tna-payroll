<x-app-layout>
    <x-slot:title>Leave</x-slot:title>
    <x-slot:header>Leave</x-slot:header>

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

    <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-[1.65rem] font-bold text-[#06112e]">Leave</h1>
            <p class="mt-1 text-sm text-slate-500">Leave requests, approvals, and balances.</p>
        </div>
        <button type="button" class="inline-flex items-center gap-2 rounded-[0.5rem] bg-[#1a56db] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1e40af]">
            <i class="ti ti-plus text-base"></i>
            Request Leave
        </button>
    </div>

    <div class="mb-4 h-px w-full bg-slate-200"></div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        @foreach ($balances as $balance)
            <div class="rounded-[0.8rem] border border-slate-200 bg-white p-4 shadow-[0_1px_2px_rgba(0,0,0,0.03)]">
                <p class="text-[0.8rem] text-slate-500">{{ $balance['type'] }}</p>
                <p class="mt-1 text-xl font-bold text-[#06112e]">
                    {{ $balance['total'] - $balance['used'] }}
                    <span class="text-[0.9rem] font-normal text-slate-400"> / {{ $balance['total'] }}</span>
                </p>
                <div class="mt-3 h-[0.35rem] w-full overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-[#1a56db]" style="width: {{ ($balance['used'] / $balance['total']) * 100 }}%"></div>
                </div>
                <p class="mt-2 text-[0.7rem] text-slate-500">{{ $balance['used'] }} used</p>
            </div>
        @endforeach
    </div>

    <div class="rounded-[0.8rem] border border-slate-200 bg-white shadow-[0_1px_2px_rgba(0,0,0,0.03)] overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-[0.75rem] font-bold text-slate-500 border-b border-slate-100">
                <tr>
                    <th class="px-5 py-4 font-bold">Employee</th>
                    <th class="px-5 py-4 font-bold">Type</th>
                    <th class="px-5 py-4 font-bold">From</th>
                    <th class="px-5 py-4 font-bold">To</th>
                    <th class="px-5 py-4 font-bold">Days</th>
                    <th class="pl-5 pr-2 py-4 font-bold">Status</th>
                    <th class="pl-2 pr-5 py-4 font-bold text-center">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($requests as $request)
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-5 py-4 font-bold text-[#06112e]">{{ $request['name'] }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $request['type'] }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $request['from'] }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $request['to'] }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $request['days'] }}</td>
                        <td class="pl-5 pr-2 py-4">
                            <span class="rounded-full px-2.5 py-1 text-[0.7rem] font-bold {{ $request['status'] === 'Approved' ? 'bg-[#dcfce7] text-[#166534]' : 'bg-[#fef3c7] text-[#92400e]' }}">
                                {{ $request['status'] }}
                            </span>
                        </td>
                        <td class="pl-2 pr-5 py-4 text-center">
                            @if ($request['status'] === 'Pending')
                                <div class="flex items-center justify-center gap-2">
                                    <button type="button" class="rounded-[0.5rem] bg-[#1a56db] px-3 py-1.5 text-[0.75rem] font-bold text-white shadow-sm transition hover:bg-[#1e40af]">Approve</button>
                                    <button type="button" class="rounded-[0.5rem] border border-slate-200 bg-white px-3 py-1.5 text-[0.75rem] font-bold text-[#06112e] shadow-sm transition hover:bg-slate-50">Decline</button>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
