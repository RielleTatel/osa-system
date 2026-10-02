<?php

namespace App\Http\Controllers;

use App\Http\Requests\TrackerFilterRequest;
use App\Models\ActivityReport;
use App\Services\ActivityReportService;
use Illuminate\Support\Facades\Storage;

class ActivityReportController extends Controller
{
    public function index()
    {
        return view('tracker.reports.index', ['reports' => ActivityReport::latest()->paginate(20)]);
    }

    public function store(TrackerFilterRequest $request, ActivityReportService $reports)
    {
        $report = $reports->generate($request->validated(), $request->user());

        return redirect()->route('tracker.reports.show', $report);
    }

    public function show(ActivityReport $activityReport)
    {
        return view('tracker.reports.show', ['report' => $activityReport]);
    }

    public function download(ActivityReport $activityReport, string $format)
    {
        abort_unless(in_array($format, ['xlsx', 'pdf'], true), 404);
        abort_unless(Storage::disk('local')->exists($activityReport->filePath($format)), 404);

        return Storage::disk('local')->download($activityReport->filePath($format), "osa-activity-report-{$activityReport->id}.{$format}", [
            'Content-Type' => $format === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
