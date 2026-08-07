<?php

namespace App\Http\Controllers\Org;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActivityRequestRequest;
use App\Models\ActivityRequest;
use App\Services\ActivityRequestService;
use App\Services\ChecklistService;
use Illuminate\Http\Request;

class ActivityRequestController extends Controller
{
    public function index(Request $request)
    {
        $requests = ActivityRequest::where('organization_id', $request->user()->organization_id)
            ->latest('submitted_at')
            ->paginate(10);

        return view('org.dashboard', compact('requests'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', ActivityRequest::class);

        return view('org.requests.create');
    }

    public function store(StoreActivityRequestRequest $request, ActivityRequestService $service)
    {
        $this->authorize('create', ActivityRequest::class);

        $activityRequest = $service->create($request->validated(), $request->user());

        return redirect()->route('org.requests.show', $activityRequest)
            ->with('status', 'Request submitted and routed to your moderator.');
    }

    public function show(ActivityRequest $activityRequest, ChecklistService $checklist)
    {
        $this->authorize('view', $activityRequest);

        $activityRequest->load([
            'checklistItems', 'participants', 'scheduleItems',
            'documentUploads', 'approvals.actor', 'osaForm3', 'referenceSlip',
        ]);

        return view('org.requests.show', [
            'request' => $activityRequest,
            'uploadTypes' => $checklist->uploadTypesByItemName(),
        ]);
    }
}
