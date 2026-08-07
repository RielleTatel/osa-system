<x-app-layout>
    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
        <x-hero-panel :title="$request->title" :reference="$request->referenceSlip?->reference_code">
            {{ $request->isOffCampus() ? 'Off-campus' : 'In-campus' }} · {{ $request->date_start->format('M j, Y') }}
            @if($request->referenceSlip && Route::has('slip.pdf'))
                · <a href="{{ route('slip.pdf', $request) }}" class="underline text-gold-500">Download reference slip</a>
            @endif
        </x-hero-panel>

        {{-- Progress timeline --}}
        <div class="bg-paper border border-slate-200 rounded-xl p-5 shadow-sm">
            <x-timeline :current="$request->status->value" />
            @if($request->status->value === 'revision_needed')
                @php $lastRevision = $request->approvals->where('decision', \App\Enums\Decision::RevisionRequested)->last(); @endphp
                @if($lastRevision?->remarks)
                    <p class="mt-3 text-sm text-red-700">Revision requested: {{ $lastRevision->remarks }}</p>
                @endif
            @endif
        </div>

        {{-- Document checklist --}}
        <div>
            <h2 class="text-lg font-semibold text-ink-900 mb-3">Document checklist</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                @foreach($request->checklistItems as $item)
                    <x-file-card :label="$item->item_name" :physical="$item->is_physical" :status="$item->status->value">
                        @if($item->is_physical)
                            <p class="text-xs text-amber-700">Submit physically at the OSA office.</p>
                        @elseif($item->item_name === \App\Services\ChecklistService::ITEM_OSA_FORM_3)
                            @if($request->isOffCampus() && Route::has('org.osa-form-3.create'))
                                <a href="{{ route('org.osa-form-3.create', $request) }}"
                                   class="inline-block text-xs font-medium text-navy-700 hover:text-navy-900 underline">
                                    {{ $request->osaForm3 ? 'Edit OSA Form 3' : 'Fill out OSA Form 3' }}
                                </a>
                            @endif
                        @elseif(isset($uploadTypes[$item->item_name]))
                            @php $upload = $request->documentUploads->firstWhere('document_type', $uploadTypes[$item->item_name]); @endphp
                            @if($upload)
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($upload->file_path) }}" target="_blank"
                                   class="inline-flex items-center gap-1 text-xs font-medium text-navy-700 hover:text-navy-900 underline">
                                    <x-heroicon-o-arrow-down-tray class="w-3.5 h-3.5" /> View file
                                </a>
                            @elseif($item->status->value === 'pending' && Route::has('org.documents.store'))
                                <form method="POST" action="{{ route('org.documents.store', $request) }}" enctype="multipart/form-data" class="flex flex-wrap gap-2 items-center">
                                    @csrf
                                    <input type="hidden" name="document_type" value="{{ $uploadTypes[$item->item_name]->value }}">
                                    <input type="file" name="file" required class="text-xs max-w-[10rem]">
                                    <button class="bg-navy-900 text-white hover:bg-navy-800 rounded-lg px-3 py-1.5 text-xs font-medium">Upload</button>
                                </form>
                            @endif
                        @endif
                    </x-file-card>
                @endforeach
            </div>
        </div>

        {{-- Participants + Schedule --}}
        <div class="grid md:grid-cols-2 gap-6">
            <div class="bg-paper border border-slate-200 rounded-xl p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-ink-900 mb-3">Participants</h3>
                @forelse($request->participants as $p)
                    <div class="text-sm text-ink-900 py-1 border-b border-slate-100 last:border-0">
                        {{ $p->full_name }}
                        @if($p->year_course)<span class="text-slate-500">· {{ $p->year_course }}</span>@endif
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No participants listed.</p>
                @endforelse
            </div>
            <div class="bg-paper border border-slate-200 rounded-xl p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-ink-900 mb-3">Schedule</h3>
                @forelse($request->scheduleItems as $s)
                    <div class="text-sm py-1 border-b border-slate-100 last:border-0">
                        <span class="font-medium text-ink-900">{{ $s->time_slot }}</span>
                        <span class="text-slate-500">— {{ $s->description }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No schedule items.</p>
                @endforelse
            </div>
        </div>

        {{-- Approvals log --}}
        @if($request->approvals->isNotEmpty())
            <div class="bg-paper border border-slate-200 rounded-xl p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-ink-900 mb-3">Approval history</h3>
                <ul class="space-y-3">
                    @foreach($request->approvals->sortBy('acted_at') as $approval)
                        <li class="flex items-start gap-3 text-sm">
                            <x-status-pill :status="$approval->decision->value" />
                            <div>
                                <p class="text-ink-900">{{ $approval->stage->label() }} — {{ $approval->actor?->name }}</p>
                                @if($approval->remarks)<p class="text-slate-500">{{ $approval->remarks }}</p>@endif
                                <p class="text-xs text-slate-400">{{ $approval->acted_at?->format('M j, Y g:i A') }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</x-app-layout>
