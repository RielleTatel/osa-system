<x-app-layout>
    @php
        $statuses = ['submitted','moderator_endorsed','osa_reviewing','docs_complete','awaiting_physical','approved','denied','revision_needed'];
    @endphp
    <div class="max-w-6xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
        <x-hero-panel title="Review queue">
            Verify completeness and move requests through the pipeline.
        </x-hero-panel>

        {{-- Filters --}}
        <form method="GET" action="{{ route('osa-admin.queue') }}"
              class="bg-paper border border-slate-200 rounded-xl p-4 shadow-sm grid sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
                <select name="status" class="w-full rounded-lg border-slate-200 text-sm">
                    <option value="">All</option>
                    @foreach($statuses as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Type</label>
                <select name="activity_type" class="w-full rounded-lg border-slate-200 text-sm">
                    <option value="">All</option>
                    <option value="in_campus" @selected(request('activity_type')==='in_campus')>In-campus</option>
                    <option value="off_campus" @selected(request('activity_type')==='off_campus')>Off-campus</option>
                </select>
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
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-lg border-slate-200 text-sm">
            </div>
            <div class="flex gap-2">
                <button class="bg-navy-900 text-white hover:bg-navy-800 rounded-lg px-4 py-2 text-sm font-medium">Filter</button>
                <a href="{{ route('osa-admin.queue') }}" class="border border-slate-500 text-navy-900 hover:bg-paper-muted rounded-lg px-4 py-2 text-sm font-medium">Reset</a>
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
                                <th class="px-4 py-3 font-semibold">Type</th>
                                <th class="px-4 py-3 font-semibold">Date</th>
                                <th class="px-4 py-3 font-semibold">Digital docs</th>
                                <th class="px-4 py-3 font-semibold">Status</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
            @endif
                            @php
                                $digital = $request->checklistItems->where('is_physical', false);
                                $done = $digital->whereIn('status', [\App\Enums\ChecklistStatus::Submitted, \App\Enums\ChecklistStatus::Verified])->count();
                                $late = $request->submitted_at && $request->submitted_at->gt($request->date_start->copy()->subDays(3));
                            @endphp
                            <tr class="hover:bg-paper-muted/60">
                                <td class="px-4 py-3 text-slate-500">{{ $request->organization->name }}</td>
                                <td class="px-4 py-3 font-medium text-ink-900">
                                    {{ $request->title }}
                                    @if($late)<span class="ml-1 text-[10px] font-semibold uppercase tracking-wide text-amber-700 bg-amber-50 rounded px-1.5 py-0.5">Filed late</span>@endif
                                </td>
                                <td class="px-4 py-3 text-slate-500">{{ $request->isOffCampus() ? 'Off-campus' : 'In-campus' }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $request->date_start->format('M j, Y') }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $done }}/{{ $digital->count() }}</td>
                                <td class="px-4 py-3"><x-status-pill :status="$request->status->value" /></td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('osa-admin.requests.show', $request) }}" class="text-navy-700 hover:text-navy-900 font-medium">Open</a>
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
                <h3 class="font-display text-lg font-extrabold uppercase tracking-wide text-ink-900">No requests</h3>
                <p class="mt-2 text-sm text-slate-500">Nothing matches the current filters.</p>
            </div>
        @endforelse
    </div>
</x-app-layout>
