<x-app-layout>
    <div class="max-w-6xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
        <a href="{{ route('tracker.index') }}" class="text-sm underline">Back to tracker</a>
        <x-hero-panel title="Saved reports">Download reports as they were generated.</x-hero-panel>
        <div class="bg-paper p-6 rounded-xl border border-slate-200 space-y-4">
            @forelse($reports as $report)
                <div class="border-b border-slate-200 pb-4 text-sm">
                    <a href="{{ route('tracker.reports.show', $report) }}" class="font-semibold underline">{{ $report->created_at->format('Y-m-d H:i:s T') }} — {{ $report->creator_name }}</a>
                    <p>{{ count($report->rows) }} activities · {{ $report->id }}</p>
                    <p>@foreach($report->filter_labels as $label => $value){{ $label }}: {{ $value }}{{ $loop->last ? '' : ' · ' }}@endforeach</p>
                </div>
            @empty <p>No reports generated yet.</p> @endforelse
        </div>
        {{ $reports->links() }}
    </div>
</x-app-layout>
