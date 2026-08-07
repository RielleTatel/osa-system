<?php

namespace App\Services;

use App\Enums\ActivityStatus;
use App\Enums\ApprovalStage;
use App\Enums\Decision;
use App\Models\ActivityRequest;
use App\Models\User;
use App\Notifications\StatusChanged;
use Illuminate\Support\Facades\Notification;

class EndorsementService
{
    /**
     * Record an approval decision, transition the request status, and notify the org.
     */
    public function decide(
        ActivityRequest $request,
        User $actor,
        ApprovalStage $stage,
        Decision $decision,
        ?string $remarks = null,
    ): void {
        $request->approvals()->create([
            'stage' => $stage,
            'acted_by' => $actor->id,
            'decision' => $decision,
            'remarks' => $remarks,
            'acted_at' => now(),
        ]);

        $newStatus = match (true) {
            $decision === Decision::Rejected => ActivityStatus::Denied,
            $decision === Decision::RevisionRequested => ActivityStatus::RevisionNeeded,
            $stage === ApprovalStage::ModeratorEndorsement => ActivityStatus::ModeratorEndorsed,
            $stage === ApprovalStage::OsaDirectorNotation => ActivityStatus::AwaitingPhysical,
            // OSA review approval is driven by checklist completion, not this transition.
            default => null,
        };

        if ($newStatus !== null) {
            $request->update(['status' => $newStatus]);
        }

        Notification::send(
            $request->organization->officers,
            new StatusChanged($request->refresh(), $remarks),
        );
    }
}
