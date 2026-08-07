<x-app-layout>
    <div class="max-w-3xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
        <x-hero-panel :title="$organization->name">
            Organization settings and moderator assignments.
        </x-hero-panel>

        {{-- Details --}}
        <form method="POST" action="{{ route('osa-admin.organizations.update', $organization) }}"
              class="bg-paper border border-slate-200 rounded-xl p-5 shadow-sm space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="block text-sm font-medium mb-1">Name</label>
                <input name="name" value="{{ old('name', $organization->name) }}" required class="w-full rounded-lg border-slate-200 text-sm">
                @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Accreditation status</label>
                <select name="accreditation_status" class="w-full sm:w-64 rounded-lg border-slate-200 text-sm">
                    @foreach(['accredited','suspended','inactive'] as $s)
                        <option value="{{ $s }}" @selected($organization->accreditation_status === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-slate-500 mt-1">Only accredited organizations can file new requests.</p>
            </div>
            <button class="bg-navy-900 text-white hover:bg-navy-800 rounded-lg px-4 py-2 text-sm font-medium">Save changes</button>
        </form>

        {{-- Moderators --}}
        <div class="bg-paper border border-slate-200 rounded-xl p-5 shadow-sm space-y-4">
            <h3 class="text-sm font-semibold text-ink-900">Assigned moderators</h3>
            @forelse($organization->moderators as $moderator)
                <div class="flex items-center justify-between text-sm border-b border-slate-100 pb-2">
                    <span class="text-ink-900">{{ $moderator->name }} <span class="text-slate-400">· {{ $moderator->email }}</span></span>
                    <form method="POST" action="{{ route('osa-admin.moderators.destroy', [$organization, $moderator]) }}">
                        @csrf @method('DELETE')
                        <button class="text-xs text-red-600 hover:text-red-700">Remove</button>
                    </form>
                </div>
            @empty
                <p class="text-sm text-slate-500">No moderators assigned yet.</p>
            @endforelse

            <form method="POST" action="{{ route('osa-admin.moderators.store', $organization) }}" class="flex items-end gap-2 pt-2">
                @csrf
                <div class="flex-1">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Assign moderator</label>
                    <select name="user_id" required class="w-full rounded-lg border-slate-200 text-sm">
                        <option value="">Select a moderator…</option>
                        @foreach($moderators as $m)
                            <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->email }})</option>
                        @endforeach
                    </select>
                    @error('user_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <button class="bg-navy-900 text-white hover:bg-navy-800 rounded-lg px-4 py-2 text-sm font-medium">Assign</button>
            </form>
        </div>

        <a href="{{ route('osa-admin.organizations.index') }}" class="text-sm text-slate-500 hover:text-ink-900">← Back to organizations</a>
    </div>
</x-app-layout>
