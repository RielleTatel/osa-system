@props(['request'])
{{-- Read-only packet summary reused by moderator, OSA admin, and director views. --}}
<div class="space-y-6">
    <div class="bg-paper border border-slate-200 rounded-xl p-5 shadow-sm">
        <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
            <div><dt class="text-slate-500">Organization</dt><dd class="text-ink-900 font-medium">{{ $request->organization->name }}</dd></div>
            <div><dt class="text-slate-500">Type</dt><dd class="text-ink-900 font-medium">{{ $request->isOffCampus() ? 'Off-campus' : 'In-campus' }}</dd></div>
            <div><dt class="text-slate-500">Nature of activity</dt><dd class="text-ink-900 font-medium">{{ $request->nature_of_activity }}</dd></div>
            <div><dt class="text-slate-500">Engagement</dt><dd class="text-ink-900 font-medium">{{ ucfirst($request->nature_of_engagement) }}@if($request->main_organizer) · {{ $request->main_organizer }}@endif</dd></div>
            <div><dt class="text-slate-500">Dates</dt><dd class="text-ink-900 font-medium">{{ $request->date_start->format('M j, Y') }}@if($request->date_end->ne($request->date_start)) – {{ $request->date_end->format('M j, Y') }}@endif</dd></div>
            <div><dt class="text-slate-500">Time</dt><dd class="text-ink-900 font-medium">{{ \Illuminate\Support\Carbon::parse($request->time_of_activity)->format('g:i A') }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-slate-500">Venue</dt><dd class="text-ink-900 font-medium">{{ $request->venue }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-slate-500">Purpose</dt><dd class="text-ink-900">{{ $request->purpose }}</dd></div>
        </dl>
    </div>

    <div class="grid md:grid-cols-2 gap-6">
        <div class="bg-paper border border-slate-200 rounded-xl p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-ink-900 mb-3">Participants ({{ $request->participants->count() }})</h3>
            @forelse($request->participants as $p)
                <div class="text-sm py-1 border-b border-slate-100 last:border-0">
                    {{ $p->full_name }}@if($p->year_course)<span class="text-slate-500"> · {{ $p->year_course }}</span>@endif
                </div>
            @empty
                <p class="text-sm text-slate-500">No participants listed.</p>
            @endforelse
        </div>
        <div class="bg-paper border border-slate-200 rounded-xl p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-ink-900 mb-3">Schedule</h3>
            @forelse($request->scheduleItems as $s)
                <div class="text-sm py-1 border-b border-slate-100 last:border-0">
                    <span class="font-medium text-ink-900">{{ $s->time_slot }}</span> <span class="text-slate-500">— {{ $s->description }}</span>
                </div>
            @empty
                <p class="text-sm text-slate-500">No schedule items.</p>
            @endforelse
        </div>
    </div>
</div>
