<x-app-layout>
    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
        <x-hero-panel :title="$request->title" :reference="$request->referenceSlip?->reference_code" />
        <div class="bg-paper border border-slate-200 rounded-xl p-5">
            <x-timeline :current="$request->status->value" />
        </div>
    </div>
</x-app-layout>
