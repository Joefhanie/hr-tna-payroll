<x-app-layout>
    <x-slot:title>Employee Profile</x-slot:title>
    <x-slot:header>Employee Profile</x-slot:header>

    @php
        // Sample employee data - replace with actual data from model
        $employee = [
            'name' => 'Ana Reyes',
            'initials' => 'AR',
            'position' => 'Senior Engineer',
            'department' => 'Engineering',
            'code' => 'EMP-0001',
            'documents' => [
                ['name' => 'Employment Contract.pdf', 'date' => 'Mar 14, 2021'],
                ['name' => 'Payslip — Apr 30, 2025.pdf', 'date' => 'Apr 30, 2025'],
                ['name' => 'Certificate of Employment.pdf', 'date' => 'Jan 12, 2024'],
                ['name' => 'BIR Form 2316 (2024).pdf', 'date' => 'Jan 31, 2025'],
            ]
        ];
    @endphp

    <!-- Employee Header -->
    <div class="mb-4 rounded-lg bg-white p-4 shadow-sm">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="h-14 w-14 rounded-full bg-blue-600 text-white flex items-center justify-center text-lg font-bold">
                    {{ $employee['initials'] }}
                </div>
                <div>
                    <h1 class="text-lg font-semibold text-slate-900">{{ $employee['name'] }}</h1>
                    <p class="text-xs text-slate-600">{{ $employee['position'] }} · {{ $employee['department'] }} · {{ $employee['code'] }}</p>
                </div>
            </div>
            <a href="#" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 transition">
                Edit Profile
            </a>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <a href="#" class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white p-3 hover:bg-slate-50 transition shadow-sm">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <span class="text-sm font-medium text-slate-900">Update Profile</span>
        </a>

        <a href="#" class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white p-3 hover:bg-slate-50 transition shadow-sm">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10m-3 5h6m2 5H7a2 2 0 01-2-2V7a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <span class="text-sm font-medium text-slate-900">Request Leave</span>
        </a>

        <a href="#" class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white p-3 hover:bg-slate-50 transition shadow-sm">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <span class="text-sm font-medium text-slate-900">View Payslip</span>
        </a>

        <a href="#" class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white p-3 hover:bg-slate-50 transition shadow-sm">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <span class="text-sm font-medium text-slate-900">Time Logs</span>
        </a>
    </div>

    <!-- My Documents -->
    <div class="rounded-lg bg-white p-4 shadow-sm">
        <h2 class="mb-4 text-base font-semibold text-slate-900">My Documents</h2>
        <div class="space-y-2">
            @foreach ($employee['documents'] as $doc)
                <div class="flex items-center justify-between rounded-lg border border-slate-200 p-3">
                    <div class="flex items-center gap-2">
                        <svg class="h-6 w-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-slate-900">{{ $doc['name'] }}</p>
                            <p class="text-xs text-slate-500">{{ $doc['date'] }}</p>
                        </div>
                    </div>
                    <a href="#" class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-900 transition font-medium text-xs">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Download
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
