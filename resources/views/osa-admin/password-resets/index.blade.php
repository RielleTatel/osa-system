<x-app-layout>
    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
        <x-hero-panel title="Password resets">
            Requests waiting for OSA Admin to set a new password.
        </x-hero-panel>

        <div class="bg-paper border border-slate-200 rounded-xl overflow-hidden shadow-sm">
            @forelse($requests as $passwordResetRequest)
                <div class="flex items-center justify-between p-4 border-b border-slate-200 last:border-b-0">
                    <div>
                        <p class="text-sm font-medium text-ink-900">{{ $passwordResetRequest->user->name }}</p>
                        <p class="text-sm text-slate-500">{{ $passwordResetRequest->user->email }} · <x-eyebrow-badge>{{ $passwordResetRequest->user->role->label() }}</x-eyebrow-badge></p>
                        <p class="text-xs text-slate-400 mt-0.5">Requested {{ $passwordResetRequest->requested_at->diffForHumans() }}</p>
                    </div>
                    <form method="POST" action="{{ route('osa-admin.password-resets.update', $passwordResetRequest) }}"
                          onsubmit="return confirm('Set a new temporary password for {{ $passwordResetRequest->user->email }}?')">
                        @csrf
                        <button class="bg-navy-900 text-white hover:bg-navy-800 rounded-lg px-4 py-2 text-sm font-medium">Set new password</button>
                    </form>
                </div>
            @empty
                <div class="p-12 text-center">
                    <h3 class="font-display text-lg font-extrabold uppercase tracking-wide text-ink-900">Nothing pending</h3>
                    <p class="mt-2 text-sm text-slate-500">No password reset requests waiting for you.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
