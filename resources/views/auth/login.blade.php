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
        $logoPath = $company->logo_path ? asset('storage/' . $company->logo_path) : null;
        $faviconUrl = $logoPath ?? asset('favicon.ico');
        $primaryColor = $brandPalette['primary'];
        $primaryHover = $brandPalette['primary_hover'];
        $primarySoft = $brandPalette['primary_soft'];
        $primaryRing = $brandPalette['primary_ring'];
        $textOnPrimary = $brandPalette['text_on_primary'];
        $initials = collect(explode(' ', $companyName))->take(2)->map(fn($w) => strtoupper($w[0]))->implode('');
    @endphp
    <title>Sign in — {{ $companyName }}</title>
    <link rel="icon" href="{{ $faviconUrl }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', system-ui, -apple-system, sans-serif; }

        :root {
            --brand: {{ $primaryColor }};
            --brand-hover: {{ $primaryHover }};
            --brand-soft: {{ $brandPalette['primary_soft'] }};
            --brand-ring: {{ $brandPalette['primary_ring'] }};
            --text-on-brand: {{ $textOnPrimary }};
        }

        body {
            background: #f8fafc;
        }

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
            box-shadow: 0 8px 25px {{ $brandPalette['primary_ring'] }};
        }

        .brand-btn:active {
            transform: translateY(0);
        }

        .brand-input:focus {
            border-color: {{ $primaryColor }};
            box-shadow: 0 0 0 3px {{ $brandPalette['primary_ring'] }};
            outline: none;
        }

        .brand-checkbox:checked {
            accent-color: {{ $primaryColor }};
        }

        .brand-link {
            color: {{ $primaryColor }};
        }
        .brand-link:hover {
            color: {{ $primaryHover }};
        }

        /* Animated background orbs */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.35;
            animation: drift 8s ease-in-out infinite alternate;
        }

        .orb-1 {
            width: 320px;
            height: 320px;
            background: white;
            top: -80px;
            left: -80px;
            animation-delay: 0s;
        }

        .orb-2 {
            width: 200px;
            height: 200px;
            background: {{ $brandPalette['accent'] }};
            bottom: 20%;
            right: -40px;
            animation-delay: 2s;
            opacity: 0.25;
        }

        .orb-3 {
            width: 140px;
            height: 140px;
            background: white;
            bottom: -40px;
            left: 30%;
            animation-delay: 4s;
            opacity: 0.2;
        }

        @keyframes drift {
            0%   { transform: translate(0, 0) scale(1); }
            50%  { transform: translate(12px, -18px) scale(1.04); }
            100% { transform: translate(-8px, 10px) scale(0.97); }
        }

        /* Divider line */
        .divider-line {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #94a3b8;
            font-size: 0.75rem;
        }
        .divider-line::before,
        .divider-line::after {
            content: '';
            flex: 1;
            border-top: 1px solid #e2e8f0;
        }

        /* Login card entrance animation */
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .login-card {
            animation: slideUp 0.5s ease both;
        }

        /* Floating label feel for inputs */
        .input-group {
            position: relative;
        }
    </style>
