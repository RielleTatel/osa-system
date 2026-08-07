<x-app-layout>
    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
        <x-hero-panel :title="$request->title">
            {{ $request->organization->name }} · {{ $request->isOffCampus() ? 'Off-campus' : 'In-campus' }}
        </x-hero-panel>

        <div class="bg-paper border border-slate-200 rounded-xl p-5 shadow-sm">
            <x-timeline :current="$request->status->value" />
        </div>

        <x-request-summary :request="$request" />

        {{-- Verified checklist snapshot --}}
        <div class="bg-paper border border-slate-200 rounded-xl p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-ink-900 mb-3">Checklist</h3>
            <ul class="space-y-1.5">
                @foreach($request->checklistItems as $item)
                    <li class="flex items-center justify-between text-sm">
                        <span class="text-ink-900">{{ $item->item_name }}</span>
                        <x-status-pill :status="$item->status->value" />
                    </li>
                @endforeach
            </ul>
        </div>

        <form method="POST" action="{{ route('osa-director.requests.decide', $request) }}"
              class="bg-paper border border-slate-200 rounded-xl p-5 shadow-sm space-y-3" x-data="{ decision: 'approved' }">
            @csrf
            <h3 class="text-sm font-semibold text-ink-900">Director notation</h3>
            <div>
                <label class="block text-sm font-medium mb-1">Decision</label>
                <select name="decision" x-model="decision" class="w-full sm:w-72 rounded-lg border-slate-200 text-sm">
                    <option value="approved">Note and release for physical signing</option>
                    <option value="revision_requested">Request revisions</option>
                    <option value="rejected">Deny</option>
                </select>
            </div>
            <div x-show="decision !== 'approved'">
                <label class="block text-sm font-medium mb-1">Remarks</label>
                <textarea name="remarks" rows="2" class="w-full rounded-lg border-slate-200 text-sm">{{ old('remarks') }}</textarea>
                @error('remarks')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-center justify-between pt-2">
                <a href="{{ route('osa-director.queue') }}" class="text-sm text-slate-500 hover:text-ink-900">Back to queue</a>
                <button class="bg-navy-900 text-white hover:bg-navy-800 rounded-lg px-5 py-2.5 text-sm font-medium">Submit notation</button>
            </div>
        </form>
    </div>
</x-app-layout>
