<x-app-layout>
    <x-slot:title>Organization Settings</x-slot:title>
    <x-slot:header>Settings</x-slot:header>

    <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
        <!-- Header with gradient background -->
        <div class="mb-6 rounded-lg bg-gradient-to-r from-blue-500 to-blue-600 p-8 text-white shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex items-end gap-4">
                    <div class="relative group">
                        <div class="flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-br from-indigo-400 to-indigo-600 border-4 border-white text-3xl font-bold text-white shadow-lg -mb-2 overflow-hidden cursor-pointer transition hover:brightness-90">
                            @if($settings->logo_path)
                                <img id="headerLogoPreview" src="{{ asset('storage/' . $settings->logo_path) }}" alt="logo" class="h-full w-full object-cover">
                            @else
                                <span id="headerLogoText">{{ strtoupper(substr($settings->company_name ?? 'C', 0, 1)) }}</span>
                            @endif
                        </div>
                        <button type="button" onclick="document.getElementById('logoInput').click()" class="absolute inset-0 flex flex-col items-center justify-end rounded-full bg-black/40 opacity-0 group-hover:opacity-100 transition pb-4">
                            <svg class="h-6 w-6 text-white mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="text-xs font-semibold text-white">Change logo</span>
                        </button>
                    </div>
                    <div class="pb-2">
                        <h1 class="text-3xl font-bold text-white">{{ $settings->company_name ?: 'Company Settings' }}</h1>
                        <p class="text-sm text-blue-100">
                            {{ $settings->email ?: 'No email' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>



        <!-- Main Form -->
        <form id="settingsForm" action="{{ route('organization.settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            <!-- Hidden logo input for header -->
            <input type="file" name="logo" id="logoInput" accept="image/*" class="sr-only">

            <!-- Company Details Section -->
            <div id="companyDetailsSection" class="rounded-lg bg-white p-6 shadow-sm max-h-[70vh] overflow-y-auto">
                <h2 class="mb-4 text-base font-semibold text-slate-900">Company Information</h2>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Company Name</label>
                        <input name="company_name" value="{{ old('company_name', $settings->company_name) }}" placeholder="e.g. Acme Corporation" class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Tagline</label>
                        <input name="tagline" value="{{ old('tagline', $settings->tagline) }}" placeholder="e.g. Innovation & Excellence" class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Email</label>
                        <input name="email" value="{{ old('email', $settings->email) }}" placeholder="e.g. info@company.com" class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Phone Number</label>
                        <div class="mt-1 relative flex rounded-lg border border-slate-200 bg-slate-50 transition-all focus-within:border-indigo-500 focus-within:bg-white focus-within:ring-2 focus-within:ring-indigo-500/20">
                            <div class="flex items-center border-r border-slate-200 bg-slate-100/50 rounded-l-lg overflow-hidden">
                                <select id="phone_country" class="bg-transparent px-3 py-3 text-sm font-semibold text-slate-700 outline-none border-none cursor-pointer focus:ring-0 focus:outline-none">
                                    <option value="PH">🇵🇭 +63</option>
                                    <option value="US">🇺🇸 +1</option>
                                    <option value="SG">🇸🇬 +65</option>
                                    <option value="JP">🇯🇵 +81</option>
                                    <option value="AU">🇦🇺 +61</option>
                                    <option value="GB">🇬🇧 +44</option>
                                    <option value="AE">🇦🇪 +971</option>
                                    <option value="CA">🇨🇦 +1</option>
                                </select>
                            </div>
                            <input type="text" placeholder="917 123 4567" class="w-full bg-transparent px-4 py-3 text-sm text-slate-800 placeholder-slate-400 outline-none">
                            <input name="phone" type="hidden" value="{{ old('phone', $settings->phone) }}">
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700">Address</label>
                        <input name="address" value="{{ old('address', $settings->address) }}" placeholder="Street / Barangay" class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">City</label>
                        <input name="city" value="{{ old('city', $settings->city) }}" placeholder="e.g. Quezon City" class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Country</label>
                        <select name="country" class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <option value="">Select Country</option>
                            <option value="Philippines" @selected(old('country', $settings->country ?? 'Philippines') === 'Philippines')>Philippines</option>
                            <option value="United States" @selected(old('country', $settings->country) === 'United States')>United States</option>
                            <option value="Singapore" @selected(old('country', $settings->country) === 'Singapore')>Singapore</option>
                            <option value="Japan" @selected(old('country', $settings->country) === 'Japan')>Japan</option>
                            <option value="Australia" @selected(old('country', $settings->country) === 'Australia')>Australia</option>
                            <option value="United Kingdom" @selected(old('country', $settings->country) === 'United Kingdom')>United Kingdom</option>
                            <option value="United Arab Emirates" @selected(old('country', $settings->country) === 'United Arab Emirates')>United Arab Emirates</option>
                            <option value="Canada" @selected(old('country', $settings->country) === 'Canada')>Canada</option>
                            <option value="China" @selected(old('country', $settings->country) === 'China')>China</option>
                            <option value="India" @selected(old('country', $settings->country) === 'India')>India</option>
                            <option value="Indonesia" @selected(old('country', $settings->country) === 'Indonesia')>Indonesia</option>
                            <option value="Malaysia" @selected(old('country', $settings->country) === 'Malaysia')>Malaysia</option>
                            <option value="Thailand" @selected(old('country', $settings->country) === 'Thailand')>Thailand</option>
                            <option value="Vietnam" @selected(old('country', $settings->country) === 'Vietnam')>Vietnam</option>
                            <option value="South Korea" @selected(old('country', $settings->country) === 'South Korea')>South Korea</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Website</label>
                        <input name="website" value="{{ old('website', $settings->website) }}" placeholder="e.g. https://www.company.com" class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">TIN</label>
                        <input name="tin" value="{{ old('tin', $settings->tin) }}" placeholder="e.g. 123-456-789-101" class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="mt-6 flex justify-end gap-3 border-t border-slate-200 pt-6">
                    <a href="{{ route('organization.departments.index') }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Cancel</a>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-6 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">Save Settings</button>
                </div>
            </div>

        </form>

            <!-- Company Files Section -->
            <div id="filesSection" class="rounded-lg bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Company Files</h2>
                        <p class="text-sm text-slate-500">Upload and manage company documents.</p>
                    </div>
                    <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm text-white shadow-sm transition hover:bg-indigo-700" onclick="document.getElementById('fileUploadModal').classList.remove('hidden')">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Upload File
                    </button>
                </div>

                <!-- Files Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200">
                                <th class="px-4 py-3 text-left font-medium text-slate-700">File Name</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-700">Type</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-700">Size</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-700">Uploaded</th>
                                <th class="px-4 py-3 text-center font-medium text-slate-700">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                        Company Policy 2024.pdf
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-600">PDF</td>
                                <td class="px-4 py-3 text-slate-600">2.4 MB</td>
                                <td class="px-4 py-3 text-slate-600">May 15, 2026</td>
                                <td class="px-4 py-3 text-center">
                                    <button type="button" class="inline-flex items-center gap-2 rounded px-2 py-1 text-xs text-slate-600 hover:bg-slate-100">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                        Download
                                    </button>
                                </td>
                            </tr>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                        Employee Handbook.docx
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-600">DOCX</td>
                                <td class="px-4 py-3 text-slate-600">1.8 MB</td>
                                <td class="px-4 py-3 text-slate-600">May 10, 2026</td>
                                <td class="px-4 py-3 text-center">
                                    <button type="button" class="inline-flex items-center gap-2 rounded px-2 py-1 text-xs text-slate-600 hover:bg-slate-100">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                        Download
                                    </button>
                                </td>
                            </tr>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                        Code of Conduct.pdf
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-600">PDF</td>
                                <td class="px-4 py-3 text-slate-600">1.2 MB</td>
                                <td class="px-4 py-3 text-slate-600">May 01, 2026</td>
                                <td class="px-4 py-3 text-center">
                                    <button type="button" class="inline-flex items-center gap-2 rounded px-2 py-1 text-xs text-slate-600 hover:bg-slate-100">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                        Download
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 text-center text-sm text-slate-500">
                    <p>Showing 3 files • <span class="font-medium text-slate-700">5.4 MB</span> total</p>
                </div>
            </div>

        <!-- File Upload Modal -->
        <div id="fileUploadModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
            <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Upload Company File</h2>
                        <p class="text-sm text-slate-500">Add a new document for company records.</p>
                    </div>
                    <button type="button" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" onclick="document.getElementById('fileUploadModal').classList.add('hidden')">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form class="space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">File Name / Description</label>
                        <input type="text" placeholder="e.g., Company Policy 2024" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">File Category</label>
                        <select class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <option value="">- Select Category -</option>
                            <option value="policies">Policies</option>
                            <option value="handbook">Employee Handbook</option>
                            <option value="compliance">Compliance</option>
                            <option value="benefits">Benefits</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">File</label>
                        <div class="flex items-center justify-center rounded-lg border-2 border-dashed border-slate-300 px-4 py-8">
                            <div class="text-center">
                                <svg class="mx-auto h-10 w-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <p class="mt-2 text-sm text-slate-700">
                                    <label for="fileInput" class="cursor-pointer font-medium text-indigo-600 hover:text-indigo-500">
                                        Click to upload
                                    </label>
                                    or drag and drop
                                </p>
                                <p class="text-xs text-slate-500">PDF, DOC, DOCX, XLS, XLSX up to 25MB</p>
                            </div>
                            <input type="file" id="fileInput" class="sr-only">
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50" onclick="document.getElementById('fileUploadModal').classList.add('hidden')">Cancel</button>
                        <button type="button" class="inline-flex rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">Upload File</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Logo preview - update both header and branding section
        document.getElementById('logoInput')?.addEventListener('change', function (e) {
            const f = e.target.files && e.target.files[0];
            if (!f) return;
            const url = URL.createObjectURL(f);

            // Update header logo
            const headerLogoPreview = document.getElementById('headerLogoPreview');
            const headerLogoText = document.getElementById('headerLogoText');
            if (headerLogoPreview) {
                headerLogoPreview.src = url;
                headerLogoPreview.style.display = 'block';
            } else if (headerLogoText) {
                // If no image exists, create one
                const headerLogo = document.querySelector('.flex.h-24.w-24');
                if (headerLogo) {
                    headerLogo.innerHTML = `<img id="headerLogoPreview" src="${url}" alt="logo" class="h-full w-full object-cover">`;
                }
            }

            // Update branding section logo
            const logo = document.getElementById('logoPreview');
            if (logo) {
                logo.src = url;
            } else {
                const preview = document.querySelector('.h-20.w-20');
                if (preview) {
                    preview.innerHTML = `<img id="logoPreview" src="${url}" alt="logo" class="h-full w-full object-contain">`;
                }
            }
        });

        // File upload modal close on escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.getElementById('fileUploadModal')?.classList.add('hidden');
            }
        });

        // Drag and drop for file upload
        const fileInput = document.getElementById('fileInput');
        const dropZone = fileInput?.parentElement;

        if (dropZone) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, unhighlight, false);
            });

            function highlight(e) {
                dropZone.classList.add('border-indigo-400', 'bg-indigo-50');
            }

            function unhighlight(e) {
                dropZone.classList.remove('border-indigo-400', 'bg-indigo-50');
            }

            dropZone.addEventListener('drop', handleDrop, false);

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                if (fileInput) fileInput.files = files;
            }
        }

        // --- Country Flag and Phone Prefix Logic ---
        const phoneCountrySelect = document.getElementById('phone_country');
        const phoneDisplay = document.querySelector('input[placeholder="917 123 4567"]');
        const phoneHidden = document.querySelector('input[name="phone"][type="hidden"]');

        const phoneConfigs = {
            'PH': { prefix: '+63', format: formatPh, placeholder: '917 123 4567' },
            'US': { prefix: '+1', format: formatUs, placeholder: '(555) 000-0000' },
            'CA': { prefix: '+1', format: formatUs, placeholder: '(555) 000-0000' },
            'SG': { prefix: '+65', format: formatSg, placeholder: '8123 4567' },
            'JP': { prefix: '+81', format: formatJp, placeholder: '90-1234-5678' },
            'AU': { prefix: '+61', format: formatAu, placeholder: '412 345 678' },
            'GB': { prefix: '+44', format: formatUk, placeholder: '7123 456789' },
            'AE': { prefix: '+971', format: formatUae, placeholder: '50 123 4567' }
        };

        function formatPh(val) {
            const digits = val.replace(/\D/g, '').replace(/^0+/, '');
            if (digits.length <= 3) return digits;
            if (digits.length <= 6) return `${digits.slice(0, 3)} ${digits.slice(3)}`;
            return `${digits.slice(0, 3)} ${digits.slice(3, 6)} ${digits.slice(6, 10)}`;
        }

        function formatUs(val) {
            const digits = val.replace(/\D/g, '').replace(/^0+/, '');
            if (digits.length <= 3) return digits;
            if (digits.length <= 6) return `(${digits.slice(0, 3)}) ${digits.slice(3)}`;
            return `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6, 10)}`;
        }

        function formatSg(val) {
            const digits = val.replace(/\D/g, '').replace(/^0+/, '');
            if (digits.length <= 4) return digits;
            return `${digits.slice(0, 4)} ${digits.slice(4, 8)}`;
        }

        function formatJp(val) {
            const digits = val.replace(/\D/g, '').replace(/^0+/, '');
            if (digits.length <= 2) return digits;
            if (digits.length <= 6) return `${digits.slice(0, 2)}-${digits.slice(2)}`;
            return `${digits.slice(0, 2)}-${digits.slice(2, 6)}-${digits.slice(6, 10)}`;
        }

        function formatAu(val) {
            const digits = val.replace(/\D/g, '').replace(/^0+/, '');
            if (digits.length <= 1) return digits;
            if (digits.length <= 5) return `${digits.slice(0, 1)} ${digits.slice(1)}`;
            return `${digits.slice(0, 1)} ${digits.slice(1, 5)} ${digits.slice(5, 9)}`;
        }

        function formatUk(val) {
            const digits = val.replace(/\D/g, '').replace(/^0+/, '');
            if (digits.length <= 4) return digits;
            return `${digits.slice(0, 4)} ${digits.slice(4, 10)}`;
        }

        function formatUae(val) {
            const digits = val.replace(/\D/g, '').replace(/^0+/, '');
            if (digits.length <= 2) return digits;
            if (digits.length <= 5) return `${digits.slice(0, 2)} ${digits.slice(2)}`;
            return `${digits.slice(0, 2)} ${digits.slice(2, 5)} ${digits.slice(5, 9)}`;
        }

        function updatePhonePrefix() {
            const code = phoneCountrySelect.value;
            const config = phoneConfigs[code] || phoneConfigs['PH'];
            phoneDisplay.placeholder = config.placeholder;
            phoneDisplay.value = config.format(phoneDisplay.value);
            updateHiddenValue();
        }

        function updateHiddenValue() {
            const code = phoneCountrySelect.value;
            const config = phoneConfigs[code] || phoneConfigs['PH'];
            const prefix = config.prefix;
            const rawBody = phoneDisplay.value.trim();
            if (rawBody === '') {
                phoneHidden.value = '';
            } else {
                phoneHidden.value = `${prefix} ${rawBody}`;
            }
        }

        function parseExistingPhone() {
            const initialVal = phoneHidden.value.trim();
            if (initialVal.startsWith('+')) {
                let matchedCode = null;
                let matchedPrefix = '';
                const sortedCodes = Object.keys(phoneConfigs).sort((a, b) => {
                    return phoneConfigs[b].prefix.length - phoneConfigs[a].prefix.length;
                });
                for (const code of sortedCodes) {
                    const prefix = phoneConfigs[code].prefix;
                    if (initialVal.startsWith(prefix)) {
                        matchedCode = code;
                        matchedPrefix = prefix;
                        break;
                    }
                }
                if (matchedCode) {
                    phoneCountrySelect.value = matchedCode;
                    const config = phoneConfigs[matchedCode];
                    phoneDisplay.placeholder = config.placeholder;
                    let body = initialVal.slice(matchedPrefix.length).trim();
                    phoneDisplay.value = config.format(body);
                } else {
                    phoneDisplay.value = initialVal;
                }
            } else {
                const code = phoneCountrySelect.value || 'PH';
                const config = phoneConfigs[code] || phoneConfigs['PH'];
                phoneDisplay.value = config.format(initialVal);
            }
            updatePhonePrefix();
        }

        if (phoneCountrySelect && phoneDisplay && phoneHidden) {
            phoneCountrySelect.addEventListener('change', updatePhonePrefix);
            phoneDisplay.addEventListener('input', (e) => {
                const code = phoneCountrySelect.value;
                const config = phoneConfigs[code] || phoneConfigs['PH'];
                const selectionStart = e.target.selectionStart;
                const prevLength = e.target.value.length;
                e.target.value = config.format(e.target.value);
                const postLength = e.target.value.length;
                const diff = postLength - prevLength;
                e.target.setSelectionRange(selectionStart + diff, selectionStart + diff);
                updateHiddenValue();
            });
            parseExistingPhone();
        }
    </script>
    </div>
</x-app-layout>
