<?php

namespace App\Http\Controllers;

use App\Models\ActivityRequest;
use App\Models\OsaForm3;
use App\Models\ReferenceSlip;
use App\Services\PdfExportService;
use App\Services\ReferenceSlipService;

class ReferenceSlipController extends Controller
{
    public function generate(ActivityRequest $activityRequest, ReferenceSlipService $service)
    {
        $service->generate($activityRequest);

        return back()->with('status', 'Reference slip generated.');
    }

    public function claim(ReferenceSlip $referenceSlip, ReferenceSlipService $service)
    {
        $service->markClaimed($referenceSlip);

        return back()->with('status', 'Slip marked as claimed.');
    }

    public function slipPdf(ActivityRequest $activityRequest, PdfExportService $pdf)
    {
        $this->authorize('view', $activityRequest);
        abort_unless($activityRequest->referenceSlip, 404);

        return $pdf->referenceSlip($activityRequest);
    }

    public function osaForm3Pdf(OsaForm3 $osaForm3, PdfExportService $pdf)
    {
        $this->authorize('view', $osaForm3->activityRequest);

        return $pdf->osaForm3($osaForm3);
    }
}
