<?php

namespace App\Http\Controllers\Org;

use App\Enums\DocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\UploadDocumentRequest;
use App\Models\ActivityRequest;
use App\Services\ChecklistService;

class DocumentUploadController extends Controller
{
    public function store(UploadDocumentRequest $request, ActivityRequest $activityRequest, ChecklistService $checklist)
    {
        $this->authorize('upload', $activityRequest);

        $type = DocumentType::from($request->validated('document_type'));
        $path = $request->file('file')->store("uploads/{$activityRequest->id}", 'public');

        $activityRequest->documentUploads()->create([
            'document_type' => $type,
            'file_path' => $path,
            'uploaded_by' => $request->user()->id,
            'uploaded_at' => now(),
        ]);

        $checklist->markSubmitted($activityRequest, $checklist->itemNameFor($type));

        return back()->with('status', 'Document uploaded.');
    }
}
