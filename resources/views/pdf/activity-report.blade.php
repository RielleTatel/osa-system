<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>OSA Activity Report</title>
<style>
    @page { margin: 28px 24px 35px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #16233F; }
    h1 { font-size: 17px; margin: 0 0 6px; } h2 { font-size: 12px; }
    p { margin: 4px 0; } table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    thead { display: table-header-group; } tr { page-break-inside: avoid; }
    th, td { border: 1px solid #C9D6EA; padding: 5px; vertical-align: top; overflow-wrap: break-word; }
    th { background: #0B1930; color: white; text-align: left; } td { white-space: normal; }
    .campus { page-break-before: always; }
</style></head><body>
@foreach(['in_campus' => 'In-campus', 'off_campus' => 'Off-campus'] as $type => $title)
    <div @class(['campus' => ! $loop->first])>
        <h1>OSA Activity Report — {{ $title }}</h1>
        <p>Report {{ $report->id }} · Generated {{ $report->created_at->format('Y-m-d H:i:s T') }} by {{ $report->creator_name }}</p>
        <p>@foreach($report->filter_labels as $label => $value){{ $label }}: {{ $value }}{{ $loop->last ? '' : ' · ' }}@endforeach</p>
        <p>Expected participants: initial submitted list. Person in charge: submitting officer. Moderator: assigned moderator(s).</p>
        <p>Pending: not started · Ongoing: unfinished · Completed: finished · Cancelled · Needs confirmation: not yet classified · Not approved: denied request</p>
        <table>
            <thead><tr>@foreach(\App\Services\ActivityReportService::COLUMNS as $label)<th style="width: {{ [10, 9, 10, 14, 8, 9, 9, 8, 9, 14][$loop->index] }}%">{{ $label }}</th>@endforeach</tr></thead>
            <tbody>
            @forelse(collect($report->rows)->where('activity_type', $type) as $row)
                @php
                    // Collapse whitespace for print and split near word boundaries. Together with
                    // normal wrapping, this bounds row height even for newline-heavy remarks.
                    $chunks = collect(\App\Services\ActivityReportService::COLUMNS)->map(function ($label, $key) use ($row) {
                        $text = preg_replace('/\s+/u', ' ', (string) ($row[$key] ?? ''));
                        preg_match_all('/.{1,180}(?:\s+|$)|.{1,180}/us', $text, $parts);
                        return $parts[0];
                    });
                    $parts = max(1, $chunks->map(fn ($values) => count($values))->max());
                @endphp
                @for($part = 0; $part < $parts; $part++)
                    <tr>@foreach(\App\Services\ActivityReportService::COLUMNS as $key => $label)<td>{{ $chunks[$key][$part] ?? ($key === 'title' && $part > 0 ? 'Activity #'.$row['activity_id'].' (continued)' : '') }}</td>@endforeach</tr>
                @endfor
            @empty <tr><td colspan="10">No matching activities.</td></tr> @endforelse
            </tbody>
        </table>
    </div>
@endforeach
</body></html>
