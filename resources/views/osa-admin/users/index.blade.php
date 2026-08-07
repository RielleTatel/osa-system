<x-app-layout>
    <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
        <x-hero-panel title="Accounts">
            Provisioned accounts across all roles.
        </x-hero-panel>

        <div class="flex justify-end">
            <a href="{{ route('osa-admin.users.create') }}"
               class="bg-navy-900 text-white hover:bg-navy-800 rounded-lg px-4 py-2 text-sm font-medium">New account</a>
        </div>

        <div class="bg-paper border border-slate-200 rounded-xl overflow-hidden shadow-sm">
            <table class="w-full text-sm">
                <thead class="bg-paper-muted text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Name</th>
                        <th class="px-4 py-3 font-semibold">Email</th>
                        <th class="px-4 py-3 font-semibold">Role</th>
                        <th class="px-4 py-3 font-semibold">Organization</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($users as $user)
                        <tr class="hover:bg-paper-muted/60">
                            <td class="px-4 py-3 font-medium text-ink-900">{{ $user->name }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $user->email }}</td>
                            <td class="px-4 py-3"><x-eyebrow-badge>{{ $user->role->label() }}</x-eyebrow-badge></td>
                            <td class="px-4 py-3 text-slate-500">{{ $user->organization?->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div>{{ $users->links() }}</div>
    </div>
</x-app-layout>