</head>
<body class="min-h-screen flex">

    {{-- Left panel — brand identity --}}
    <div class="brand-bg hidden lg:flex lg:w-[45%] xl:w-[42%] relative flex-col justify-between p-12 overflow-hidden">

        {{-- Animated orbs --}}
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>

        {{-- Top: Employee Portal badge --}}
        <div class="relative z-10">
            <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-medium uppercase tracking-widest text-white/70">
                <span class="h-1.5 w-1.5 rounded-full bg-white/70 animate-pulse"></span>
                Employee Portal
            </div>
        </div>

        {{-- Center: Company identity hero --}}
        <div class="relative z-10 flex flex-col items-center text-center">
            {{-- Logo --}}
            @if ($logoPath)
                <div class="relative mb-6">
                    {{-- Glow ring --}}
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

            {{-- Company name --}}
            <h1 class="text-3xl xl:text-4xl font-extrabold text-white tracking-tight drop-shadow-lg">
                {{ $companyName }}
            </h1>

            {{-- Industry tag --}}
            @if ($company->industry)
                <span class="mt-2 inline-block rounded-full border border-white/20 bg-white/10 px-3 py-0.5 text-xs font-medium text-white/70 tracking-wide">
                    {{ $company->industry }}
                </span>
            @endif

            {{-- Tagline --}}
            <p class="mt-5 text-white/65 text-sm leading-relaxed max-w-[16rem]">
                {{ $tagline }}
            </p>

            {{-- Decorative divider --}}
            <div class="mt-6 flex items-center gap-3">
                <div class="h-px w-10 bg-white/20 rounded"></div>
                <div class="h-1.5 w-1.5 rounded-full bg-white/30"></div>
                <div class="h-px w-10 bg-white/20 rounded"></div>
            </div>
        </div>

        {{-- Bottom: Footer info --}}
        <div class="relative z-10 space-y-1">
            @if ($company->address || $company->city)
                <p class="text-white/50 text-xs flex items-center gap-1.5">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    {{ trim(($company->address ? $company->address . ', ' : '') . ($company->city ?? '')) }}
                </p>
            @endif
            @if ($company->phone)
                <p class="text-white/50 text-xs flex items-center gap-1.5">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548A1 1 0 0119 10.72V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    {{ $company->phone }}
                </p>
            @endif
            <p class="text-white/30 text-xs mt-2">
                &copy; {{ date('Y') }} {{ $companyName }}. All rights reserved.
            </p>
        </div>
    </div>

    {{-- Right panel — login form --}}
    <div class="flex-1 flex items-center justify-center px-6 py-10 sm:px-10 bg-slate-50">
        <div class="login-card w-full max-w-md">

            {{-- Mobile logo (only shown on small screens) --}}
            <div class="lg:hidden flex items-center gap-3 mb-8">
                @if ($logoPath)
                    <div class="h-10 w-10 rounded-xl overflow-hidden shadow border border-slate-200">
                        <img src="{{ $logoPath }}" alt="{{ $companyName }} Logo" class="h-full w-full object-contain">
                    </div>
                @else
                    <div class="h-10 w-10 rounded-xl flex items-center justify-center text-white font-bold text-sm shadow" style="background: {{ $primaryColor }}">
                        {{ $initials }}
                    </div>
                @endif
                <div>
                    <p class="font-semibold text-slate-900 text-sm">{{ $companyName }}</p>
                    <p class="text-xs text-slate-400">Employee Portal</p>
                </div>
            </div>

            {{-- Form header --}}
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Sign in to your account</h2>
                <p class="mt-1.5 text-sm text-slate-500">
                    Enter your credentials to access your workspace.
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="brand-link font-semibold ml-0.5">New here?</a>
                    @endif
                </p>
            </div>

            {{-- Error messages --}}
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

            {{-- Login form --}}
            <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                @csrf

                {{-- Email / Username --}}
                <div class="input-group">
                    <label for="login" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Email or Username
                    </label>
                    <div class="relative">
                        <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                            <svg class="h-4.5 w-4.5 h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </span>
                        <input
                            id="login"
                            name="login"
                            type="text"
                            value="{{ old('login') }}"
                            required
                            autofocus
                            placeholder="you@company.com"
                            class="brand-input w-full rounded-xl border border-slate-200 bg-white pl-10 pr-4 py-3 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400"
                        >
                    </div>
                </div>

                {{-- Password --}}
                <div class="input-group">
                    <label for="password" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Password
                    </label>
                    <div class="relative">
                        <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                            <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </span>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                            class="brand-input w-full rounded-xl border border-slate-200 bg-white pl-10 pr-12 py-3 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400"
                        >
                        <button type="button" id="togglePassword" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition" aria-label="Toggle password">
                            <svg id="eye-icon" class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg id="eye-off-icon" class="hidden h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Remember me --}}
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input id="remember" name="remember" type="checkbox" value="1"
                            class="brand-checkbox h-4 w-4 rounded border-slate-300 transition">
                        <span class="text-sm text-slate-600">Remember me</span>
                    </label>
                </div>

                {{-- Submit --}}
                <button type="submit" class="brand-btn w-full rounded-xl px-5 py-3.5 text-sm font-semibold shadow-sm">
                    Sign in
                </button>
            </form>

            {{-- Footer --}}
            <p class="mt-8 text-center text-xs text-slate-400">
                &copy; {{ date('Y') }} {{ $companyName }} &mdash; Secure HR Portal
            </p>
        </div>
    </div>

    <script>
        // Password toggle
        const pwdInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eye-icon');
        const eyeOffIcon = document.getElementById('eye-off-icon');

        document.getElementById('togglePassword')?.addEventListener('click', function () {
            const isPassword = pwdInput.type === 'password';
            pwdInput.type = isPassword ? 'text' : 'password';
            eyeIcon.classList.toggle('hidden', !isPassword);
            eyeOffIcon.classList.toggle('hidden', isPassword);
        });
    </script>
</body>
</html>
