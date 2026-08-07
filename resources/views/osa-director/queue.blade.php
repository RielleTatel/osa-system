<x-app-layout>
    <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
        <x-hero-panel title="Notation queue">
            Digitally complete packets awaiting your notation before physical signing.
        </x-hero-panel>

        @forelse($requests as $request)
            @if($loop->first)
                <div class="bg-paper border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                    <table class="w-full text-sm">
                        <thead class="bg-paper-muted text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Organization</th>
                                <th class="px-4 py-3 font-semibold">Activity</th>
                                <th class="px-4 py-3 font-semibold">Type</th>
                                <th class="px-4 py-3 font-semibold">Date</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
            @endif
                            <tr class="hover:bg-paper-muted/60">
                                <td class="px-4 py-3 text-slate-500">{{ $request->organization->name }}</td>
                                <td class="px-4 py-3 font-medium text-ink-900">{{ $request->title }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $request->isOffCampus() ? 'Off-campus' : 'In-campus' }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $request->date_start->format('M j, Y') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('osa-director.requests.show', $request) }}" class="text-navy-700 hover:text-navy-900 font-medium">Review</a>
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
                <h3 class="font-display text-lg font-extrabold uppercase tracking-wide text-ink-900">Nothing awaiting notation</h3>
                <p class="mt-2 text-sm text-slate-500">Completed packets will appear here for your final digital notation.</p>
            </div>
        @endforelse
    </div>
</x-app-layout>
