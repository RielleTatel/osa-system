<?php

namespace App\Http\Controllers\OsaDirector;

use App\Enums\ActivityStatus;
use App\Enums\ApprovalStage;
use App\Enums\Decision;
use App\Http\Controllers\Controller;
use App\Http\Requests\DecisionRequest;
use App\Models\ActivityRequest;
use App\Services\EndorsementService;

class NotationController extends Controller
{
    public function index()
    {
        $requests = ActivityRequest::status(ActivityStatus::DocsComplete)
            ->with('organization')
            ->latest('submitted_at')
            ->paginate(15);

        return view('osa-director.queue', compact('requests'));
    }

    public function show(ActivityRequest $activityRequest)
    {
        $activityRequest->load([
            'organization', 'participants', 'scheduleItems', 'checklistItems',
            'documentUploads', 'approvals.actor', 'osaForm3.complianceItems',
        ]);

        return view('osa-director.show', ['request' => $activityRequest]);
    }

    public function decide(DecisionRequest $request, ActivityRequest $activityRequest, EndorsementService $service)
    {
        abort_unless($activityRequest->status === ActivityStatus::DocsComplete, 422);

        $service->decide(
            $activityRequest,
            $request->user(),
            ApprovalStage::OsaDirectorNotation,
            Decision::from($request->validated('decision')),
            $request->validated('remarks'),
        );

        return redirect()->route('osa-director.queue')->with('status', 'Notation recorded.');
    }
}
