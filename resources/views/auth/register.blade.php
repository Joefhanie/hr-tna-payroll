<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Employee Account</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
    </style>
</head>
<body class="min-h-screen bg-[#f8fafc] text-slate-950 flex items-center justify-center p-4 sm:p-6 md:p-10">
    @php
        $stepOrder = ['personal' => 1, 'contact' => 2, 'employment' => 3];
        $currentStep = isset($step) && is_string($step) ? ($stepOrder[$step] ?? 1) : 1;
        $stepLabels = [
            'personal' => 'Personal',
            'contact' => 'Contact',
            'employment' => 'Employment',
        ];
    @endphp

    <main class="w-full max-w-5xl bg-white rounded-3xl border border-slate-100/80 shadow-[0_8px_30px_rgb(0,0,0,0.02)] p-6 sm:p-10 md:p-14">
        <!-- Step Indicators -->
        <div class="flex justify-between items-center max-w-3xl mx-auto mb-12 relative">
            <!-- Step 1: Personal -->
            <div class="flex flex-col items-center flex-1 z-10">
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-semibold text-sm transition-all duration-300 {{ $currentStep == 1 ? 'bg-indigo-50 text-indigo-600 ring-4 ring-indigo-50' : ($currentStep > 1 ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/10' : 'bg-slate-50 text-slate-400 border border-slate-200/80') }}">
                    @if ($currentStep > 1)
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                    @else
                        1
                    @endif
                </div>
                <span class="mt-2 text-xs font-semibold uppercase tracking-wider {{ $currentStep == 1 ? 'text-slate-800' : 'text-slate-400' }}">Personal</span>
            </div>

            <!-- Connector line 1 -->
            <div class="absolute left-[16.66%] right-[50%] top-5 h-[2px] bg-slate-100 -z-0">
                <div class="h-full bg-indigo-600 transition-all duration-500" style="width: {{ $currentStep > 1 ? '100%' : '0%' }}"></div>
            </div>

            <!-- Step 2: Contact -->
            <div class="flex flex-col items-center flex-1 z-10">
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-semibold text-sm transition-all duration-300 {{ $currentStep == 2 ? 'bg-indigo-50 text-indigo-600 ring-4 ring-indigo-50' : ($currentStep > 2 ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/10' : 'bg-slate-50 text-slate-400 border border-slate-200/80') }}">
                    @if ($currentStep > 2)
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                    @else
                        2
                    @endif
                </div>
                <span class="mt-2 text-xs font-semibold uppercase tracking-wider {{ $currentStep == 2 ? 'text-slate-800' : 'text-slate-400' }}">Contact</span>
            </div>

            <!-- Connector line 2 -->
            <div class="absolute left-[50%] right-[16.66%] top-5 h-[2px] bg-slate-100 -z-0">
                <div class="h-full bg-indigo-600 transition-all duration-500" style="width: {{ $currentStep > 2 ? '100%' : '0%' }}"></div>
            </div>

            <!-- Step 3: Employment -->
            <div class="flex flex-col items-center flex-1 z-10">
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-semibold text-sm transition-all duration-300 {{ $currentStep == 3 ? 'bg-indigo-50 text-indigo-600 ring-4 ring-indigo-50' : 'bg-slate-50 text-slate-400 border border-slate-200/80' }}">
                    3
                </div>
                <span class="mt-2 text-xs font-semibold uppercase tracking-wider {{ $currentStep == 3 ? 'text-slate-800' : 'text-slate-400' }}">Employment</span>
            </div>
        </div>

        <!-- Section Header -->
        <div class="flex items-center gap-4 mb-8">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-lg shadow-indigo-600/15">
                @if($currentStep == 1)
                    <!-- User icon -->
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                @elseif($currentStep == 2)
                    <!-- Phone / Envelope / Contact icon -->
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                    </svg>
                @else
                    <!-- Briefcase / Employment icon -->
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                @endif
            </div>
            <div>
                <h2 class="text-xl font-bold tracking-tight text-slate-900">{{ $title ?? 'Registration' }}</h2>
                <p class="text-xs text-slate-500 font-medium mt-0.5">{{ $description ?? 'Please fill out the form to proceed.' }}</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-red-100 bg-red-50/50 p-4 text-sm text-red-700">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                            {{ $error }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ $formAction }}" class="space-y-6">
            @csrf
            @includeIf('auth.register.steps.' . $step)
        </form>
    </main>
</body>
</html>
