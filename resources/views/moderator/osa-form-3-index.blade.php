<x-app-layout>
    <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
        <x-hero-panel title="OSA Form 3 approvals">
            Compliance reports awaiting your recommending approval.
        </x-hero-panel>

        @forelse($forms as $form)
            @if($loop->first)
                <div class="bg-paper border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                    <table class="w-full text-sm">
                        <thead class="bg-paper-muted text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Organization</th>
                                <th class="px-4 py-3 font-semibold">Program</th>
                                <th class="px-4 py-3 font-semibold">Dates</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
            @endif
                            <tr class="hover:bg-paper-muted/60">
                                <td class="px-4 py-3 text-slate-500">{{ $form->activityRequest->organization->name }}</td>
                                <td class="px-4 py-3 font-medium text-ink-900">{{ $form->program_name }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $form->inclusive_dates }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('moderator.osa-form-3.show', $form) }}" class="text-navy-700 hover:text-navy-900 font-medium">Review</a>
                                </td>
                            </tr>
            @if($loop->last)
                        </tbody>
                    </table>
                </div>
                <div>{{ $forms->links() }}</div>
            @endif
        @empty
            <div class="bg-paper border border-dashed border-slate-200 rounded-xl p-12 text-center">
                <h3 class="font-display text-lg font-extrabold uppercase tracking-wide text-ink-900">Nothing to approve</h3>
                <p class="mt-2 text-sm text-slate-500">No OSA Form 3 submissions are pending your approval.</p>
            </div>
        @endforelse
    </div>
</x-app-layout>
