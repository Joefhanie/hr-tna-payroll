@php
    $account = session('registration.account', []);
    $profile = session('registration.profile', []);
@endphp

<div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
    <div>{{ data_get($account, 'name') }} | {{ data_get($account, 'email') }}</div>
    <div>{{ data_get($profile, 'first_name') }} {{ data_get($profile, 'last_name') }}</div>
</div>

<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <label for="employment_type" class="mb-2 block text-sm font-medium text-slate-800">Employment type</label>
        <select id="employment_type" name="employment_type" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-950 shadow-sm outline-none transition focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10">
            <option value="">Select type</option>
            @foreach ([1 => 'Full-time', 2 => 'Part-time', 3 => 'Contractual', 4 => 'Intern'] as $value => $label)
                <option value="{{ $value }}" @selected(old('employment_type') == $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="status" class="mb-2 block text-sm font-medium text-slate-800">Employment status</label>
        <select id="status" name="status" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-950 shadow-sm outline-none transition focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10">
            <option value="">Select status</option>
            @foreach ([1 => 'Active', 2 => 'Probationary', 3 => 'On Leave', 4 => 'Resigned', 5 => 'Terminated'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status') == $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="hire_date" class="mb-2 block text-sm font-medium text-slate-800">Hire date</label>
        <input id="hire_date" name="hire_date" type="date" value="{{ old('hire_date') }}" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-950 shadow-sm outline-none transition focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10">
    </div>
    <div>
        <label for="regularization_date" class="mb-2 block text-sm font-medium text-slate-800">Regularization date</label>
        <input id="regularization_date" name="regularization_date" type="date" value="{{ old('regularization_date') }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-950 shadow-sm outline-none transition focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10">
    </div>
    <div>
        <label for="department_id" class="mb-2 block text-sm font-medium text-slate-800">Department</label>
        <select id="department_id" name="department_id" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-950 shadow-sm outline-none transition focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10">
            <option value="">Select department</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="position_id" class="mb-2 block text-sm font-medium text-slate-800">Position</label>
        <select id="position_id" name="position_id" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-950 shadow-sm outline-none transition focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10">
            <option value="">Select position</option>
            @foreach ($positions as $position)
                <option value="{{ $position->id }}" @selected(old('position_id') == $position->id)>{{ $position->title }}</option>
            @endforeach
        </select>
    </div>
    <div class="sm:col-span-2">
        <label for="manager_id" class="mb-2 block text-sm font-medium text-slate-800">Direct manager</label>
        <select id="manager_id" name="manager_id" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-950 shadow-sm outline-none transition focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10">
            <option value="">Select manager</option>
            @foreach ($managers as $manager)
                <option value="{{ $manager->id }}" @selected(old('manager_id') == $manager->id)>{{ $manager->full_name ?? $manager->name ?? 'Employee #' . $manager->id }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="flex items-center justify-between border-t border-slate-100 pt-6">
    <a href="{{ route('register.profile') }}" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Back</a>
    <button type="submit" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white shadow-[0_16px_32px_rgba(15,23,42,0.22)] transition hover:-translate-y-0.5 hover:bg-slate-800">Create account</button>
</div>
