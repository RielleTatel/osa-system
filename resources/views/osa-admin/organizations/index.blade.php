<x-app-layout>
    <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
        <x-hero-panel title="Organizations">
            Manage accredited organizations and their moderators.
        </x-hero-panel>

        <form method="POST" action="{{ route('osa-admin.organizations.store') }}"
              class="bg-paper border border-slate-200 rounded-xl p-4 shadow-sm flex flex-wrap items-end gap-3">
            @csrf
            <div class="flex-1 min-w-[16rem]">
                <label class="block text-xs font-medium text-slate-500 mb-1">New organization</label>
                <input name="name" value="{{ old('name') }}" required placeholder="Organization name"
                       class="w-full rounded-lg border-slate-200 text-sm">
                @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
                <select name="accreditation_status" class="rounded-lg border-slate-200 text-sm">
                    <option value="accredited">Accredited</option>
                    <option value="suspended">Suspended</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <button class="bg-navy-900 text-white hover:bg-navy-800 rounded-lg px-4 py-2 text-sm font-medium">Add</button>
        </form>

        <div class="bg-paper border border-slate-200 rounded-xl overflow-hidden shadow-sm">
            <table class="w-full text-sm">
                <thead class="bg-paper-muted text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Organization</th>
                        <th class="px-4 py-3 font-semibold">Officers</th>
                        <th class="px-4 py-3 font-semibold">Moderators</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($organizations as $org)
                        <tr class="hover:bg-paper-muted/60">
                            <td class="px-4 py-3 font-medium text-ink-900">{{ $org->name }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $org->officers_count }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $org->moderators->pluck('name')->join(', ') ?: '—' }}</td>
                            <td class="px-4 py-3"><x-status-pill :status="$org->accreditation_status" /></td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('osa-admin.organizations.edit', $org) }}" class="text-navy-700 hover:text-navy-900 font-medium">Manage</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div>{{ $organizations->links() }}</div>
    </div>
</x-app-layout>
