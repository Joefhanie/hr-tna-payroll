<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $company = \App\Models\CompanySetting::current();
        $brandPalette = $company->brand_palette;
        $companyName = $company->company_name ?? 'HR System';
        $tagline = $company->tagline ?? 'People Operations Platform';
        $logoPath = $company->logo_path ? route('media.file', ['path' => ltrim($company->logo_path, '/')]) : null;
        $faviconUrl = $logoPath ?? asset('favicon.ico');
        $primaryColor = $brandPalette['primary'];
        $primaryHover = $brandPalette['primary_hover'];
        $primaryRing = $brandPalette['primary_ring'];
        $textOnPrimary = $brandPalette['text_on_primary'];
        $initials = collect(explode(' ', $companyName))->take(2)->map(fn($w) => strtoupper($w[0]))->implode('');

        $stepOrder   = ['personal' => 1, 'contact' => 2, 'employment' => 3];
        $currentStep = isset($step) && is_string($step) ? ($stepOrder[$step] ?? 1) : 1;
    @endphp
    <title>Create Account — {{ $companyName }}</title>
    <link rel="icon" href="{{ $faviconUrl }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', system-ui, -apple-system, sans-serif; }

        .brand-bg {
            background: linear-gradient(145deg, {{ $primaryColor }} 0%, {{ $brandPalette['secondary'] }} 100%);
        }

        .brand-btn {
            background: {{ $primaryColor }};
            color: {{ $textOnPrimary }};
            transition: all 0.2s ease;
        }
        .brand-btn:hover {
            background: {{ $primaryHover }};
            transform: translateY(-1px);
            box-shadow: 0 8px 25px {{ $primaryRing }};
        }
        .brand-btn:active { transform: translateY(0); }

        /* ── Input fields ── */
        .brand-input:focus {
            border-color: {{ $primaryColor }};
            box-shadow: 0 0 0 3px {{ $primaryRing }};
            outline: none;
        }
        /* Applied to every text/select/date input in step partials */
        .reg-field {
            width: 100%;
            border-radius: 0.75rem;
            border: 1px solid rgba(226,232,240,0.7);
            background: rgba(248,250,252,0.5);
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            color: #0f172a;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
        }
        .reg-field::placeholder { color: #94a3b8; }
        .reg-field:focus {
            border-color: {{ $primaryColor }};
            box-shadow: 0 0 0 3px {{ $primaryRing }};
            background: #fff;
        }

        /* ── Section headings ── */
        .brand-section-heading {
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: {{ $primaryColor }};
            border-bottom: 1px solid {{ $brandPalette['primary_soft'] }};
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }

        /* ── Checkbox ── */
        .brand-checkbox { accent-color: {{ $primaryColor }}; }

        /* ── Back / ghost button ── */
        .brand-btn-ghost {
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            background: #fff;
            padding: 0.75rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #334155;
            transition: background 0.15s;
            text-decoration: none;
            display: inline-block;
        }
        .brand-btn-ghost:hover { background: #f8fafc; }

        /* ── Step progress ── */
        .step-active {
            background: {{ $primaryColor }};
            color: {{ $textOnPrimary }};
            box-shadow: 0 4px 14px {{ $primaryRing }};
        }
        .step-done {
            background: {{ $primaryColor }};
            color: {{ $textOnPrimary }};
            opacity: 0.7;
        }
        .step-pending {
            background: #f1f5f9;
            color: #94a3b8;
            border: 1px solid #e2e8f0;
        }
        .step-connector-fill {
            background: {{ $primaryColor }};
        }

        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.35;
            animation: drift 8s ease-in-out infinite alternate;
        }
        .orb-1 { width: 300px; height: 300px; background: white;       top: -80px; left: -80px; animation-delay: 0s; }
        .orb-2 { width: 180px; height: 180px; background: {{ $brandPalette['accent'] }}; bottom: 20%; right: -40px; animation-delay: 2s; opacity: 0.25; }
        .orb-3 { width: 120px; height: 120px; background: white;       bottom: -40px; left: 30%; animation-delay: 4s; opacity: 0.2; }

        @keyframes drift {
            0%   { transform: translate(0, 0) scale(1); }
            50%  { transform: translate(12px, -18px) scale(1.04); }
            100% { transform: translate(-8px, 10px) scale(0.97); }
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .form-card { animation: slideUp 0.45s ease both; }
    </style>
</head>
<body class="min-h-screen flex bg-slate-50">

    {{-- ─── Left brand panel ────────────────────────────────────────── --}}
    <div class="brand-bg hidden lg:flex lg:w-[38%] xl:w-[36%] relative flex-col justify-between p-12 overflow-hidden shrink-0">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>

        {{-- Top badge --}}
        <div class="relative z-10">
            <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-medium uppercase tracking-widest text-white/70">
                <span class="h-1.5 w-1.5 rounded-full bg-white/70 animate-pulse"></span>
                New Account
            </div>
        </div>

        {{-- Centre: Company identity hero --}}
        <div class="relative z-10 flex flex-col items-center text-center">
            @if ($logoPath)
                <div class="relative mb-6">
                    <div class="absolute inset-0 rounded-3xl bg-white/20 blur-xl scale-110"></div>
                    <div class="relative h-28 w-28 rounded-3xl overflow-hidden bg-white shadow-[0_0_0_4px_rgba(255,255,255,0.25),0_20px_60px_rgba(0,0,0,0.3)] p-2">
                        <img src="{{ $logoPath }}" alt="{{ $companyName }} Logo" class="h-full w-full object-contain">
                    </div>
                </div>
            @else
                <div class="relative mb-6">
                    <div class="absolute inset-0 rounded-3xl bg-white/20 blur-xl scale-110"></div>
                    <div class="relative h-28 w-28 rounded-3xl flex items-center justify-center bg-white/20 border-2 border-white/30 shadow-[0_0_0_4px_rgba(255,255,255,0.15),0_20px_60px_rgba(0,0,0,0.3)] text-white font-bold text-4xl backdrop-blur">
                        {{ $initials }}
                    </div>
                </div>
            @endif

            <h1 class="text-3xl xl:text-4xl font-extrabold text-white tracking-tight drop-shadow-lg">
                {{ $companyName }}
            </h1>

            @if ($company->industry)
                <span class="mt-2 inline-block rounded-full border border-white/20 bg-white/10 px-3 py-0.5 text-xs font-medium text-white/70 tracking-wide">
                    {{ $company->industry }}
                </span>
            @endif

            <p class="mt-5 text-white/65 text-sm leading-relaxed max-w-[15rem]">
                {{ $tagline }}
            </p>

            <div class="mt-6 flex items-center gap-3">
                <div class="h-px w-10 bg-white/20 rounded"></div>
                <div class="h-1.5 w-1.5 rounded-full bg-white/30"></div>
                <div class="h-px w-10 bg-white/20 rounded"></div>
            </div>
        </div>

        {{-- Bottom info --}}
        <div class="relative z-10 space-y-1">
            @if ($company->address || $company->city)
                <p class="text-white/50 text-xs flex items-center gap-1.5">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    {{ trim(($company->address ? $company->address . ', ' : '') . ($company->city ?? '')) }}
                </p>
            @endif
            @if ($company->phone)
                <p class="text-white/50 text-xs flex items-center gap-1.5">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548A1 1 0 0119 10.72V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    {{ $company->phone }}
                </p>
            @endif
            <p class="text-white/30 text-xs mt-2">&copy; {{ date('Y') }} {{ $companyName }}</p>
        </div>
    </div>

    {{-- ─── Right form panel ────────────────────────────────────────── --}}
    <div class="flex-1 flex flex-col min-h-screen overflow-y-auto">
        <div class="flex-1 flex items-start justify-center px-6 py-10 sm:px-10">
            <div class="form-card w-full max-w-xl">

                {{-- Mobile logo --}}
                <div class="lg:hidden flex items-center gap-3 mb-8">
                    @if ($logoPath)
                        <div class="h-10 w-10 rounded-xl overflow-hidden shadow border border-slate-200">
                            <img src="{{ $logoPath }}" alt="{{ $companyName }}" class="h-full w-full object-contain">
                        </div>
                    @else
                        <div class="h-10 w-10 rounded-xl flex items-center justify-center text-white font-bold text-sm shadow" style="background: {{ $primaryColor }}">
                            {{ $initials }}
                        </div>
                    @endif
                    <div>
                        <p class="font-semibold text-slate-900 text-sm">{{ $companyName }}</p>
                        <p class="text-xs text-slate-400">New Account</p>
                    </div>
                </div>

                {{-- Step progress --}}
                <div class="mb-8">
                    <div class="flex items-center gap-0">
                        @foreach (['Personal', 'Contact', 'Employment'] as $i => $label)
                            @php $stepNum = $i + 1; @endphp

                            {{-- Step bubble --}}
                            <div class="flex flex-col items-center z-10">
                                <div class="h-9 w-9 rounded-full flex items-center justify-center text-sm font-semibold transition-all duration-300
                                    {{ $currentStep == $stepNum ? 'step-active' : ($currentStep > $stepNum ? 'step-done' : 'step-pending') }}">
                                    @if ($currentStep > $stepNum)
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    @else
                                        {{ $stepNum }}
                                    @endif
                                </div>
                                <span class="mt-1.5 text-[10px] font-semibold uppercase tracking-wide {{ $currentStep == $stepNum ? 'text-slate-800' : 'text-slate-400' }}">
                                    {{ $label }}
                                </span>
                            </div>

                            {{-- Connector (not after last step) --}}
                            @if ($stepNum < 3)
                                <div class="flex-1 h-[2px] bg-slate-100 mx-1 mb-5 rounded overflow-hidden">
                                    <div class="h-full step-connector-fill transition-all duration-500 rounded"
                                         style="width: {{ $currentStep > $stepNum ? '100%' : '0%' }}"></div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>

                {{-- Step header --}}
                <div class="flex items-center gap-4 mb-7">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-white shadow-lg brand-btn">
                        @if ($currentStep == 1)
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        @elseif ($currentStep == 2)
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        @else
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        @endif
                    </div>
                    <div>
                        <h2 class="text-xl font-bold tracking-tight text-slate-900">{{ $title ?? 'Registration' }}</h2>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $description ?? 'Please fill out the form to proceed.' }}</p>
                    </div>
                </div>

                {{-- Errors --}}
                @if ($errors->any())
                    <div class="mb-5 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <svg class="h-5 w-5 shrink-0 mt-0.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <ul class="space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Form --}}
                <form method="POST" action="{{ $formAction }}" class="space-y-5">
                    @csrf
                    @includeIf('auth.register.steps.' . $step)
                </form>

                {{-- Back to login --}}
                <p class="mt-6 text-center text-xs text-slate-400">
                    Already have an account?
                    <a href="{{ route('login') }}" class="font-semibold" style="color: {{ $primaryColor }}">Sign in</a>
                </p>
            </div>
        </div>

        {{-- Mobile footer --}}
        <p class="lg:hidden text-center text-xs text-slate-400 py-4">&copy; {{ date('Y') }} {{ $companyName }}</p>
    </div>

</body>
</html>
