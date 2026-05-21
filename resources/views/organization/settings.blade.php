<x-app-layout>
    <x-slot:title>Organization Settings</x-slot:title>
    <x-slot:header>Settings</x-slot:header>

    @php
        $documentCount = $companyDocuments->count();
        $totalFileSizeKb = (float) $companyDocuments->sum(fn ($document) => (float) ($document->file_size_kb ?? 0));
        $totalSizeLabel = $totalFileSizeKb >= 1024
            ? number_format($totalFileSizeKb / 1024, 2) . ' MB'
            : number_format($totalFileSizeKb, 2) . ' KB';
        $hasCompanyDocumentErrors = $errors->has('title')
            || $errors->has('category')
            || $errors->has('description')
            || $errors->has('document_file');
    @endphp

    <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <p class="font-semibold">Please fix the highlighted fields and try again.</p>
            </div>
        @endif

        <div class="rounded-lg bg-gradient-to-r from-blue-500 to-blue-600 p-8 text-white shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex items-end gap-4">
                    <div class="group relative">
                        <div class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-full border-4 border-white bg-gradient-to-br from-indigo-400 to-indigo-600 text-3xl font-bold text-white shadow-lg">
                            @if ($settings->logo_path)
                                <img id="headerLogoPreview" src="{{ asset('storage/' . $settings->logo_path) }}" alt="logo" class="h-full w-full object-cover">
                            @else
                                <span id="headerLogoText">{{ strtoupper(substr($settings->company_name ?? 'C', 0, 1)) }}</span>
                            @endif
                        </div>
                        <button type="button" onclick="document.getElementById('logoInput').click()" class="absolute inset-0 flex flex-col items-center justify-end rounded-full bg-black/40 pb-4 opacity-0 transition group-hover:opacity-100">
                            <svg class="mb-1 h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="text-xs font-semibold text-white">Change logo</span>
                        </button>
                    </div>

                    <div>
                        <h1 class="text-3xl font-bold text-white">{{ $settings->company_name ?: 'Company Settings' }}</h1>
                        <p class="text-sm text-blue-100">{{ $settings->email ?: 'No email' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <form id="settingsForm" action="{{ route('organization.settings.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="file" name="logo" id="logoInput" accept="image/*" class="sr-only">

            <div id="companyDetailsSection" class="rounded-lg bg-white p-6 shadow-sm">
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
                        <div class="mt-1 flex rounded-lg border border-slate-200 bg-slate-50 transition focus-within:border-indigo-500 focus-within:bg-white focus-within:ring-2 focus-within:ring-indigo-500/20">
                            <div class="flex items-center overflow-hidden rounded-l-lg border-r border-slate-200 bg-slate-100/50">
                                <select id="phone_country" class="cursor-pointer border-none bg-transparent px-3 py-3 text-sm font-semibold text-slate-700 outline-none focus:ring-0">
                                    <option value="PH">PH +63</option>
                                    <option value="US">US +1</option>
                                    <option value="SG">SG +65</option>
                                    <option value="JP">JP +81</option>
                                    <option value="AU">AU +61</option>
                                    <option value="GB">GB +44</option>
                                    <option value="AE">AE +971</option>
                                    <option value="CA">CA +1</option>
                                </select>
                            </div>
                            <input id="phone_display" type="text" placeholder="917 123 4567" class="w-full bg-transparent px-4 py-3 text-sm text-slate-800 placeholder-slate-400 outline-none">
                            <input name="phone" id="phone_hidden" type="hidden" value="{{ old('phone', $settings->phone) }}">
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

                <div class="mt-6 flex justify-end gap-3 border-t border-slate-200 pt-6">
                    <a href="{{ route('organization.departments.index') }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Cancel</a>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-6 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">Save Settings</button>
                </div>
            </div>
        </form>

        <div id="filesSection" class="rounded-lg bg-white p-6 shadow-sm">
            <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Company Files</h2>
                    <p class="text-sm text-slate-500">Upload and manage company documents used across settings and onboarding.</p>
                </div>
                <button type="button" data-open-modal="company-file-modal" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm text-white shadow-sm transition hover:bg-indigo-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Upload File
                </button>
            </div>

            @if ($companyDocuments->isEmpty())
                <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-6 py-10 text-center">
                    <p class="text-sm font-medium text-slate-700">No company files uploaded yet.</p>
                    <p class="mt-1 text-sm text-slate-500">Upload policies, benefits guides, or the latest employment contract here.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200">
                                <th class="px-4 py-3 text-left font-medium text-slate-700">File</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-700">Category</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-700">Size</th>
                                <th class="px-4 py-3 text-left font-medium text-slate-700">Uploaded</th>
                                <th class="px-4 py-3 text-center font-medium text-slate-700">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach ($companyDocuments as $document)
                                @php
                                    $fileSizeKb = (float) ($document->file_size_kb ?? 0);
                                    $fileSizeLabel = $fileSizeKb >= 1024
                                        ? number_format($fileSizeKb / 1024, 2) . ' MB'
                                        : number_format($fileSizeKb, 2) . ' KB';
                                    $extensionLabel = strtoupper((string) ($document->file_extension ?: pathinfo($document->file_name, PATHINFO_EXTENSION)));
                                @endphp
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 text-slate-900">
                                        <div class="flex items-start gap-3">
                                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            </svg>
                                            <div>
                                                <p class="font-medium text-slate-900">{{ $document->title }}</p>
                                                <p class="text-xs text-slate-500">{{ $document->file_name }}</p>
                                                @if ($document->description)
                                                    <p class="mt-1 text-xs text-slate-500">{{ $document->description }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $document->category }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">
                                        {{ $extensionLabel !== '' ? $extensionLabel . ' • ' : '' }}{{ $fileSizeLabel }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">
                                        <div>{{ optional($document->uploaded_at ?? $document->created_at)->format('M d, Y') }}</div>
                                        @if ($document->uploadedBy)
                                            <div class="text-xs text-slate-400">by {{ $document->uploadedBy->name }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="{{ route('organization.settings.company-documents.download', $document) }}" class="inline-flex items-center gap-2 rounded px-2 py-1 text-xs font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                            Download
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="mt-4 text-center text-sm text-slate-500">
                <p>Showing {{ $documentCount }} {{ \Illuminate\Support\Str::plural('file', $documentCount) }} • <span class="font-medium text-slate-700">{{ $totalSizeLabel }}</span> total</p>
            </div>
        </div>
    </div>

    <div id="company-file-modal" data-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
        <div class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl" data-modal-panel>
            <div class="max-h-[90vh] overflow-y-auto p-6">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Upload Company File</h2>
                        <p class="text-sm text-slate-500">Store documents here for settings and employee onboarding.</p>
                    </div>
                    <button type="button" data-close-modal="company-file-modal" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form action="{{ route('organization.settings.company-documents.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div>
                        <label for="company_document_title" class="mb-2 block text-sm font-medium text-slate-700">Title</label>
                        <input id="company_document_title" name="title" type="text" value="{{ old('title') }}" placeholder="e.g. Employment Contract v2026.05" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label for="company_document_category" class="mb-2 block text-sm font-medium text-slate-700">Category</label>
                        <select id="company_document_category" name="category" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <option value="">Select category</option>
                            @foreach ($companyDocumentCategories as $category)
                                <option value="{{ $category }}" @selected(old('category') === $category)>{{ $category }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="company_document_description" class="mb-2 block text-sm font-medium text-slate-700">Description</label>
                        <textarea id="company_document_description" name="description" rows="3" placeholder="Optional note about this file" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">{{ old('description') }}</textarea>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">File</label>
                        <label for="companyDocumentFileInput" id="companyDocumentDropZone" class="flex cursor-pointer items-center justify-center rounded-lg border-2 border-dashed border-slate-300 px-4 py-8 transition hover:border-indigo-400 hover:bg-indigo-50/40">
                            <div class="text-center">
                                <svg class="mx-auto h-10 w-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <p class="mt-2 text-sm text-slate-700">
                                    <span class="font-medium text-indigo-600">Click to upload</span>
                                    or drag and drop
                                </p>
                                <p class="text-xs text-slate-500">PDF, DOC, DOCX, XLS, XLSX, PNG, JPG up to 25MB</p>
                                <p id="companyDocumentFileName" class="mt-2 text-xs font-medium text-slate-600"></p>
                            </div>
                        </label>
                        <input type="file" id="companyDocumentFileInput" name="document_file" class="sr-only" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg">
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" data-close-modal="company-file-modal" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="inline-flex rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">Upload File</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('logoInput')?.addEventListener('change', function (event) {
            const file = event.target.files && event.target.files[0];
            if (!file) {
                return;
            }

            const url = URL.createObjectURL(file);
            const preview = document.getElementById('headerLogoPreview');
            const fallback = document.getElementById('headerLogoText');

            if (preview) {
                preview.src = url;
                return;
            }

            if (fallback) {
                fallback.outerHTML = `<img id="headerLogoPreview" src="${url}" alt="logo" class="h-full w-full object-cover">`;
            }
        });

        (() => {
            const openModal = (modalId) => {
                const modal = document.getElementById(modalId);
                if (!modal) {
                    return;
                }

                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };

            const closeModal = (modalId) => {
                const modal = document.getElementById(modalId);
                if (!modal) {
                    return;
                }

                modal.classList.remove('flex');
                modal.classList.add('hidden');
            };

            document.querySelectorAll('[data-open-modal]').forEach((button) => {
                button.addEventListener('click', () => openModal(button.dataset.openModal));
            });

            document.querySelectorAll('[data-close-modal]').forEach((button) => {
                button.addEventListener('click', () => closeModal(button.dataset.closeModal));
            });

            document.querySelectorAll('[data-modal]').forEach((modal) => {
                modal.addEventListener('click', (event) => {
                    if (event.target === modal) {
                        closeModal(modal.id);
                    }
                });
            });

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') {
                    return;
                }

                document.querySelectorAll('[data-modal]').forEach((modal) => closeModal(modal.id));
            });

            @if ($hasCompanyDocumentErrors)
                openModal('company-file-modal');
            @endif
        })();

        (() => {
            const fileInput = document.getElementById('companyDocumentFileInput');
            const dropZone = document.getElementById('companyDocumentDropZone');
            const fileName = document.getElementById('companyDocumentFileName');

            if (!fileInput || !dropZone) {
                return;
            }

            const updateFileName = () => {
                const file = fileInput.files && fileInput.files[0];
                fileName.textContent = file ? file.name : '';
            };

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach((eventName) => {
                dropZone.addEventListener(eventName, (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                });
            });

            ['dragenter', 'dragover'].forEach((eventName) => {
                dropZone.addEventListener(eventName, () => {
                    dropZone.classList.add('border-indigo-400', 'bg-indigo-50');
                });
            });

            ['dragleave', 'drop'].forEach((eventName) => {
                dropZone.addEventListener(eventName, () => {
                    dropZone.classList.remove('border-indigo-400', 'bg-indigo-50');
                });
            });

            dropZone.addEventListener('drop', (event) => {
                const files = event.dataTransfer?.files;
                if (!files || files.length === 0) {
                    return;
                }

                fileInput.files = files;
                updateFileName();
            });

            fileInput.addEventListener('change', updateFileName);
            updateFileName();
        })();

        (() => {
            const phoneCountrySelect = document.getElementById('phone_country');
            const phoneDisplay = document.getElementById('phone_display');
            const phoneHidden = document.getElementById('phone_hidden');

            if (!phoneCountrySelect || !phoneDisplay || !phoneHidden) {
                return;
            }

            const phoneConfigs = {
                PH: { prefix: '+63', placeholder: '917 123 4567', format: formatPh },
                US: { prefix: '+1', placeholder: '(555) 000-0000', format: formatUs },
                CA: { prefix: '+1', placeholder: '(555) 000-0000', format: formatUs },
                SG: { prefix: '+65', placeholder: '8123 4567', format: formatSg },
                JP: { prefix: '+81', placeholder: '90-1234-5678', format: formatJp },
                AU: { prefix: '+61', placeholder: '412 345 678', format: formatAu },
                GB: { prefix: '+44', placeholder: '7123 456789', format: formatUk },
                AE: { prefix: '+971', placeholder: '50 123 4567', format: formatUae },
            };

            function formatPh(value) {
                const digits = value.replace(/\D/g, '').replace(/^0+/, '');
                if (digits.length <= 3) return digits;
                if (digits.length <= 6) return `${digits.slice(0, 3)} ${digits.slice(3)}`;
                return `${digits.slice(0, 3)} ${digits.slice(3, 6)} ${digits.slice(6, 10)}`;
            }

            function formatUs(value) {
                const digits = value.replace(/\D/g, '').replace(/^0+/, '');
                if (digits.length <= 3) return digits;
                if (digits.length <= 6) return `(${digits.slice(0, 3)}) ${digits.slice(3)}`;
                return `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6, 10)}`;
            }

            function formatSg(value) {
                const digits = value.replace(/\D/g, '').replace(/^0+/, '');
                if (digits.length <= 4) return digits;
                return `${digits.slice(0, 4)} ${digits.slice(4, 8)}`;
            }

            function formatJp(value) {
                const digits = value.replace(/\D/g, '').replace(/^0+/, '');
                if (digits.length <= 2) return digits;
                if (digits.length <= 6) return `${digits.slice(0, 2)}-${digits.slice(2)}`;
                return `${digits.slice(0, 2)}-${digits.slice(2, 6)}-${digits.slice(6, 10)}`;
            }

            function formatAu(value) {
                const digits = value.replace(/\D/g, '').replace(/^0+/, '');
                if (digits.length <= 1) return digits;
                if (digits.length <= 5) return `${digits.slice(0, 1)} ${digits.slice(1)}`;
                return `${digits.slice(0, 1)} ${digits.slice(1, 5)} ${digits.slice(5, 9)}`;
            }

            function formatUk(value) {
                const digits = value.replace(/\D/g, '').replace(/^0+/, '');
                if (digits.length <= 4) return digits;
                return `${digits.slice(0, 4)} ${digits.slice(4, 10)}`;
            }

            function formatUae(value) {
                const digits = value.replace(/\D/g, '').replace(/^0+/, '');
                if (digits.length <= 2) return digits;
                if (digits.length <= 5) return `${digits.slice(0, 2)} ${digits.slice(2)}`;
                return `${digits.slice(0, 2)} ${digits.slice(2, 5)} ${digits.slice(5, 9)}`;
            }

            const updateHiddenValue = () => {
                const config = phoneConfigs[phoneCountrySelect.value] || phoneConfigs.PH;
                const rawBody = phoneDisplay.value.trim();
                phoneHidden.value = rawBody === '' ? '' : `${config.prefix} ${rawBody}`;
            };

            const updatePhonePrefix = () => {
                const config = phoneConfigs[phoneCountrySelect.value] || phoneConfigs.PH;
                phoneDisplay.placeholder = config.placeholder;
                phoneDisplay.value = config.format(phoneDisplay.value);
                updateHiddenValue();
            };

            const parseExistingPhone = () => {
                const initialValue = phoneHidden.value.trim();

                if (!initialValue) {
                    updatePhonePrefix();
                    return;
                }

                if (initialValue.startsWith('+')) {
                    const sortedCodes = Object.keys(phoneConfigs).sort((left, right) => {
                        return phoneConfigs[right].prefix.length - phoneConfigs[left].prefix.length;
                    });

                    for (const code of sortedCodes) {
                        const prefix = phoneConfigs[code].prefix;
                        if (!initialValue.startsWith(prefix)) {
                            continue;
                        }

                        phoneCountrySelect.value = code;
                        phoneDisplay.placeholder = phoneConfigs[code].placeholder;
                        phoneDisplay.value = phoneConfigs[code].format(initialValue.slice(prefix.length).trim());
                        updateHiddenValue();
                        return;
                    }
                }

                const config = phoneConfigs[phoneCountrySelect.value] || phoneConfigs.PH;
                phoneDisplay.value = config.format(initialValue);
                updateHiddenValue();
            };

            phoneCountrySelect.addEventListener('change', updatePhonePrefix);
            phoneDisplay.addEventListener('input', (event) => {
                const config = phoneConfigs[phoneCountrySelect.value] || phoneConfigs.PH;
                const selectionStart = event.target.selectionStart ?? event.target.value.length;
                const previousLength = event.target.value.length;
                event.target.value = config.format(event.target.value);
                const diff = event.target.value.length - previousLength;
                event.target.setSelectionRange(selectionStart + diff, selectionStart + diff);
                updateHiddenValue();
            });

            parseExistingPhone();
        })();
    </script>
</x-app-layout>
