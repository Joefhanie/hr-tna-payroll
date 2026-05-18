@php
    $profile = session('registration.profile', []);
    $account = session('registration.account', []);
@endphp

<div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
    Signing up for {{ data_get($account, 'email') }} as {{ data_get($account, 'name') }}.
</div>

<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <label for="first_name" class="mb-2 block text-sm font-medium text-slate-800">First name</label>
        <input id="first_name" name="first_name" type="text" value="{{ old('first_name', data_get($profile, 'first_name')) }}" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10" placeholder="Juan">
    </div>
    <div>
        <label for="middle_name" class="mb-2 block text-sm font-medium text-slate-800">Middle name</label>
        <input id="middle_name" name="middle_name" type="text" value="{{ old('middle_name', data_get($profile, 'middle_name')) }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10" placeholder="Dela">
    </div>
    <div>
        <label for="last_name" class="mb-2 block text-sm font-medium text-slate-800">Last name</label>
        <input id="last_name" name="last_name" type="text" value="{{ old('last_name', data_get($profile, 'last_name')) }}" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10" placeholder="Cruz">
    </div>
    <div>
        <label for="gender" class="mb-2 block text-sm font-medium text-slate-800">Gender</label>
        <select id="gender" name="gender" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-950 shadow-sm outline-none transition focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10">
            <option value="">Select gender</option>
            @foreach (['Male', 'Female', 'Non-binary', 'Prefer not to say'] as $gender)
                <option value="{{ $gender }}" @selected(old('gender', data_get($profile, 'gender')) === $gender)>{{ $gender }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="birth_date" class="mb-2 block text-sm font-medium text-slate-800">Birth date</label>
        <input id="birth_date" name="birth_date" type="date" value="{{ old('birth_date', data_get($profile, 'birth_date')) }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-950 shadow-sm outline-none transition focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10">
    </div>
    <div>
        <label for="nationality" class="mb-2 block text-sm font-medium text-slate-800">Nationality</label>
        <input id="nationality" name="nationality" type="text" value="{{ old('nationality', data_get($profile, 'nationality')) }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10" placeholder="Filipino">
    </div>
    <div>
        <label for="marital_status" class="mb-2 block text-sm font-medium text-slate-800">Marital status</label>
        <select id="marital_status" name="marital_status" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-950 shadow-sm outline-none transition focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10">
            <option value="">Select status</option>
            @foreach (['Single', 'Married', 'Widowed', 'Divorced', 'Separated'] as $maritalStatus)
                <option value="{{ $maritalStatus }}" @selected(old('marital_status', data_get($profile, 'marital_status')) === $maritalStatus)>{{ $maritalStatus }}</option>
            @endforeach
        </select>
    </div>
</div>

<div>
    <label for="phone" class="mb-2 block text-sm font-medium text-slate-800">Phone number</label>
    <input id="phone" name="phone" type="text" value="{{ old('phone', data_get($profile, 'phone')) }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10" placeholder="09xxxxxxxxx">
</div>

<div>
    <label for="country" class="mb-2 block text-sm font-medium text-slate-800">Country</label>
    <input id="country" name="country" type="text" value="{{ old('country', data_get($profile, 'country')) }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10" placeholder="Philippines">
</div>

<div class="sm:col-span-2">
    <label for="address_line1" class="mb-2 block text-sm font-medium text-slate-800">Address line 1</label>
    <input id="address_line1" name="address_line1" type="text" value="{{ old('address_line1', data_get($profile, 'address_line1')) }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10" placeholder="Street / Barangay">
</div>

<div class="sm:col-span-2">
    <label for="address_line2" class="mb-2 block text-sm font-medium text-slate-800">Address line 2</label>
    <input id="address_line2" name="address_line2" type="text" value="{{ old('address_line2', data_get($profile, 'address_line2')) }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10" placeholder="Subdivision / Building / Unit">
</div>

<div>
    <label for="city" class="mb-2 block text-sm font-medium text-slate-800">City / Municipality</label>
    <input id="city" name="city" type="text" value="{{ old('city', data_get($profile, 'city')) }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10" placeholder="Quezon City">
</div>

<div>
    <label for="province" class="mb-2 block text-sm font-medium text-slate-800">Province / State</label>
    <input id="province" name="province" type="text" value="{{ old('province', data_get($profile, 'province')) }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10" placeholder="Metro Manila">
</div>

<div>
    <label for="postal_code" class="mb-2 block text-sm font-medium text-slate-800">Postal code</label>
    <input id="postal_code" name="postal_code" type="text" value="{{ old('postal_code', data_get($profile, 'postal_code')) }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10" placeholder="1100">
</div>

<div class="flex items-center justify-between border-t border-slate-100 pt-6">
    <a href="{{ route('register') }}" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Back</a>
    <button type="submit" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white shadow-[0_16px_32px_rgba(15,23,42,0.22)] transition hover:-translate-y-0.5 hover:bg-slate-800">Continue</button>
</div>
