<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[radial-gradient(circle_at_top_left,_#f9f5ec,_#f3efe6_40%,_#ece5d7_100%)] text-slate-900">
    <main class="mx-auto flex min-h-screen w-full max-w-6xl items-center px-4 py-10 sm:px-6 lg:px-8">
        <section class="grid w-full overflow-hidden rounded-[2rem] border border-black/10 bg-white/85 shadow-[0_24px_80px_rgba(15,23,42,0.12)] backdrop-blur md:grid-cols-[1.1fr_0.9fr]">
            <div class="relative isolate overflow-hidden bg-slate-950 px-8 py-10 text-white sm:px-10 sm:py-14 lg:px-14 lg:py-16">
                <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(255,255,255,0.09),transparent_40%),radial-gradient(circle_at_top_right,rgba(250,204,21,0.28),transparent_30%),radial-gradient(circle_at_bottom_left,rgba(59,130,246,0.24),transparent_28%)]"></div>
                <div class="relative flex h-full flex-col justify-between gap-10">
                    <div>
                        <p class="mb-4 inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-medium uppercase tracking-[0.24em] text-white/75">HR TNA Payroll</p>
                        <h1 class="max-w-md text-4xl font-semibold tracking-tight sm:text-5xl">Welcome back.</h1>
                        <p class="mt-5 max-w-md text-base leading-7 text-white/75">Sign in with your email or username to access the payroll workspace.</p>
                    </div>

                    <div class="grid gap-3 text-sm text-white/80 sm:grid-cols-2">
                        <div class="rounded-2xl border border-white/10 bg-white/8 p-4">Email or username login</div>
                        <div class="rounded-2xl border border-white/10 bg-white/8 p-4">Session persistence</div>
                        <div class="rounded-2xl border border-white/10 bg-white/8 p-4">Secure authentication</div>
                        <div class="rounded-2xl border border-white/10 bg-white/8 p-4">Remember me option</div>
                    </div>
                </div>
            </div>

            <div class="px-6 py-8 sm:px-10 sm:py-12 lg:px-14 lg:py-16">
                <div class="max-w-md">
                    <h2 class="text-3xl font-semibold tracking-tight text-slate-950">Sign in</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Don't have an account? <a href="{{ route('register') }}" class="font-semibold text-slate-900 hover:underline">Create one</a></p>

                    @if ($errors->any())
                        <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                            <ul class="space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5">
                        @csrf

                        <div>
                            <label for="login" class="mb-2 block text-sm font-medium text-slate-800">Email or username</label>
                            <input id="login" name="login" type="text" value="{{ old('login') }}" required autofocus class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10" placeholder="Enter your email or username">
                        </div>

                        <div>
                            <label for="password" class="mb-2 block text-sm font-medium text-slate-800">Password</label>
                            <div class="relative">
                                <input id="password" name="password" type="password" required autocomplete="current-password" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 pr-12 text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10" placeholder="Enter your password">
                                <button type="button" class="password-toggle absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition" data-input="password" aria-label="Toggle password visibility">
                                    <svg class="eye-icon h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    <svg class="eye-off-icon hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <input id="remember" name="remember" type="checkbox" value="1" class="h-4 w-4 rounded border-slate-300 bg-white text-slate-950 shadow-sm focus:ring-2 focus:ring-slate-900/20">
                            <label for="remember" class="text-sm font-medium text-slate-700">Remember me</label>
                        </div>

                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-slate-950 px-5 py-3.5 text-sm font-semibold text-white shadow-[0_16px_32px_rgba(15,23,42,0.22)] transition hover:-translate-y-0.5 hover:bg-slate-800">Sign in</button>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <script>
        document.querySelectorAll('.password-toggle').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const inputId = this.getAttribute('data-input');
                const input = document.getElementById(inputId);
                const isPassword = input.type === 'password';
                const eyeIcon = this.querySelector('.eye-icon');
                const eyeOffIcon = this.querySelector('.eye-off-icon');

                input.type = isPassword ? 'text' : 'password';
                eyeIcon.classList.toggle('hidden');
                eyeOffIcon.classList.toggle('hidden');
                this.classList.toggle('text-slate-600');
            });
        });
    </script>
</body>
</html>
