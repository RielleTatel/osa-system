<x-app-layout>
    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
        <x-hero-panel title="OSA Form 3" :reference="$form->activityRequest->title">
            {{ $form->activityRequest->organization->name }}
        </x-hero-panel>

        <div class="bg-paper border border-slate-200 rounded-xl p-5 shadow-sm">
            <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <div><dt class="text-slate-500">Program</dt><dd class="text-ink-900 font-medium">{{ $form->program_name }}</dd></div>
                <div><dt class="text-slate-500">Course</dt><dd class="text-ink-900 font-medium">{{ $form->course }}</dd></div>
                <div><dt class="text-slate-500">Destination &amp; venue</dt><dd class="text-ink-900 font-medium">{{ $form->destination_venue }}</dd></div>
                <div><dt class="text-slate-500">Inclusive dates</dt><dd class="text-ink-900 font-medium">{{ $form->inclusive_dates }}</dd></div>
                <div><dt class="text-slate-500">Number of students</dt><dd class="text-ink-900 font-medium">{{ $form->number_of_students }}</dd></div>
                <div><dt class="text-slate-500">Personnel-in-charge</dt><dd class="text-ink-900 font-medium">{{ $form->personnel_in_charge }}</dd></div>
            </dl>
        </div>

        <div class="bg-paper border border-slate-200 rounded-xl overflow-hidden shadow-sm">
            <table class="w-full text-sm">
                <thead class="bg-paper-muted text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-2 font-semibold">#</th><th class="px-4 py-2 font-semibold">Activity</th><th class="px-4 py-2 font-semibold">Compliance</th><th class="px-4 py-2 font-semibold">Remarks</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($form->complianceItems as $i => $item)
                        <tr>
                            <td class="px-4 py-2 text-slate-400">{{ $i + 1 }}</td>
                            <td class="px-4 py-2 text-ink-900">{{ $item->activity_label }}</td>
                            <td class="px-4 py-2">
                                <span class="text-[11px] font-medium px-2 py-0.5 rounded-full {{ $item->compliance ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                                    {{ $item->compliance ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-slate-500">{{ $item->remarks ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($form->moderator_approval_status === 'pending')
            <form method="POST" action="{{ route('moderator.osa-form-3.decide', $form) }}"
                  class="bg-paper border border-slate-200 rounded-xl p-5 shadow-sm flex items-center justify-between">
                @csrf
                <p class="text-sm text-slate-500">Recommend this compliance report?</p>
                <div class="flex gap-2">
                    <button name="decision" value="rejected"
                            class="bg-transparent border border-slate-500 text-navy-900 hover:bg-paper-muted rounded-lg px-4 py-2 text-sm font-medium">Reject</button>
                    <button name="decision" value="approved"
                            class="bg-navy-900 text-white hover:bg-navy-800 rounded-lg px-4 py-2 text-sm font-medium">Recommend approval</button>
                </div>
            </form>
        @else
            <div class="bg-paper border border-slate-200 rounded-xl p-5 shadow-sm text-sm">
                <x-status-pill :status="$form->moderator_approval_status" />
                <span class="ml-2 text-slate-500">by {{ $form->moderator?->name }} on {{ $form->moderator_approved_at?->format('M j, Y') }}</span>
            </div>
        @endif
    </div>
</x-app-layout>
