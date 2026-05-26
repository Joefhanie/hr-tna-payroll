@php
    $account = session('registration.account', []);
    $profile = session('registration.profile', []);
@endphp

<div class="space-y-8">
    <div>
        <h3 class="brand-section-heading">Account Credentials</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label for="username" class="block text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-2">Username</label>
                <input id="username" name="username" type="text" value="{{ old('username', data_get($account, 'username')) }}" required autofocus autocomplete="username"
                    class="reg-field" placeholder="e.g. jane_doe">
            </div>

            <div>
                <label for="email" class="block text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-2">Work Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', data_get($account, 'email')) }}" required autocomplete="email"
                    class="reg-field" placeholder="e.g. jane@company.com">
            </div>

            <div>
                <label for="password" class="block text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-2">Password</label>
                <div class="relative">
                    <input id="password" name="password" type="password" required autocomplete="new-password"
                        class="reg-field pr-12" placeholder="Minimum 8 characters">
                    <button type="button" class="password-toggle absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition" data-input="password" aria-label="Toggle password visibility">
                        <svg class="eye-icon h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg class="eye-off-icon hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div>
                <label for="password_confirmation" class="block text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-2">Confirm Password</label>
                <div class="relative">
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                        class="reg-field pr-12" placeholder="Repeat password">
                    <button type="button" class="password-toggle absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition" data-input="password_confirmation" aria-label="Toggle password visibility">
                        <svg class="eye-icon h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg class="eye-off-icon hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <input id="remember" name="remember" type="checkbox" value="1" @checked(old('remember', data_get($account, 'remember')) == 1)
                class="brand-checkbox h-4 w-4 rounded border-slate-300">
            <label for="remember" class="text-sm font-semibold text-slate-700">Remember me</label>
        </div>
    </div>

    <div>
        <h3 class="brand-section-heading">Personal Information</h3>
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- First Name -->
    <div>
        <label for="first_name" class="block text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-2">First Name</label>
        <input id="first_name" name="first_name" type="text" value="{{ old('first_name', data_get($profile, 'first_name')) }}" required
            class="reg-field" placeholder="e.g. Juan">
    </div>

    <!-- Middle Name -->
    <div>
        <label for="middle_name" class="block text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-2">Middle Name <span class="text-[10px] font-normal lowercase text-slate-400/80">(optional)</span></label>
        <input id="middle_name" name="middle_name" type="text" value="{{ old('middle_name', data_get($profile, 'middle_name')) }}"
            class="reg-field" placeholder="e.g. Dela">
    </div>

    <!-- Last Name -->
    <div>
        <label for="last_name" class="block text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-2">Last Name</label>
        <input id="last_name" name="last_name" type="text" value="{{ old('last_name', data_get($profile, 'last_name')) }}" required
            class="reg-field" placeholder="e.g. Cruz">
    </div>

    <!-- Gender -->
    <div>
        <label for="gender" class="block text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-2">Gender</label>
        <div class="relative">
            <select id="gender" name="gender" class="reg-field appearance-none pr-10">
                <option value="">Select gender</option>
                @foreach (['Male', 'Female', 'Non-binary', 'Prefer not to say'] as $gender)
                    <option value="{{ $gender }}" @selected(old('gender', data_get($profile, 'gender')) === $gender)>{{ $gender }}</option>
                @endforeach
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
    </div>

    <!-- Birth Date -->
    <div>
        <label for="birth_date" class="block text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-2">Birth Date</label>
        <input id="birth_date" name="birth_date" type="date" value="{{ old('birth_date', data_get($profile, 'birth_date')) }}"
            class="reg-field">
    </div>

    <!-- Nationality -->
    <div>
        <label for="nationality" class="block text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-2">Nationality</label>
        <div class="relative">
            <select id="nationality" name="nationality" class="reg-field appearance-none pr-10">
                <option value="">Select nationality</option>
                @foreach ([
                    'Filipino','American','Australian','British','Canadian','Chinese',
                    'Indian','Indonesian','Japanese','Korean','Malaysian','Singaporean',
                    'Thai','Vietnamese','Other'
                ] as $nationality)
                    <option value="{{ $nationality }}" @selected(old('nationality', data_get($profile, 'nationality')) === $nationality)>{{ $nationality }}</option>
                @endforeach
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
    </div>

    <!-- Marital Status -->
    <div>
        <label for="marital_status" class="block text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-2">Marital Status</label>
        <div class="relative">
            <select id="marital_status" name="marital_status" class="reg-field appearance-none pr-10">
                <option value="">Select status</option>
                @foreach (['Single', 'Married', 'Widowed', 'Divorced', 'Separated'] as $status)
                    <option value="{{ $status }}" @selected(old('marital_status', data_get($profile, 'marital_status')) === $status)>{{ $status }}</option>
                @endforeach
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
    </div>
</div>

    </div>
</div>

<div class="flex items-center justify-end border-t border-slate-100 pt-6 mt-8">
    <button type="submit" class="brand-btn rounded-xl px-6 py-3 text-sm font-semibold shadow-md">Continue</button>
</div>
