<?php

namespace App\Services;

use App\Enums\ActivityStatus;
use App\Models\ActivityRequest;
use App\Models\ReferenceSlip;

class ReferenceSlipService
{
    public function generate(ActivityRequest $request): ReferenceSlip
    {
        abort_unless(
            in_array($request->status, [ActivityStatus::DocsComplete, ActivityStatus::AwaitingPhysical], true),
            422,
        );

        return $request->referenceSlip()->firstOrCreate([], [
            'reference_code' => 'OSA-'.now()->year.'-'.str_pad((string) $request->id, 5, '0', STR_PAD_LEFT),
            'generated_at' => now(),
        ]);
    }

    public function markClaimed(ReferenceSlip $slip): void
    {
        $slip->update(['claimed_at' => now()]);
    }
}
