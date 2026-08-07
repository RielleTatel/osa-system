<?php

use App\Models\ActivityRequest;
use App\Services\ChecklistService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('uploads a document and marks the checklist item submitted', function () {
    Storage::fake('public');
    $u = officer();
    $r = ActivityRequest::factory()->create(['organization_id' => $u->organization_id]);
    app(ChecklistService::class)->seedFor($r);

    $this->actingAs($u)->post(route('org.documents.store', $r), [
        'document_type' => 'participant_list_file',
        'file' => UploadedFile::fake()->create('list.pdf', 100, 'application/pdf'),
    ])->assertRedirect();

    expect($r->documentUploads()->count())->toBe(1)
        ->and($r->checklistItems()->where('item_name', 'List of Participants')->sole()->status->value)->toBe('submitted');
    Storage::disk('public')->assertExists($r->documentUploads()->sole()->file_path);
});

it('rejects invalid document types', function () {
    $u = officer();
    $r = ActivityRequest::factory()->create(['organization_id' => $u->organization_id]);

    $this->actingAs($u)->post(route('org.documents.store', $r), [
        'document_type' => 'parents_consent', // not in the enum — physical-only
        'file' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
    ])->assertSessionHasErrors('document_type');
});

it('rejects oversized files', function () {
    $u = officer();
    $r = ActivityRequest::factory()->create(['organization_id' => $u->organization_id]);

    $this->actingAs($u)->post(route('org.documents.store', $r), [
        'document_type' => 'student_id',
        'file' => UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf'),
    ])->assertSessionHasErrors('file');
});

it("blocks uploading to another org's request", function () {
    $u = officer();
    $other = ActivityRequest::factory()->create();

    $this->actingAs($u)->post(route('org.documents.store', $other), [
        'document_type' => 'student_id',
        'file' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
    ])->assertForbidden();
});
