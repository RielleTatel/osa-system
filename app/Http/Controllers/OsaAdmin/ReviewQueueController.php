<?php

namespace App\Http\Controllers\OsaAdmin;

use App\Enums\ActivityStatus;
use App\Enums\ApprovalStage;
use App\Enums\Decision;
use App\Http\Controllers\Controller;
use App\Http\Requests\DecisionRequest;
use App\Models\ActivityRequest;
use App\Models\Organization;
use App\Notifications\DocumentsMissing;
use App\Notifications\StatusChanged;
use App\Services\EndorsementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class ReviewQueueController extends Controller
{
    public function index(Request $request)
    {
        $requests = ActivityRequest::query()
            ->with(['organization', 'checklistItems'])
            ->whereNot('status', ActivityStatus::Draft)
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->activity_type, fn ($q, $t) => $q->where('activity_type', $t))
            ->when($request->organization_id, fn ($q, $o) => $q->where('organization_id', $o))
            ->when($request->date_from, fn ($q, $d) => $q->whereDate('date_start', '>=', $d))
            ->when($request->date_to, fn ($q, $d) => $q->whereDate('date_start', '<=', $d))
            ->latest('submitted_at')
            ->paginate(15)
            ->withQueryString();

        return view('osa-admin.queue', [
            'requests' => $requests,
            'organizations' => Organization::orderBy('name')->get(),
        ]);
    }

    public function show(ActivityRequest $activityRequest)
    {
        $activityRequest->load([
            'organization', 'participants', 'scheduleItems', 'checklistItems',
            'documentUploads', 'approvals.actor', 'osaForm3.complianceItems', 'referenceSlip',
        ]);

        return view('osa-admin.show', ['request' => $activityRequest]);
    }

    public function startReview(ActivityRequest $activityRequest)
    {
        abort_unless($activityRequest->status === ActivityStatus::ModeratorEndorsed, 422);

        $activityRequest->update(['status' => ActivityStatus::OsaReviewing]);
        Notification::send($activityRequest->organization->officers, new StatusChanged($activityRequest));

        return back()->with('status', 'Review started.');
    }

    public function decide(DecisionRequest $request, ActivityRequest $activityRequest, EndorsementService $service)
    {
        abort_unless(
            in_array($activityRequest->status, [ActivityStatus::OsaReviewing, ActivityStatus::ModeratorEndorsed], true),
            422,
        );

        $service->decide(
            $activityRequest,
            $request->user(),
            ApprovalStage::OsaReview,
            Decision::from($request->validated('decision')),
            $request->validated('remarks'),
        );

        return redirect()->route('osa-admin.queue')->with('status', 'Decision recorded.');
    }

    public function nudge(ActivityRequest $activityRequest)
    {
        $missing = $activityRequest->checklistItems()
            ->where('is_physical', false)
            ->where('status', 'pending')
            ->pluck('item_name')
            ->all();

        Notification::send($activityRequest->organization->officers, new DocumentsMissing($activityRequest, $missing));

        return back()->with('status', 'Reminder sent to the organization.');
    }
}
