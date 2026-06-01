<x-app-layout>
    <x-slot:title>Machines</x-slot:title>
    <x-slot:header>Settings</x-slot:header>

    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Machines</h1>
                <p class="mt-1 text-sm text-slate-500">Manage per-machine configuration values.</p>
            </div>
            <a href="{{ route('organization.settings') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Back to Company Settings</a>
        </div>

        <div class="rounded-lg bg-white p-6 shadow-sm">
            <div class="mb-3 flex items-center gap-2">
                <input type="checkbox" id="use_machine_label" name="use_machine_label" value="1" class="h-4 w-4 rounded border-slate-200 text-indigo-600 focus:ring-indigo-500">
                <label for="use_machine_label" class="block text-sm font-medium text-slate-700">Use a machine</label>
            </div>

            <h2 class="mb-4 text-base font-semibold text-slate-900">Machines</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">ID</th>
                            <th class="px-4 py-3">Description</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($machines as $m)
                            <tr>
                                <td class="px-4 py-3">{{ $m->id }}</td>
                                <td class="px-4 py-3">{{ $m->description }}</td>
                                <td class="px-4 py-3">{{ $m->status }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-center text-slate-500">No machines configured.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
