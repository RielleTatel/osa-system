<?php

namespace App\Policies;

use App\Models\OsaForm3;
use App\Models\User;

class OsaForm3Policy
{
    public function view(User $user, OsaForm3 $form): bool
    {
        return app(ActivityRequestPolicy::class)->view($user, $form->activityRequest);
    }
}
