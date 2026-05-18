<x-app-layout>
    <x-slot:title>Self-Service</x-slot:header>
    <x-slot:header>Self-Service</x-slot:header>

    <div class="mb-6 flex items-center justify-between pb-6 border-b border-slate-200">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Self-Service</h1>
            <p class="mt-1 text-sm text-slate-600">Employee-facing requests and profile tasks.</p>
        </div>
    </div>

    <div class="mb-6 flex items-center gap-3">
        <div class="relative flex-1 max-w-xs bg-white rounded-lg">
            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="search" placeholder="Search requests..." class="w-full rounded-lg border-0 py-2 pl-10 pr-3 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500" />
        </div>
        <button class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
            Filter
        </button>
        <button class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export
        </button>
    </div>

    @php
        $requests = [
            ['code' => 'REQ-0001', 'id' => 1, 'employee' => 'Ana Reyes', 'email' => 'ana.reyes@northwind.ph', 'type' => 'Leave Request', 'date' => '2026-05-18', 'status' => 'Pending'],
            ['code' => 'REQ-0002', 'id' => 2, 'employee' => 'Mark Santos', 'email' => 'mark.santos@northwind.ph', 'type' => 'Profile Update', 'date' => '2026-05-17', 'status' => 'Approved'],
            ['code' => 'REQ-0003', 'id' => 3, 'employee' => 'Liza Cruz', 'email' => 'liza.cruz@northwind.ph', 'type' => 'Payslip Request', 'date' => '2026-05-16', 'status' => 'Pending'],
            ['code' => 'REQ-0004', 'id' => 4, 'employee' => 'Jules Tan', 'email' => 'jules.tan@northwind.ph', 'type' => 'Password Change', 'date' => '2026-05-15', 'status' => 'Completed'],
        ];
        $badge = fn($s) => match($s) {
            'Pending' => 'badge-amber',
            'Approved' => 'badge-green',
            'Completed' => 'badge-blue',
            'Rejected' => 'badge-red',
            default => 'badge-gray',
        };
    @endphp

    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">Employee</th>
                    <th class="px-4 py-3">Request Type</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-center">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($requests as $request)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $request['code'] }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="h-8 w-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-semibold">
                                    {{ collect(explode(' ', $request['employee']))->map(fn($n) => $n[0])->join('') }}
                                </div>
                                <div>
                                    <p class="font-medium text-slate-900">{{ $request['employee'] }}</p>
                                    <p class="text-xs text-slate-500">{{ $request['email'] }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $request['type'] }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $request['date'] }}</td>
                        <td class="px-4 py-3"><span class="badge {{ $badge($request['status']) }}">{{ $request['status'] }}</span></td>
                        <td class="px-4 py-3 text-sm flex items-center justify-center">
                            <a href="{{ route('self-service.profile', $request['id']) }}" class="text-slate-600 hover:text-slate-900 transition">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
