<x-app-layout>
    <div class="max-w-xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6" x-data="{ role: '{{ old('role', 'org_officer') }}' }">
        <x-hero-panel title="New account">
            A temporary password is generated and shown once after creation.
        </x-hero-panel>

        @if($errors->any())
            <div class="flex items-start gap-2 rounded-lg border border-red-600/20 bg-red-50 px-4 py-3 text-sm text-red-700">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5 shrink-0 text-red-600" />
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('osa-admin.users.store') }}"
              class="bg-paper border border-slate-200 rounded-xl p-6 shadow-sm space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium mb-1">Name</label>
                <input name="name" value="{{ old('name') }}" required class="w-full rounded-lg border-slate-200 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-lg border-slate-200 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Role</label>
                <select name="role" x-model="role" class="w-full rounded-lg border-slate-200 text-sm">
                    <option value="org_officer">Organization Officer</option>
                    <option value="moderator">Moderator</option>
                    <option value="osa_admin">OSA Admin</option>
                    <option value="osa_director">OSA Director</option>
                </select>
            </div>
            <div x-show="role === 'org_officer'">
                <label class="block text-sm font-medium mb-1">Organization</label>
                <select name="organization_id" class="w-full rounded-lg border-slate-200 text-sm">
                    <option value="">Select an organization…</option>
                    @foreach($organizations as $org)
                        <option value="{{ $org->id }}" @selected((int) old('organization_id') === $org->id)>{{ $org->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center justify-between pt-2">
                <a href="{{ route('osa-admin.users.index') }}" class="text-sm text-slate-500 hover:text-ink-900">Cancel</a>
                <button class="bg-navy-900 text-white hover:bg-navy-800 rounded-lg px-5 py-2.5 text-sm font-medium">Create account</button>
            </div>
        </form>
    </div>
</x-app-layout>
