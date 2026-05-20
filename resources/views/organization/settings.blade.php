<x-app-layout>
    <x-slot:title>Organization Settings</x-slot:title>
    <x-slot:header>Settings</x-slot:header>

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Company Details</h1>
        <p class="mt-1 text-sm text-slate-600">Manage company information and upload your logo.</p>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-green-800">{{ session('success') }}</div>
    @endif

    <form action="{{ route('organization.settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700">Company Name</label>
                <input name="company_name" value="{{ old('company_name', $settings->company_name) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Tagline</label>
                <input name="tagline" value="{{ old('tagline', $settings->tagline) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Email</label>
                <input name="email" value="{{ old('email', $settings->email) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Phone</label>
                <input name="phone" value="{{ old('phone', $settings->phone) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Address</label>
                <input name="address" value="{{ old('address', $settings->address) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">City</label>
                <input name="city" value="{{ old('city', $settings->city) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Country</label>
                <input name="country" value="{{ old('country', $settings->country) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Website</label>
                <input name="website" value="{{ old('website', $settings->website) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">TIN</label>
                <input name="tin" value="{{ old('tin', $settings->tin) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Industry</label>
                <input name="industry" value="{{ old('industry', $settings->industry) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700">Logo (light)</label>
                <div class="mt-2 flex items-center gap-4">
                    <div class="h-20 w-20 overflow-hidden rounded-md border border-slate-200 bg-white flex items-center justify-center">
                        @if($settings->logo_path)
                            <img id="logoPreview" src="{{ asset('storage/' . $settings->logo_path) }}" alt="logo" class="h-full object-contain">
                        @else
                            <div class="text-slate-400">No logo</div>
                        @endif
                    </div>
                    <div>
                        <input type="file" name="logo" id="logoInput" accept="image/*" class="text-sm">
                        <p class="mt-1 text-xs text-slate-400">PNG, JPG. Max 10MB.</p>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Logo (dark)</label>
                <div class="mt-2 flex items-center gap-4">
                    <div class="h-20 w-20 overflow-hidden rounded-md border border-slate-200 bg-white flex items-center justify-center">
                        @if($settings->logo_dark_path)
                            <img id="logoDarkPreview" src="{{ asset('storage/' . $settings->logo_dark_path) }}" alt="logo dark" class="h-full object-contain">
                        @else
                            <div class="text-slate-400">No logo</div>
                        @endif
                    </div>
                    <div>
                        <input type="file" name="logo_dark" id="logoDarkInput" accept="image/*" class="text-sm">
                        <p class="mt-1 text-xs text-slate-400">PNG, JPG. Max 10MB.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('organization.departments.index') }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Cancel</a>
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Save Settings</button>
        </div>
    </form>

    <script>
        document.getElementById('logoInput')?.addEventListener('change', function (e) {
            const f = e.target.files && e.target.files[0];
            if (!f) return;
            const url = URL.createObjectURL(f);
            const img = document.getElementById('logoPreview');
            if (img) img.src = url;
        });

        document.getElementById('logoDarkInput')?.addEventListener('change', function (e) {
            const f = e.target.files && e.target.files[0];
            if (!f) return;
            const url = URL.createObjectURL(f);
            const img = document.getElementById('logoDarkPreview');
            if (img) img.src = url;
        });
    </script>
</x-app-layout>
