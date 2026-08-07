<x-app-layout>
    @php
        $showRoute = match(auth()->user()->role) {
            \App\Enums\Role::OrgOfficer => 'org.requests.show',
            \App\Enums\Role::Moderator => 'moderator.requests.show',
            \App\Enums\Role::OsaAdmin => 'osa-admin.requests.show',
            \App\Enums\Role::OsaDirector => 'osa-director.requests.show',
        };
    @endphp
    <div class="max-w-3xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
        <x-hero-panel title="Notifications" />

        <div class="bg-paper border border-slate-200 rounded-xl divide-y divide-slate-200 shadow-sm">
            @forelse($notifications as $notification)
                @php $data = $notification->data; @endphp
                <a href="{{ route($showRoute, $data['activity_request_id']) }}"
                   class="flex items-start gap-3 p-4 hover:bg-paper-muted/60 {{ $notification->read_at ? '' : 'bg-blue-50/40' }}">
                    <span class="mt-1 w-2 h-2 rounded-full shrink-0 {{ $notification->read_at ? 'bg-slate-200' : 'bg-gold-500' }}"></span>
                    <div>
                        <p class="text-sm font-medium text-ink-900">{{ $data['title'] ?? 'Activity request' }}</p>
                        <p class="text-sm text-slate-500">{{ $data['message'] ?? '' }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                </a>
            @empty
                <div class="p-12 text-center">
                    <h3 class="font-display text-lg font-extrabold uppercase tracking-wide text-ink-900">No notifications</h3>
                    <p class="mt-2 text-sm text-slate-500">You're all caught up.</p>
                </div>
            @endforelse
        </div>
        <div>{{ $notifications->links() }}</div>
    </div>
</x-app-layout>
