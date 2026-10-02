<?php

namespace App\Services;

use App\Enums\ActivityProgress;
use App\Enums\ActivityStatus;
use App\Models\ActivityReport;
use App\Models\ActivityRequest;
use App\Models\Organization;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ActivityReportService
{
    public const COLUMNS = [
        'date' => 'Date', 'venue' => 'Venue', 'organization' => 'Organization',
        'title' => 'Activity', 'expected_participants' => 'Expected participants',
        'person_in_charge' => 'Person in charge', 'moderator' => 'Moderator',
        'approval' => 'Approval', 'progress' => 'Progress', 'remarks' => 'Remarks',
    ];

    public function __construct(private ActivityTracker $tracker, private ActivityReportExcel $excel) {}

    public function generate(array $filters, User $actor): ActivityReport
    {
        $rows = $this->tracker->query($filters)->get()->map(fn (ActivityRequest $activity) => [
            'activity_id' => $activity->id,
            'activity_type' => $activity->activity_type,
            'date' => $activity->date_start->format('Y-m-d').($activity->date_end->equalTo($activity->date_start) ? '' : ' – '.$activity->date_end->format('Y-m-d')),
            'venue' => $activity->venue, 'organization' => $activity->organization->name,
            'title' => $activity->title, 'expected_participants' => $activity->expected_participants,
            'person_in_charge' => $activity->submitter->name,
            'moderator' => $activity->organization->moderators->sortBy('name')->pluck('name')->implode(', '),
            'approval' => $activity->status->trackerLabel(),
            'progress' => $activity->progress->label(), 'remarks' => $activity->tracker_remarks,
        ])->all();

        $report = new ActivityReport([
            'id' => (string) Str::uuid(),
            'created_by' => $actor->id, 'creator_name' => $actor->name,
            'filters' => $filters, 'filter_labels' => $this->filterLabels($filters), 'rows' => $rows,
            'created_at' => now(),
        ]);
        $pdf = Pdf::loadView('pdf.activity-report', ['report' => $report])->setPaper('a4', 'landscape');
        $pdf->render();
        $pdf->getDomPDF()->getCanvas()->page_text(740, 572, 'Page {PAGE_NUM} / {PAGE_COUNT}', null, 8);
        $files = ['xlsx' => $this->excel->render($report), 'pdf' => $pdf->output()];
        try {
            foreach ($files as $format => $bytes) {
                if (! Storage::disk('local')->put($report->filePath($format), $bytes)) {
                    throw new RuntimeException('Could not save the activity report. Please try again.');
                }
            }
            DB::transaction(fn () => $report->save());
        } catch (Throwable $error) {
            Storage::disk('local')->deleteDirectory('activity-reports/'.$report->id);
            throw $error;
        }

        return $report;
    }

    private function filterLabels(array $filters): array
    {
        return [
            'From' => $filters['from'] ?? 'Any date', 'To' => $filters['to'] ?? 'Any date',
            'Campus' => empty($filters['activity_type']) ? 'All' : ($filters['activity_type'] === 'in_campus' ? 'In-campus' : 'Off-campus'),
            'Organization' => empty($filters['organization_id']) ? 'All' : Organization::findOrFail($filters['organization_id'])->name,
            'Approval' => empty($filters['approval']) ? 'All' : ActivityStatus::from($filters['approval'])->trackerLabel(),
            'Progress' => empty($filters['progress']) ? 'All' : ActivityProgress::from($filters['progress'])->label(),
        ];
    }
}
