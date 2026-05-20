<div>
    <label for="username" class="mb-2 block text-sm font-medium text-slate-800">Username</label>
    <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus autocomplete="username" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10" placeholder="jane_doe">
</div>

<div>
    <label for="email" class="mb-2 block text-sm font-medium text-slate-800">Work email</label>
    <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10" placeholder="jane@company.com">
</div>

<div>
    <label for="password" class="mb-2 block text-sm font-medium text-slate-800">Password</label>
    <div class="relative">
        <input id="password" name="password" type="password" required autocomplete="new-password" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 pr-12 text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10" placeholder="Minimum 8 characters">
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

<div>
    <label for="password_confirmation" class="mb-2 block text-sm font-medium text-slate-800">Confirm password</label>
    <div class="relative">
        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 pr-12 text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10" placeholder="Repeat password">
        <button type="button" class="password-toggle absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition" data-input="password_confirmation" aria-label="Toggle password visibility">
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

<button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-slate-950 px-5 py-3.5 text-sm font-semibold text-white shadow-[0_16px_32px_rgba(15,23,42,0.22)] transition hover:-translate-y-0.5 hover:bg-slate-800">Continue</button>

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
