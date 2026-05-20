<x-app-layout>
    <x-slot:title>Reports</x-slot:title>
    <x-slot:header>Reports</x-slot:header>

    <div>
        <h1 class="text-2xl font-semibold">Reports</h1>
        <p class="text-sm text-slate-500">Operational summaries and exportable reports.</p>
    </div>

    @php
        $reports = [
            ['name' => 'Headcount', 'type' => 'Workforce', 'status' => 'Ready'],
            ['name' => 'Attendance', 'type' => 'Timekeeping', 'status' => 'Ready'],
            ['name' => 'Payroll Summary', 'type' => 'Finance', 'status' => 'Ready'],
            ['name' => 'Leave Utilization', 'type' => 'People Ops', 'status' => 'In progress'],
        ];
    @endphp

    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Report</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($reports as $report)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $report['name'] }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $report['type'] }}</td>
                        <td class="px-4 py-3"><span class="badge {{ $report['status'] === 'Ready' ? 'badge-green' : 'badge-amber' }}">{{ $report['status'] }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
