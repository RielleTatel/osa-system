<?php

namespace App\Http\Controllers\OsaAdmin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function index()
    {
        $organizations = Organization::withCount('officers')
            ->with('moderators')
            ->orderBy('name')
            ->paginate(15);

        return view('osa-admin.organizations.index', compact('organizations'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:organizations,name'],
            'accreditation_status' => ['nullable', 'in:accredited,suspended,inactive'],
        ]);

        Organization::create([
            'name' => $request->name,
            'accreditation_status' => $request->accreditation_status ?? 'accredited',
        ]);

        return back()->with('status', 'Organization created.');
    }

    public function edit(Organization $organization)
    {
        $organization->load('moderators');
        $moderators = \App\Models\User::where('role', \App\Enums\Role::Moderator)->orderBy('name')->get();

        return view('osa-admin.organizations.edit', compact('organization', 'moderators'));
    }

    public function update(Request $request, Organization $organization)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:organizations,name,'.$organization->id],
            'accreditation_status' => ['required', 'in:accredited,suspended,inactive'],
        ]);

        $organization->update($validated);

        return back()->with('status', 'Organization updated.');
    }
}
