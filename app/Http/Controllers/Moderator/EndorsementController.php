<?php

namespace App\Http\Controllers\Moderator;

use App\Enums\ActivityStatus;
use App\Enums\ApprovalStage;
use App\Enums\Decision;
use App\Http\Controllers\Controller;
use App\Http\Requests\DecisionRequest;
use App\Models\ActivityRequest;
use App\Services\EndorsementService;
use Illuminate\Http\Request;

class EndorsementController extends Controller
{
    public function index(Request $request)
    {
        $orgIds = $request->user()->moderatedOrganizations()->pluck('organizations.id');

        $requests = ActivityRequest::whereIn('organization_id', $orgIds)
            ->status(ActivityStatus::Submitted)
            ->with('organization')
            ->latest('submitted_at')
            ->paginate(10);

        return view('moderator.queue', compact('requests'));
    }

    public function show(ActivityRequest $activityRequest)
    {
        $this->authorize('view', $activityRequest);

        $activityRequest->load(['organization', 'participants', 'scheduleItems', 'checklistItems', 'documentUploads']);

        return view('moderator.show', ['request' => $activityRequest]);
    }

    public function decide(DecisionRequest $request, ActivityRequest $activityRequest, EndorsementService $service)
    {
        $this->authorize('view', $activityRequest);
        abort_unless(
            $request->user()->moderatedOrganizations()->whereKey($activityRequest->organization_id)->exists(),
            403,
        );
        abort_unless($activityRequest->status === ActivityStatus::Submitted, 422);

        $service->decide(
            $activityRequest,
            $request->user(),
            ApprovalStage::ModeratorEndorsement,
            Decision::from($request->validated('decision')),
            $request->validated('remarks'),
        );

        return redirect()->route('moderator.queue')->with('status', 'Decision recorded.');
    }
}
