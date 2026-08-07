<x-app-layout>
    @php $existing = $form?->complianceItems->values(); @endphp
    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
        <x-hero-panel title="OSA Form 3" :reference="$request->title">
            Local Off-Campus Activities — Report of Compliance (CHED)
        </x-hero-panel>

        @if($errors->any())
            <div class="flex items-start gap-2 rounded-lg border border-red-600/20 bg-red-50 px-4 py-3 text-sm text-red-700">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5 shrink-0 text-red-600" />
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('org.osa-form-3.store', $request) }}"
              class="bg-paper border border-slate-200 rounded-xl p-6 shadow-sm space-y-6">
            @csrf

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1" for="program_name">Program name</label>
                    <input id="program_name" name="program_name" value="{{ old('program_name', $form?->program_name) }}" required
                           class="w-full rounded-lg border-slate-200 focus:border-navy-700 focus:ring-navy-700">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" for="course">Course</label>
                    <input id="course" name="course" value="{{ old('course', $form?->course) }}" required
                           class="w-full rounded-lg border-slate-200 focus:border-navy-700 focus:ring-navy-700">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" for="destination_venue">Destination &amp; venue</label>
                    <input id="destination_venue" name="destination_venue" value="{{ old('destination_venue', $form?->destination_venue) }}" required
                           class="w-full rounded-lg border-slate-200 focus:border-navy-700 focus:ring-navy-700">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" for="inclusive_dates">Inclusive dates</label>
                    <input id="inclusive_dates" name="inclusive_dates" value="{{ old('inclusive_dates', $form?->inclusive_dates) }}" required
                           class="w-full rounded-lg border-slate-200 focus:border-navy-700 focus:ring-navy-700">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" for="number_of_students">Number of students</label>
                    <input type="number" min="1" id="number_of_students" name="number_of_students" value="{{ old('number_of_students', $form?->number_of_students) }}" required
                           class="w-full rounded-lg border-slate-200 focus:border-navy-700 focus:ring-navy-700">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" for="personnel_in_charge">Personnel-in-charge</label>
                    <input id="personnel_in_charge" name="personnel_in_charge" value="{{ old('personnel_in_charge', $form?->personnel_in_charge) }}" required
                           class="w-full rounded-lg border-slate-200 focus:border-navy-700 focus:ring-navy-700">
                </div>
            </div>

            <div class="border-t border-slate-200 pt-5">
                <h3 class="text-sm font-semibold text-ink-900 mb-3">Report before the activity</h3>
                <div class="space-y-3">
                    @foreach($labels as $i => $label)
                        @php
                            $prev = old("compliance.$i.compliance", $existing[$i]->compliance ?? '1');
                            $prevRemarks = old("compliance.$i.remarks", $existing[$i]->remarks ?? '');
                        @endphp
                        <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto_1fr] gap-3 items-start border-b border-slate-100 pb-3">
                            <p class="text-sm text-ink-900"><span class="text-slate-400 mr-1">{{ $i + 1 }}.</span>{{ $label }}</p>
                            <div class="flex items-center gap-3 text-sm">
                                <label class="inline-flex items-center gap-1">
                                    <input type="radio" name="compliance[{{ $i }}][compliance]" value="1" @checked($prev == '1') class="text-navy-700 focus:ring-navy-700"> Yes
                                </label>
                                <label class="inline-flex items-center gap-1">
                                    <input type="radio" name="compliance[{{ $i }}][compliance]" value="0" @checked($prev == '0') class="text-navy-700 focus:ring-navy-700"> No
                                </label>
                            </div>
                            <input name="compliance[{{ $i }}][remarks]" value="{{ $prevRemarks }}" placeholder="Remarks"
                                   class="w-full rounded-lg border-slate-200 focus:border-navy-700 focus:ring-navy-700 text-sm">
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center justify-between border-t border-slate-200 pt-5">
                <a href="{{ route('org.requests.show', $request) }}" class="text-sm text-slate-500 hover:text-ink-900">Cancel</a>
                <button class="bg-navy-900 text-white hover:bg-navy-800 rounded-lg px-5 py-2.5 text-sm font-medium">
                    Submit to moderator
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
