<x-app-layout>
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
        <a href="{{ route('tracker.reports.index') }}" class="text-sm underline">Saved reports</a>
        <x-hero-panel title="Activity report">{{ count($report->rows) }} activities · Generated {{ $report->created_at->format('Y-m-d H:i:s T') }} by {{ $report->creator_name }}</x-hero-panel>
        <div class="flex gap-3">@foreach(['xlsx' => 'Download Excel', 'pdf' => 'Download PDF'] as $format => $label)<a href="{{ route('tracker.reports.download', [$report, $format]) }}" class="bg-navy-900 text-white rounded-lg px-4 py-2 text-sm">{{ $label }}</a>@endforeach</div>
        <div class="bg-paper p-4 rounded-xl border border-slate-200 text-sm space-y-2">
            <p>Report {{ $report->id }}</p>
            <p>@foreach($report->filter_labels as $label => $value){{ $label }}: {{ $value }}{{ $loop->last ? '' : ' · ' }}@endforeach</p>
            <p>This saved report retains the data recorded when it was generated.</p>
        </div>
        @foreach(['in_campus' => 'In-campus', 'off_campus' => 'Off-campus'] as $type => $title)
            <section class="space-y-2"><h2 class="font-semibold">{{ $title }}</h2>
                <div class="overflow-x-auto bg-paper rounded-xl border border-slate-200">
                    <table class="w-full text-sm text-left"><thead class="bg-paper-muted"><tr>@foreach(\App\Services\ActivityReportService::COLUMNS as $label)<th class="p-3">{{ $label }}</th>@endforeach</tr></thead>
                        <tbody class="divide-y divide-slate-200">@forelse(collect($report->rows)->where('activity_type', $type) as $row)<tr>@foreach(\App\Services\ActivityReportService::COLUMNS as $key => $label)<td class="p-3 whitespace-pre-wrap">{{ $row[$key] }}</td>@endforeach</tr>@empty<tr><td colspan="10" class="p-6 text-center">No matching activities.</td></tr>@endforelse</tbody>
                    </table>
                </div>
            </section>
        @endforeach
    </div>
</x-app-layout>
