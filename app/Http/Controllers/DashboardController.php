<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        return redirect()->route(match ($request->user()->role) {
            Role::OrgOfficer => 'org.dashboard',
            Role::Moderator => 'moderator.queue',
            Role::OsaAdmin => 'osa-admin.queue',
            Role::OsaDirector => 'osa-director.queue',
        });
    }
}
