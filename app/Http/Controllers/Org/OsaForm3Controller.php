<?php

namespace App\Http\Controllers\Org;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOsaForm3Request;
use App\Models\ActivityRequest;
use App\Notifications\NewRequestAwaitingEndorsement;
use App\Services\ChecklistService;
use Illuminate\Support\Facades\Notification;

class OsaForm3Controller extends Controller
{
    /**
     * The 11 fixed CHED compliance labels, verbatim from docs/out-campus forms/OSA FORM 3.md.
     */
    public const COMPLIANCE_LABELS = [
        'Curriculum Requirements',
        'Destination',
        'Handbook or Manual',
        'Students — Consents of the Parents/Guardians; Medical Clearance of the students',
        'Person In Charge/Moderator',
        'First Aid Kit',
        'Fees/Fund',
        'Insurance',
        'Mobility of Students (Vehicles)',
        'LGUs/NGOs',
        'Activities — Orientation, Consultation, Announcement, Briefing, Learning Journals, Emergency Preparedness Plan',
    ];

    public function create(ActivityRequest $activityRequest)
    {
        $this->authorize('upload', $activityRequest);
        abort_unless($activityRequest->isOffCampus(), 404);

        return view('org.osa-form-3', [
            'request' => $activityRequest,
            'labels' => self::COMPLIANCE_LABELS,
            'form' => $activityRequest->osaForm3()->with('complianceItems')->first(),
        ]);
    }

    public function store(StoreOsaForm3Request $request, ActivityRequest $activityRequest, ChecklistService $checklist)
    {
        $this->authorize('upload', $activityRequest);
        abort_unless($activityRequest->isOffCampus(), 404);

        $form = $activityRequest->osaForm3()->updateOrCreate([], $request->safe()->except('compliance'));

        $form->complianceItems()->delete();
        foreach ($request->validated('compliance') as $i => $row) {
            $form->complianceItems()->create([
                'activity_label' => self::COMPLIANCE_LABELS[$i],
                'compliance' => $row['compliance'],
                'remarks' => $row['remarks'] ?? null,
            ]);
        }

        $checklist->markSubmitted($activityRequest, ChecklistService::ITEM_OSA_FORM_3);

        Notification::send(
            $activityRequest->organization->moderators,
            new NewRequestAwaitingEndorsement($activityRequest),
        );

        return redirect()->route('org.requests.show', $activityRequest)
            ->with('status', 'OSA Form 3 submitted to your moderator.');
    }
}
