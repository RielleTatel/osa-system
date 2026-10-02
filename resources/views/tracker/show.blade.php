<x-app-layout>
    <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
        <a href="{{ route('tracker.index') }}" class="text-sm underline">Back to tracker</a>
        <x-hero-panel :title="$activity->title">{{ $activity->organization->name }}</x-hero-panel>
        @if(session('status'))<p role="status" class="text-green-700">{{ session('status') }}</p>@endif
        @if($errors->any())<p role="alert" class="text-red-700">{{ $errors->first() }}</p>@endif
        <dl class="bg-paper p-6 border border-slate-200 rounded-xl grid sm:grid-cols-2 gap-4 text-sm">
            <div><dt class="font-semibold">Scheduled dates</dt><dd>{{ $activity->date_start->format('M j, Y') }} – {{ $activity->date_end->format('M j, Y') }}</dd></div>
            <div><dt class="font-semibold">Venue</dt><dd>{{ $activity->venue }}</dd></div>
            <div><dt class="font-semibold">Approval</dt><dd>{{ $activity->status->trackerLabel() }}</dd></div>
            <div><dt class="font-semibold">Activity progress</dt><dd>{{ $activity->progress->label() }}</dd></div>
            <div><dt class="font-semibold">Expected participants</dt><dd>{{ $activity->expected_participants ?? 'Not provided' }}</dd></div>
            <div><dt class="font-semibold">Submitted by / Person in charge</dt><dd>{{ $activity->submitter->name }}</dd></div>
            <div class="sm:col-span-2"><dt class="font-semibold">Remarks</dt><dd class="whitespace-pre-wrap break-words">{{ $activity->tracker_remarks ?: 'No remarks.' }}</dd></div>
        </dl>
        @if(auth()->user()->role === \App\Enums\Role::OsaAdmin)
            <form method="POST" action="{{ route('tracker.update', $activity) }}" class="bg-paper p-6 rounded-xl border border-slate-200 space-y-4">
                @csrf @method('PATCH')
                <p class="text-sm text-slate-500">Pending: not started. Ongoing: started but unfinished. Completed: the activity finished. Dates do not change progress automatically.</p>
                <label class="block text-sm">Activity progress<select name="progress" class="block mt-1 rounded-lg border-slate-200">@foreach(\App\Enums\ActivityProgress::cases() as $progress)<option value="{{ $progress->value }}" @selected(old('progress', $activity->progress->value) === $progress->value)>{{ $progress->label() }}</option>@endforeach</select></label>
                <label class="block text-sm">Remarks<textarea name="remarks" rows="3" maxlength="2000" class="block mt-1 w-full rounded-lg border-slate-200">{{ old('remarks', $activity->tracker_remarks) }}</textarea></label>
                <button class="bg-navy-900 text-white rounded-lg px-4 py-2 text-sm">Save progress</button>
            </form>
        @endif
        <section class="bg-paper p-6 rounded-xl border border-slate-200 space-y-4">
            <h2 class="font-semibold">Tracking history</h2>
            @forelse($activity->progressUpdates as $update)
                <div class="border-t border-slate-200 pt-3 text-sm">
                    <p class="font-semibold">{{ $update->old_progress->label() }} → {{ $update->new_progress->label() }}</p>
                    <p>{{ $update->actor_name }} · {{ $update->created_at->format('Y-m-d H:i:s T') }}</p>
                    <p class="whitespace-pre-wrap break-words">Previous remarks: {{ $update->old_remarks ?: 'None' }}</p>
                    <p class="whitespace-pre-wrap break-words">Updated remarks: {{ $update->new_remarks ?: 'None' }}</p>
                </div>
            @empty <p class="text-sm text-slate-500">No tracking changes recorded.</p> @endforelse
        </section>
    </div>
</x-app-layout>
