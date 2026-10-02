<x-app-layout>
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
        <x-hero-panel title="Activity tracker">Track activity progress and generate reports at any stage.</x-hero-panel>
        @if($errors->any())<div role="alert" class="text-red-700">{{ $errors->first() }}</div>@endif
        <form method="GET" action="{{ route('tracker.index') }}" class="bg-paper p-4 rounded-xl border border-slate-200 grid sm:grid-cols-3 gap-4">
            <label class="text-sm">From<input type="date" name="from" value="{{ request('from') }}" class="block w-full rounded-lg border-slate-200"></label>
            <label class="text-sm">To<input type="date" name="to" value="{{ request('to') }}" class="block w-full rounded-lg border-slate-200"></label>
            <label class="text-sm">Campus<select name="activity_type" class="block w-full rounded-lg border-slate-200"><option value="">All campuses</option>@foreach(['in_campus' => 'In-campus', 'off_campus' => 'Off-campus'] as $value => $label)<option value="{{ $value }}" @selected(request('activity_type') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label class="text-sm">Organization<select name="organization_id" class="block w-full rounded-lg border-slate-200"><option value="">All organizations</option>@foreach($organizations as $org)<option value="{{ $org->id }}" @selected((string) request('organization_id') === (string) $org->id)>{{ $org->name }}</option>@endforeach</select></label>
            <label class="text-sm">Approval<select name="approval" class="block w-full rounded-lg border-slate-200"><option value="">All approvals</option>@foreach(\App\Enums\ActivityStatus::cases() as $status)@if($status !== \App\Enums\ActivityStatus::Draft)<option value="{{ $status->value }}" @selected(request('approval') === $status->value)>{{ $status->trackerLabel() }}</option>@endif @endforeach</select></label>
            <label class="text-sm">Progress<select name="progress" class="block w-full rounded-lg border-slate-200"><option value="">All progress</option>@foreach(\App\Enums\ActivityProgress::cases() as $progress)<option value="{{ $progress->value }}" @selected(request('progress') === $progress->value)>{{ $progress->label() }}</option>@endforeach</select></label>
            <div class="flex gap-4 items-center"><button class="rounded-lg bg-navy-900 text-white px-4 py-2 text-sm">Apply filters</button><a href="{{ route('tracker.index') }}" class="text-sm underline">Reset</a></div>
        </form>
        <div class="flex items-center gap-4">
            <form method="POST" action="{{ route('tracker.reports.store') }}">
                @csrf
                @foreach(request()->only(['from', 'to', 'activity_type', 'organization_id', 'approval', 'progress']) as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
                <button class="bg-navy-900 text-white rounded-lg px-4 py-2 text-sm">Generate report</button>
            </form>
            <a href="{{ route('tracker.reports.index') }}" class="text-sm underline">Saved reports</a>
            <p class="text-sm text-slate-500">{{ $activities->total() }} activities match the applied filters.</p>
        </div>
        <div class="bg-paper border border-slate-200 rounded-xl overflow-x-auto shadow-sm">
            <table class="w-full text-sm text-left">
                <thead class="bg-paper-muted"><tr><th class="p-4">Date</th><th class="p-4">Organization / Activity</th><th class="p-4">Approval</th><th class="p-4">Progress</th></tr></thead>
                <tbody class="divide-y divide-slate-200">
                @forelse($activities as $activity)
                    <tr><td class="p-4">{{ $activity->date_start->format('M j, Y') }} – {{ $activity->date_end->format('M j, Y') }}</td>
                        <td class="p-4"><div>{{ $activity->organization->name }}</div><a href="{{ route('tracker.show', $activity) }}" class="font-semibold underline">{{ $activity->title }}</a></td>
                        <td class="p-4">{{ $activity->status->trackerLabel() }}</td>
                        <td class="p-4">{{ $activity->progress->label() }}</td></tr>
                @empty
                    <tr><td colspan="4" class="p-8 text-center">No matching activities.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $activities->links() }}
    </div>
</x-app-layout>
