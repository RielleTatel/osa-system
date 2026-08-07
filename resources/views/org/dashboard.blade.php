<x-app-layout>
    <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
        <x-hero-panel :title="auth()->user()->organization?->name ?? 'Organization'">
            <div class="flex items-center gap-3">
                <x-eyebrow-badge>AY {{ now()->year }}–{{ now()->year + 1 }}</x-eyebrow-badge>
                <span>Activity request tracker</span>
            </div>
        </x-hero-panel>

        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-ink-900">Your requests</h2>
            <a href="{{ route('org.requests.create') }}"
               class="bg-navy-900 text-white hover:bg-navy-800 rounded-lg px-4 py-2 text-sm font-medium">
                New request
            </a>
        </div>

        @forelse($requests as $request)
            @if($loop->first)
                <div class="bg-paper border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                    <table class="w-full text-sm">
                        <thead class="bg-paper-muted text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Activity</th>
                                <th class="px-4 py-3 font-semibold">Type</th>
                                <th class="px-4 py-3 font-semibold">Date</th>
                                <th class="px-4 py-3 font-semibold">Status</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
            @endif
                            <tr class="hover:bg-paper-muted/60">
                                <td class="px-4 py-3 font-medium text-ink-900">{{ $request->title }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $request->isOffCampus() ? 'Off-campus' : 'In-campus' }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $request->date_start->format('M j, Y') }}</td>
                                <td class="px-4 py-3"><x-status-pill :status="$request->status->value" /></td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('org.requests.show', $request) }}" class="text-navy-700 hover:text-navy-900 font-medium">View</a>
                                </td>
                            </tr>
            @if($loop->last)
                        </tbody>
                    </table>
                </div>
                <div>{{ $requests->links() }}</div>
            @endif
        @empty
            <div class="bg-paper border border-dashed border-slate-200 rounded-xl p-12 text-center">
                <h3 class="font-display text-lg font-extrabold uppercase tracking-wide text-ink-900">No requests yet</h3>
                <p class="mt-2 text-sm text-slate-500">File your first activity request to start tracking your documents.</p>
                <a href="{{ route('org.requests.create') }}"
                   class="inline-block mt-4 bg-navy-900 text-white hover:bg-navy-800 rounded-lg px-4 py-2 text-sm font-medium">
                    New request
                </a>
            </div>
        @endforelse
    </div>
</x-app-layout>
