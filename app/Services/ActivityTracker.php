<?php

namespace App\Services;

use App\Enums\ActivityStatus;
use App\Models\ActivityRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ActivityTracker
{
    public function update(ActivityRequest $activity, array $values, User $actor): void
    {
        DB::transaction(function () use ($activity, $values, $actor) {
            $activity = ActivityRequest::whereKey($activity->id)->lockForUpdate()->firstOrFail();
            $remarks = $values['remarks'] ?? null;
            if ($activity->progress->value === $values['progress'] && $activity->tracker_remarks === $remarks) {
                return;
            }
            $activity->progressUpdates()->create([
                'user_id' => $actor->id, 'actor_name' => $actor->name,
                'old_progress' => $activity->progress, 'new_progress' => $values['progress'],
                'old_remarks' => $activity->tracker_remarks, 'new_remarks' => $remarks,
            ]);
            $activity->update(['progress' => $values['progress'], 'tracker_remarks' => $remarks]);
        });
    }

    public function query(array $filters): Builder
    {
        return ActivityRequest::query()->whereNotNull('submitted_at')
            ->where('status', '!=', ActivityStatus::Draft)
            ->when($filters['from'] ?? null, fn ($q, $date) => $q->whereDate('date_end', '>=', $date))
            ->when($filters['to'] ?? null, fn ($q, $date) => $q->whereDate('date_start', '<=', $date))
            ->when($filters['progress'] ?? null, fn ($q, $value) => $q->where('progress', $value))
            ->when($filters['approval'] ?? null, fn ($q, $value) => $q->where('status', $value))
            ->when($filters['organization_id'] ?? null, fn ($q, $value) => $q->where('organization_id', $value))
            ->when($filters['activity_type'] ?? null, fn ($q, $value) => $q->where('activity_type', $value))
            ->with(['organization.moderators', 'submitter'])->orderBy('date_start')->orderBy('id');
    }
}
