@php
    $profile = session('registration.profile', []);
@endphp

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- First Name -->
    <div>
        <label for="first_name" class="block text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-2">First Name</label>
        <input id="first_name" name="first_name" type="text" value="{{ old('first_name', data_get($profile, 'first_name')) }}" required autofocus
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

<div class="flex items-center justify-end border-t border-slate-100 pt-6 mt-8">
    <button type="submit" class="brand-btn rounded-xl px-6 py-3 text-sm font-semibold shadow-md">Continue</button>
</div>
