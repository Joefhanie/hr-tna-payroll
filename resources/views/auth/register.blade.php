<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create account</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[radial-gradient(circle_at_top_left,_#f9f5ec,_#f3efe6_40%,_#ece5d7_100%)] text-slate-900">
    @php
        $stepOrder = ['credentials' => 1, 'profile' => 2, 'employment' => 3];
        $currentStep = $stepOrder[$step] ?? 1;
        $stepLabels = [
            'credentials' => 'Account',
            'profile' => 'Profile',
            'employment' => 'Employment',
        ];
    @endphp

    <main class="mx-auto flex min-h-screen w-full max-w-6xl items-center px-4 py-10 sm:px-6 lg:px-8">
        <section class="w-full overflow-hidden rounded-[2rem] border border-black/10 bg-white/85 shadow-[0_24px_80px_rgba(15,23,42,0.12)] backdrop-blur">
            <header class="relative isolate overflow-hidden bg-slate-950 px-8 py-10 text-white sm:px-10 sm:py-14 lg:px-14 lg:py-16">
                <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(255,255,255,0.09),transparent_40%),radial-gradient(circle_at_top_right,rgba(250,204,21,0.28),transparent_30%),radial-gradient(circle_at_bottom_left,rgba(59,130,246,0.24),transparent_28%)]"></div>
                <div class="relative flex flex-col gap-8">
                    <div class="max-w-3xl">
                        <p class="mb-4 inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-medium uppercase tracking-[0.24em] text-white/75">HR TNA Payroll</p>
                        <h1 class="max-w-3xl text-4xl font-semibold tracking-tight sm:text-5xl">Create a secure staff account.</h1>
                    </div>
                </div>
            </header>

            <div class="px-6 py-8 sm:px-10 sm:py-12 lg:px-14 lg:py-16">
                <div class="mx-auto w-full max-w-5xl">
                    <header class="mb-8 border-b border-slate-200 pb-6">
                        <div class="flex flex-wrap items-center justify-between gap-4 text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">
                            <span>Step {{ $currentStep }} of 3</span>
                            <span>{{ $stepLabels[$step] ?? 'Account' }}</span>
                        </div>

                        <h2 class="mt-4 text-3xl font-semibold tracking-tight text-slate-950">{{ $title ?? 'Sign up' }}</h2>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">{{ $description ?? 'Create your profile to continue into the payroll system.' }}</p>
                    </header>

                    @if ($errors->any())
                        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                            <ul class="space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ $formAction }}" class="space-y-5">
                        @csrf
                        @includeIf('auth.register.steps.' . $step)
                    </form>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
