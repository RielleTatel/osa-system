<x-app-layout>
    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
        <x-hero-panel :title="$request->title">
            {{ $request->organization->name }} · {{ $request->isOffCampus() ? 'Off-campus' : 'In-campus' }}
        </x-hero-panel>

        <x-request-summary :request="$request" />

        {{-- Digital documents --}}
        <div class="bg-paper border border-slate-200 rounded-xl p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-ink-900 mb-3">Submitted documents</h3>
            <div class="flex flex-wrap gap-2">
                @forelse($request->documentUploads as $upload)
                    <a href="{{ \Illuminate\Support\Facades\Storage::url($upload->file_path) }}" target="_blank"
                       class="inline-flex items-center gap-1 text-xs font-medium text-navy-700 hover:text-navy-900 underline">
                        <x-heroicon-o-paper-clip class="w-3.5 h-3.5" /> {{ str_replace('_', ' ', $upload->document_type->value) }}
                    </a>
                @empty
                    <p class="text-sm text-slate-500">No digital documents uploaded yet.</p>
                @endforelse
            </div>
        </div>

        {{-- Decision --}}
        <form method="POST" action="{{ route('moderator.requests.decide', $request) }}"
              class="bg-paper border border-slate-200 rounded-xl p-5 shadow-sm space-y-3"
              x-data="{ decision: 'approved' }">
            @csrf
            <h3 class="text-sm font-semibold text-ink-900">Your decision</h3>
            <div>
                <label class="block text-sm font-medium mb-1" for="decision">Decision</label>
                <select id="decision" name="decision" x-model="decision"
                        class="w-full sm:w-64 rounded-lg border-slate-200 focus:border-navy-700 focus:ring-navy-700">
                    <option value="approved">Endorse</option>
                    <option value="revision_requested">Request revisions</option>
                    <option value="rejected">Reject</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1" for="remarks">Remarks <span class="text-gray-400" x-show="decision !== 'approved'">(required)</span></label>
                <textarea id="remarks" name="remarks" rows="2" class="w-full rounded-lg border-slate-200 focus:border-navy-700 focus:ring-navy-700">{{ old('remarks') }}</textarea>
                @error('remarks')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-center justify-between pt-2">
                <a href="{{ route('moderator.queue') }}" class="text-sm text-slate-500 hover:text-ink-900">Back to queue</a>
                <button class="bg-navy-900 text-white hover:bg-navy-800 rounded-lg px-5 py-2.5 text-sm font-medium">Submit decision</button>
            </div>
        </form>
    </div>
</x-app-layout>
