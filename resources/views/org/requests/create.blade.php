<x-app-layout>
    <div class="max-w-3xl mx-auto py-8 px-4 sm:px-6 lg:px-8"
         x-data="{
            type: '{{ old('activity_type', 'in_campus') }}',
            participants: {{ Illuminate\Support\Js::from(old('participants', [['full_name' => '', 'year_course' => '', 'contact_info' => '']])) }},
            schedule: {{ Illuminate\Support\Js::from(old('schedule_items', [['time_slot' => '', 'description' => '']])) }},
         }">
        <x-hero-panel title="New activity request">
            File your digital packet at least 3 days before the activity date.
        </x-hero-panel>

        @if($errors->any())
            <div class="mt-6 flex items-start gap-2 rounded-lg border border-red-600/20 bg-red-50 px-4 py-3 text-sm text-red-700">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5 shrink-0 text-red-600" />
                <div>
                    <p class="font-medium">Please fix the following:</p>
                    <ul class="mt-1 list-disc list-inside space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('org.requests.store') }}"
              class="bg-paper border border-slate-200 rounded-xl p-6 mt-6 space-y-6 shadow-sm">
            @csrf

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1" for="activity_type">Activity type</label>
                    <select id="activity_type" name="activity_type" x-model="type"
                            class="w-full rounded-lg border-slate-200 focus:border-navy-700 focus:ring-navy-700">
                        <option value="in_campus">In-campus</option>
                        <option value="off_campus">Off-campus</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" for="nature_of_engagement">Nature of engagement</label>
                    <select id="nature_of_engagement" name="nature_of_engagement" x-data="{ eng: '{{ old('nature_of_engagement', 'organizer') }}' }" x-model="eng"
                            class="w-full rounded-lg border-slate-200 focus:border-navy-700 focus:ring-navy-700">
                        <option value="organizer">Organizer</option>
                        <option value="partner">Partner</option>
                        <option value="participant">Participant</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1" for="title">Activity title</label>
                <input id="title" name="title" value="{{ old('title') }}" required
                       class="w-full rounded-lg border-slate-200 focus:border-navy-700 focus:ring-navy-700">
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1" for="nature_of_activity">Nature of activity</label>
                    <input id="nature_of_activity" name="nature_of_activity" value="{{ old('nature_of_activity') }}" required
                           placeholder="e.g. Seminar, Outreach, Sports"
                           class="w-full rounded-lg border-slate-200 focus:border-navy-700 focus:ring-navy-700">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" for="main_organizer">Main organizer <span class="text-gray-400">(if partner/participant)</span></label>
                    <input id="main_organizer" name="main_organizer" value="{{ old('main_organizer') }}"
                           class="w-full rounded-lg border-slate-200 focus:border-navy-700 focus:ring-navy-700">
                </div>
            </div>

            <div class="grid sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1" for="date_start">Start date</label>
                    <input type="date" id="date_start" name="date_start" value="{{ old('date_start') }}" required
                           class="w-full rounded-lg border-slate-200 focus:border-navy-700 focus:ring-navy-700">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" for="date_end">End date</label>
                    <input type="date" id="date_end" name="date_end" value="{{ old('date_end') }}" required
                           class="w-full rounded-lg border-slate-200 focus:border-navy-700 focus:ring-navy-700">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" for="time_of_activity">Time</label>
                    <input type="time" id="time_of_activity" name="time_of_activity" value="{{ old('time_of_activity') }}" required
                           class="w-full rounded-lg border-slate-200 focus:border-navy-700 focus:ring-navy-700">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1" for="venue">Venue</label>
                <input id="venue" name="venue" value="{{ old('venue') }}" required
                       class="w-full rounded-lg border-slate-200 focus:border-navy-700 focus:ring-navy-700">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1" for="purpose">Purpose</label>
                <textarea id="purpose" name="purpose" rows="3" required
                          class="w-full rounded-lg border-slate-200 focus:border-navy-700 focus:ring-navy-700">{{ old('purpose') }}</textarea>
            </div>

            {{-- Participants --}}
            <fieldset class="border-t border-slate-200 pt-5">
                <legend class="text-sm font-semibold text-ink-900">Participants</legend>
                <div class="space-y-2 mt-2">
                    <template x-for="(p, i) in participants" :key="i">
                        <div class="grid grid-cols-1 sm:grid-cols-[2fr_1.5fr_1.5fr_auto] gap-2 items-center">
                            <input :name="`participants[${i}][full_name]`" x-model="p.full_name" placeholder="Full name"
                                   class="rounded-lg border-slate-200 focus:border-navy-700 focus:ring-navy-700 text-sm">
                            <input :name="`participants[${i}][year_course]`" x-model="p.year_course" placeholder="Year & course"
                                   class="rounded-lg border-slate-200 focus:border-navy-700 focus:ring-navy-700 text-sm">
                            <input :name="`participants[${i}][contact_info]`" x-model="p.contact_info" placeholder="Contact"
                                   class="rounded-lg border-slate-200 focus:border-navy-700 focus:ring-navy-700 text-sm">
                            <button type="button" @click="participants.splice(i, 1)" x-show="participants.length > 1"
                                    class="text-slate-500 hover:text-red-600 p-2" title="Remove">
                                <x-heroicon-o-x-mark class="w-4 h-4" />
                            </button>
                        </div>
                    </template>
                </div>
                <button type="button" @click="participants.push({ full_name: '', year_course: '', contact_info: '' })"
                        class="mt-2 text-sm font-medium text-navy-700 hover:text-navy-900">+ Add participant</button>
            </fieldset>

            {{-- Schedule --}}
            <fieldset class="border-t border-slate-200 pt-5">
                <legend class="text-sm font-semibold text-ink-900">Schedule of activities</legend>
                <div class="space-y-2 mt-2">
                    <template x-for="(s, i) in schedule" :key="i">
                        <div class="grid grid-cols-1 sm:grid-cols-[1fr_2fr_auto] gap-2 items-center">
                            <input :name="`schedule_items[${i}][time_slot]`" x-model="s.time_slot" placeholder="e.g. 13:00-14:00"
                                   class="rounded-lg border-slate-200 focus:border-navy-700 focus:ring-navy-700 text-sm">
                            <input :name="`schedule_items[${i}][description]`" x-model="s.description" placeholder="Description"
                                   class="rounded-lg border-slate-200 focus:border-navy-700 focus:ring-navy-700 text-sm">
                            <button type="button" @click="schedule.splice(i, 1)" x-show="schedule.length > 1"
                                    class="text-slate-500 hover:text-red-600 p-2" title="Remove">
                                <x-heroicon-o-x-mark class="w-4 h-4" />
                            </button>
                        </div>
                    </template>
                </div>
                <button type="button" @click="schedule.push({ time_slot: '', description: '' })"
                        class="mt-2 text-sm font-medium text-navy-700 hover:text-navy-900">+ Add schedule item</button>
            </fieldset>

            <div class="flex items-center justify-between border-t border-slate-200 pt-5">
                <a href="{{ route('org.dashboard') }}" class="text-sm text-slate-500 hover:text-ink-900">Cancel</a>
                <button class="bg-navy-900 text-white hover:bg-navy-800 rounded-lg px-5 py-2.5 text-sm font-medium">
                    Submit request
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
