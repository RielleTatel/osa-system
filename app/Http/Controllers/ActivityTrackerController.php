<?php

namespace App\Http\Controllers;

use App\Enums\ActivityProgress;
use App\Enums\ActivityStatus;
use App\Http\Requests\TrackerFilterRequest;
use App\Models\ActivityRequest;
use App\Models\Organization;
use App\Services\ActivityTracker;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActivityTrackerController extends Controller
{
    public function show(ActivityRequest $activityRequest)
    {
        abort_if($activityRequest->status === ActivityStatus::Draft || ! $activityRequest->submitted_at, 404);

        return view('tracker.show', ['activity' => $activityRequest->load(['organization', 'submitter', 'progressUpdates'])]);
    }

    public function update(Request $request, ActivityRequest $activityRequest, ActivityTracker $tracker)
    {
        abort_if($activityRequest->status === ActivityStatus::Draft || ! $activityRequest->submitted_at, 404);
        $values = $request->validate([
            'progress' => ['required', Rule::enum(ActivityProgress::class)],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);
        $tracker->update($activityRequest, $values, $request->user());

        return redirect()->route('tracker.show', $activityRequest)->with('status', 'Activity tracking updated.');
    }

    public function index(TrackerFilterRequest $request, ActivityTracker $tracker)
    {
        return view('tracker.index', [
            'activities' => $tracker->query($request->validated())->paginate(20)->withQueryString(),
            'organizations' => Organization::orderBy('name')->get(),
        ]);
    }
}
