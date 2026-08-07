<?php

namespace App\Http\Controllers\Moderator;

use App\Enums\ActivityStatus;
use App\Http\Controllers\Controller;
use App\Models\OsaForm3;
use Illuminate\Http\Request;

class OsaForm3ApprovalController extends Controller
{
    public function index(Request $request)
    {
        $orgIds = $request->user()->moderatedOrganizations()->pluck('organizations.id');

        $forms = OsaForm3::whereHas('activityRequest', fn ($q) => $q->whereIn('organization_id', $orgIds))
            ->where('moderator_approval_status', 'pending')
            ->with('activityRequest.organization')
            ->latest()
            ->paginate(10);

        return view('moderator.osa-form-3-index', compact('forms'));
    }

    public function show(OsaForm3 $osaForm3)
    {
        $this->authorize('view', $osaForm3);
        $osaForm3->load(['complianceItems', 'activityRequest.organization']);

        return view('moderator.osa-form-3', ['form' => $osaForm3]);
    }

    public function decide(Request $request, OsaForm3 $osaForm3)
    {
        $this->authorize('view', $osaForm3);
        abort_unless(
            $request->user()->moderatedOrganizations()->whereKey($osaForm3->activityRequest->organization_id)->exists(),
            403,
        );

        $validated = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
        ]);

        $osaForm3->update([
            'moderator_approval_status' => $validated['decision'],
            'moderator_id' => $request->user()->id,
            'moderator_approved_at' => now(),
        ]);

        return redirect()->route('moderator.osa-form-3.index')
            ->with('status', 'OSA Form 3 '.$validated['decision'].'.');
    }
}
