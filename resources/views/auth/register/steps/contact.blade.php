@php
    $account = session('registration.account', []);
    $profile  = session('registration.profile', []);

    $fullPhone   = old('phone', data_get($profile, 'phone', ''));
    $phonePrefix = '+63';
    $phoneLocal  = $fullPhone;

    $prefixes = ['+63', '+1', '+61', '+44', '+65', '+60', '+62', '+66', '+84', '+81', '+82', '+86', '+91'];
    foreach ($prefixes as $prefix) {
        if ($fullPhone && str_starts_with($fullPhone, $prefix)) {
            $phonePrefix = $prefix;
            $phoneLocal  = substr($fullPhone, strlen($prefix));
            break;
        }
    }
@endphp

<div class="space-y-8">

    <!-- Account Credentials Group -->
    <div>
        <h3 class="brand-section-heading">Account Credentials</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            <!-- Username -->
            <div>
                <label for="username" class="block text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-2">Username</label>
                <input id="username" name="username" type="text" value="{{ old('username', data_get($account, 'username')) }}" required autofocus autocomplete="username"
                    class="reg-field" placeholder="e.g. jane_doe">
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-2">Work Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', data_get($account, 'email')) }}" required autocomplete="email"
                    class="reg-field" placeholder="e.g. jane@company.com">
            </div>

            <!-- Password -->
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

            <!-- Confirm Password -->
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

    <!-- Contact & Location Info Group -->
    <div>
        <h3 class="brand-section-heading">Contact &amp; Location Info</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            <!-- Phone -->
            <div>
                <label for="phone_number_local" class="block text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-2">Phone Number</label>
                <div class="flex gap-2">
                    <div class="relative w-32 shrink-0">
                        <select id="phone_prefix" class="reg-field appearance-none pr-8">
                            @foreach ([
                                '+63' => 'PH (+63)', '+1' => 'US/CA (+1)', '+61' => 'AU (+61)',
                                '+44' => 'UK (+44)', '+65' => 'SG (+65)', '+60' => 'MY (+60)',
                                '+62' => 'ID (+62)', '+66' => 'TH (+66)', '+84' => 'VN (+84)',
                                '+81' => 'JP (+81)', '+82' => 'KR (+82)', '+86' => 'CN (+86)', '+91' => 'IN (+91)',
                            ] as $code => $label)
                                <option value="{{ $code }}" @selected($phonePrefix === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>
                    <div class="flex-1">
                        <input id="phone_number_local" type="text" value="{{ $phoneLocal }}"
                            class="reg-field" placeholder="e.g. 9171234567">
                    </div>
                </div>
                <input type="hidden" id="phone" name="phone" value="{{ $fullPhone }}">
            </div>

            <!-- Country -->
            <div>
                <label for="country" class="block text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-2">Country</label>
                <div class="relative">
                    <select id="country" name="country" class="reg-field appearance-none pr-10">
                        <option value="">Select country</option>
                        @foreach ([
                            'Philippines','United States','Australia','United Kingdom','Canada',
                            'Singapore','Malaysia','Indonesia','Thailand','Vietnam',
                            'Japan','South Korea','China','India','Other'
                        ] as $countryOption)
                            <option value="{{ $countryOption }}" @selected(old('country', data_get($profile, 'country', 'Philippines')) === $countryOption)>{{ $countryOption }}</option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
            </div>

            <!-- Address Line 1 -->
            <div class="md:col-span-2">
                <label for="address_line1" class="block text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-2">Address Line 1</label>
                <input id="address_line1" name="address_line1" type="text" value="{{ old('address_line1', data_get($profile, 'address_line1')) }}"
                    class="reg-field" placeholder="e.g. Street / Barangay">
            </div>

            <!-- Address Line 2 -->
            <div class="md:col-span-2">
                <label for="address_line2" class="block text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-2">Address Line 2 <span class="text-[10px] font-normal lowercase text-slate-400/80">(optional)</span></label>
                <input id="address_line2" name="address_line2" type="text" value="{{ old('address_line2', data_get($profile, 'address_line2')) }}"
                    class="reg-field" placeholder="e.g. Subdivision / Building / Unit">
            </div>

            <!-- City / Municipality -->
            <div>
                <label for="city" class="block text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-2">City / Municipality</label>
                <input id="city" name="city" type="text" value="{{ old('city', data_get($profile, 'city')) }}"
                    class="reg-field" placeholder="e.g. Quezon City">
            </div>

            <!-- Province / State -->
            <div>
                <label for="province" class="block text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-2">Province / State</label>
                <input id="province" name="province" type="text" value="{{ old('province', data_get($profile, 'province')) }}"
                    class="reg-field" placeholder="e.g. Metro Manila">
            </div>

            <!-- Postal Code -->
            <div>
                <label for="postal_code" class="block text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-2">Postal Code</label>
                <input id="postal_code" name="postal_code" type="text" value="{{ old('postal_code', data_get($profile, 'postal_code')) }}"
                    class="reg-field" placeholder="e.g. 1100">
            </div>
        </div>
    </div>
</div>

<div class="flex items-center justify-between border-t border-slate-100 pt-6 mt-8">
    <a href="{{ route('register') }}" class="brand-btn-ghost">Back</a>
    <button type="submit" class="brand-btn rounded-xl px-6 py-3 text-sm font-semibold shadow-md">Continue</button>
</div>

<script>
    document.querySelectorAll('.password-toggle').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const input     = document.getElementById(this.getAttribute('data-input'));
            const isPass    = input.type === 'password';
            const eyeIcon   = this.querySelector('.eye-icon');
            const eyeOff    = this.querySelector('.eye-off-icon');
            input.type = isPass ? 'text' : 'password';
            eyeIcon.classList.toggle('hidden');
            eyeOff.classList.toggle('hidden');
        });
    });

    const prefixSelect = document.getElementById('phone_prefix');
    const localInput   = document.getElementById('phone_number_local');
    const hiddenPhone  = document.getElementById('phone');
    const countrySelect = document.getElementById('country');

    function updatePhone() {
        const prefix = prefixSelect.value;
        const local  = localInput.value.replace(/^[0]/, '');
        hiddenPhone.value = prefix && local ? prefix + local : (local || '');
    }

    if (prefixSelect && localInput && hiddenPhone) {
        prefixSelect.addEventListener('change', updatePhone);
        localInput.addEventListener('input', updatePhone);
    }

    const countryToPrefix = {
        'Philippines': '+63', 'United States': '+1', 'Australia': '+61',
        'United Kingdom': '+44', 'Canada': '+1', 'Singapore': '+65',
        'Malaysia': '+60', 'Indonesia': '+62', 'Thailand': '+66',
        'Vietnam': '+84', 'Japan': '+81', 'South Korea': '+82',
        'China': '+86', 'India': '+91'
    };

    if (countrySelect && prefixSelect) {
        countrySelect.addEventListener('change', function () {
            const prefix = countryToPrefix[this.value];
            if (prefix) { prefixSelect.value = prefix; updatePhone(); }
        });
    }
</script>
