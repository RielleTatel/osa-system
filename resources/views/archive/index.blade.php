<x-app-layout>
    @php
        $showRoute = auth()->user()->role === \App\Enums\Role::OsaDirector
            ? 'osa-director.requests.show'
            : 'osa-admin.requests.show';
    @endphp
    <div class="max-w-6xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
        <x-hero-panel title="Records archive">
            Searchable history of completed and denied requests.
        </x-hero-panel>

        <form method="GET" action="{{ route('archive.index') }}"
              class="bg-paper border border-slate-200 rounded-xl p-4 shadow-sm grid sm:grid-cols-4 gap-3 items-end">
            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-slate-500 mb-1">Search</label>
                <input name="q" value="{{ request('q') }}" placeholder="Title or venue" class="w-full rounded-lg border-slate-200 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Organization</label>
                <select name="organization_id" class="w-full rounded-lg border-slate-200 text-sm">
                    <option value="">All</option>
                    @foreach($organizations as $org)
                        <option value="{{ $org->id }}" @selected((int) request('organization_id') === $org->id)>{{ $org->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button class="bg-navy-900 text-white hover:bg-navy-800 rounded-lg px-4 py-2 text-sm font-medium">Search</button>
                <a href="{{ route('archive.index') }}" class="border border-slate-500 text-navy-900 hover:bg-paper-muted rounded-lg px-4 py-2 text-sm font-medium">Reset</a>
            </div>
        </form>

        @forelse($requests as $request)
            @if($loop->first)
                <div class="bg-paper border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                    <table class="w-full text-sm">
                        <thead class="bg-paper-muted text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Organization</th>
                                <th class="px-4 py-3 font-semibold">Activity</th>
                                <th class="px-4 py-3 font-semibold">Date</th>
                                <th class="px-4 py-3 font-semibold">Turnaround</th>
                                <th class="px-4 py-3 font-semibold">Outcome</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
            @endif
                            @php
                                $finalActedAt = $request->approvals->max('acted_at');
                                $turnaround = $request->submitted_at && $finalActedAt
                                    ? $request->submitted_at->diffInDays($finalActedAt).' days'
                                    : '—';
                            @endphp
                            <tr class="hover:bg-paper-muted/60">
                                <td class="px-4 py-3 text-slate-500">{{ $request->organization->name }}</td>
                                <td class="px-4 py-3 font-medium text-ink-900">{{ $request->title }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $request->date_start->format('M j, Y') }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $turnaround }}</td>
                                <td class="px-4 py-3"><x-status-pill :status="$request->status->value" /></td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route($showRoute, $request) }}" class="text-navy-700 hover:text-navy-900 font-medium">Open</a>
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
                <h3 class="font-display text-lg font-extrabold uppercase tracking-wide text-ink-900">No records found</h3>
                <p class="mt-2 text-sm text-slate-500">Approved and denied requests will be archived here.</p>
            </div>
        @endforelse
    </div>
</x-app-layout>
