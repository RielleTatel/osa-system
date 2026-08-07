<x-app-layout>
    @php $status = $request->status->value; @endphp
    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
        <x-hero-panel :title="$request->title" :reference="$request->referenceSlip?->reference_code">
            {{ $request->organization->name }} · {{ $request->isOffCampus() ? 'Off-campus' : 'In-campus' }}
        </x-hero-panel>

        <div class="bg-paper border border-slate-200 rounded-xl p-5 shadow-sm flex items-center justify-between">
            <x-timeline :current="$status" />
            <x-status-pill :status="$status" />
        </div>

        <x-request-summary :request="$request" />

        {{-- OSA Form 3 summary --}}
        @if($request->osaForm3)
            <div class="bg-paper border border-slate-200 rounded-xl p-5 shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold text-ink-900">OSA Form 3</h3>
                    <x-status-pill :status="$request->osaForm3->moderator_approval_status" />
                </div>
                <p class="text-sm text-slate-500">
                    {{ $request->osaForm3->program_name }} · {{ $request->osaForm3->destination_venue }} · {{ $request->osaForm3->inclusive_dates }}
                    @if(Route::has('osa-form-3.pdf'))
                        · <a href="{{ route('osa-form-3.pdf', $request->osaForm3) }}" class="text-navy-700 underline">Download PDF</a>
                    @endif
                </p>
            </div>
        @endif

        {{-- Checklist verification --}}
        <div>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-ink-900">Document checklist</h3>
                <form method="POST" action="{{ route('osa-admin.requests.nudge', $request) }}">
                    @csrf
                    <button class="text-xs font-medium text-navy-700 hover:text-navy-900 underline">Remind org of missing docs</button>
                </form>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                @foreach($request->checklistItems as $item)
                    <x-file-card :label="$item->item_name" :physical="$item->is_physical" :status="$item->status->value">
                        @php $upload = $request->documentUploads->firstWhere('document_type', app(\App\Services\ChecklistService::class)->uploadTypesByItemName()[$item->item_name] ?? null); @endphp
                        @if($upload)
                            <a href="{{ \Illuminate\Support\Facades\Storage::url($upload->file_path) }}" target="_blank"
                               class="inline-flex items-center gap-1 text-xs font-medium text-navy-700 hover:text-navy-900 underline">
                                <x-heroicon-o-arrow-down-tray class="w-3.5 h-3.5" /> View file
                            </a>
                        @endif
                        <form method="POST" action="{{ route('osa-admin.checklist.update', $item) }}" class="mt-2 flex items-center gap-2">
                            @csrf @method('PATCH')
                            <select name="status" class="rounded-lg border-slate-200 text-xs py-1">
                                <option value="pending" @selected($item->status->value==='pending')>Pending</option>
                                <option value="submitted" @selected($item->status->value==='submitted')>Submitted</option>
                                <option value="verified" @selected($item->status->value==='verified')>Verified</option>
                            </select>
                            <button class="bg-navy-900 text-white hover:bg-navy-800 rounded-lg px-3 py-1 text-xs font-medium">Update</button>
                        </form>
                    </x-file-card>
                @endforeach
            </div>
        </div>

        {{-- Actions --}}
        <div class="bg-paper border border-slate-200 rounded-xl p-5 shadow-sm space-y-4">
            <h3 class="text-sm font-semibold text-ink-900">Actions</h3>
            <div class="flex flex-wrap gap-2">
                @if($status === 'moderator_endorsed')
                    <form method="POST" action="{{ route('osa-admin.requests.start-review', $request) }}">
                        @csrf
                        <button class="bg-navy-900 text-white hover:bg-navy-800 rounded-lg px-4 py-2 text-sm font-medium">Start review</button>
                    </form>
                @endif

                @if(Route::has('osa-admin.slip.generate') && in_array($status, ['docs_complete','awaiting_physical']))
                    <form method="POST" action="{{ route('osa-admin.slip.generate', $request) }}">
                        @csrf
                        <button class="bg-navy-900 text-white hover:bg-navy-800 rounded-lg px-4 py-2 text-sm font-medium">
                            {{ $request->referenceSlip ? 'Regenerate slip' : 'Generate reference slip' }}
                        </button>
                    </form>
                @endif

                @if(Route::has('osa-admin.slip.claim') && $request->referenceSlip && ! $request->referenceSlip->claimed_at)
                    <form method="POST" action="{{ route('osa-admin.slip.claim', $request->referenceSlip) }}">
                        @csrf
                        <button class="border border-slate-500 text-navy-900 hover:bg-paper-muted rounded-lg px-4 py-2 text-sm font-medium">Mark slip claimed</button>
                    </form>
                @endif

                @if(Route::has('osa-admin.requests.approve') && $status === 'awaiting_physical')
                    <form method="POST" action="{{ route('osa-admin.requests.approve', $request) }}" enctype="multipart/form-data" class="flex items-center gap-2">
                        @csrf
                        <input type="file" name="final_scan" class="text-xs">
                        <button class="bg-navy-900 text-white hover:bg-navy-800 rounded-lg px-4 py-2 text-sm font-medium">Mark approved</button>
                    </form>
                @endif
            </div>

            {{-- Request revisions / deny --}}
            @if(in_array($status, ['osa_reviewing','moderator_endorsed']))
                <form method="POST" action="{{ route('osa-admin.requests.decide', $request) }}" class="border-t border-slate-200 pt-4 space-y-2" x-data="{ decision: 'revision_requested' }">
                    @csrf
                    <div class="flex flex-wrap items-end gap-2">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Decision</label>
                            <select name="decision" x-model="decision" class="rounded-lg border-slate-200 text-sm">
                                <option value="revision_requested">Request revisions</option>
                                <option value="rejected">Deny</option>
                            </select>
                        </div>
                        <input name="remarks" placeholder="Remarks (required)" class="flex-1 min-w-[12rem] rounded-lg border-slate-200 text-sm">
                        <button class="border border-slate-500 text-navy-900 hover:bg-paper-muted rounded-lg px-4 py-2 text-sm font-medium">Submit</button>
                    </div>
                    @error('remarks')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                </form>
            @endif
        </div>

        {{-- Approval history --}}
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
