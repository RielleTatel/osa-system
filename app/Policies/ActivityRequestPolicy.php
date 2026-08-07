<?php

namespace App\Policies;

use App\Enums\ActivityStatus;
use App\Enums\Role;
use App\Models\ActivityRequest;
use App\Models\User;

class ActivityRequestPolicy
{
    public function view(User $user, ActivityRequest $request): bool
    {
        return match ($user->role) {
            Role::OrgOfficer => $user->organization_id === $request->organization_id,
            Role::Moderator => $user->moderatedOrganizations()->whereKey($request->organization_id)->exists(),
            Role::OsaAdmin, Role::OsaDirector => true,
        };
    }

    public function create(User $user): bool
    {
        return $user->role === Role::OrgOfficer
            && $user->organization?->accreditation_status === 'accredited';
    }

    public function update(User $user, ActivityRequest $request): bool
    {
        return $user->role === Role::OrgOfficer
            && $user->organization_id === $request->organization_id
            && in_array($request->status, [ActivityStatus::Draft, ActivityStatus::RevisionNeeded], true);
    }

    /**
     * Whether the officer may still attach documents / fill sub-forms.
     */
    public function upload(User $user, ActivityRequest $request): bool
    {
        return $user->role === Role::OrgOfficer
            && $user->organization_id === $request->organization_id
            && ! in_array($request->status, [
                ActivityStatus::Approved,
                ActivityStatus::Denied,
                ActivityStatus::AwaitingPhysical,
            ], true);
    }
}
