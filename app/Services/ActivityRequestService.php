<?php

namespace App\Services;

use App\Enums\ActivityStatus;
use App\Models\ActivityRequest;
use App\Models\User;
use App\Notifications\NewRequestAwaitingEndorsement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ActivityRequestService
{
    public function __construct(private ChecklistService $checklist) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated, User $officer): ActivityRequest
    {
        $request = DB::transaction(function () use ($validated, $officer) {
            $request = ActivityRequest::create([
                ...collect($validated)->except(['participants', 'schedule_items'])->all(),
                'organization_id' => $officer->organization_id,
                'submitted_by' => $officer->id,
                'status' => ActivityStatus::Submitted,
                'submitted_at' => now(),
                'expected_participants' => count($validated['participants'] ?? []) ?: null,
            ]);

            $request->participants()->createMany($validated['participants'] ?? []);
            $request->scheduleItems()->createMany($validated['schedule_items'] ?? []);
            $this->checklist->seedFor($request);

            return $request;
        });

        Notification::send(
            $request->organization->moderators,
            new NewRequestAwaitingEndorsement($request),
        );

        return $request;
    }
}
