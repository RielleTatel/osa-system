<?php

namespace App\Http\Controllers\OsaAdmin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ModeratorAssignmentController extends Controller
{
    public function store(Request $request, Organization $organization)
    {
        $validated = $request->validate([
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->where('role', Role::Moderator->value),
            ],
        ]);

        $organization->moderators()->syncWithoutDetaching([$validated['user_id']]);

        return back()->with('status', 'Moderator assigned.');
    }

    public function destroy(Organization $organization, User $user)
    {
        $organization->moderators()->detach($user->id);

        return back()->with('status', 'Moderator removed.');
    }
}
