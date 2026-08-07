<?php

namespace App\Services;

use App\Models\ActivityRequest;
use App\Models\OsaForm3;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class PdfExportService
{
    public function referenceSlip(ActivityRequest $request): Response
    {
        $request->loadMissing(['organization', 'referenceSlip', 'checklistItems']);

        return Pdf::loadView('pdf.reference-slip', ['request' => $request])
            ->download("reference-slip-{$request->referenceSlip->reference_code}.pdf");
    }

    public function osaForm3(OsaForm3 $form): Response
    {
        $form->loadMissing(['complianceItems', 'activityRequest.organization', 'moderator']);

        return Pdf::loadView('pdf.osa-form-3', ['form' => $form])
            ->download("osa-form-3-{$form->activityRequest->id}.pdf");
    }
}
