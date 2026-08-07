<?php

use App\Enums\ActivityStatus;
use App\Models\ActivityRequest;
use App\Models\ReferenceSlip;
use App\Services\ReferenceSlipService;

it('generates an idempotent reference code once docs are complete', function () {
    $a = admin();
    $r = ActivityRequest::factory()->create(['status' => ActivityStatus::DocsComplete]);

    $this->actingAs($a)->post(route('osa-admin.slip.generate', $r))->assertRedirect();
    $this->actingAs($a)->post(route('osa-admin.slip.generate', $r));

    expect(ReferenceSlip::count())->toBe(1)
        ->and($r->refresh()->referenceSlip->reference_code)
        ->toBe('OSA-'.now()->year.'-'.str_pad($r->id, 5, '0', STR_PAD_LEFT));
});

it('refuses slip generation before docs_complete', function () {
    $a = admin();
    $r = ActivityRequest::factory()->create(['status' => ActivityStatus::OsaReviewing]);

    $this->actingAs($a)->post(route('osa-admin.slip.generate', $r))->assertStatus(422);
});

it('downloads the slip pdf for the owning org', function () {
    $u = officer();
    $r = ActivityRequest::factory()->create(['organization_id' => $u->organization_id, 'status' => ActivityStatus::AwaitingPhysical]);
    app(ReferenceSlipService::class)->generate($r);

    $this->actingAs($u)->get(route('slip.pdf', $r))
        ->assertOk()->assertHeader('content-type', 'application/pdf');
});

it('marks approved after the physical stage', function () {
    $a = admin();
    $r = ActivityRequest::factory()->create(['status' => ActivityStatus::AwaitingPhysical]);

    $this->actingAs($a)->post(route('osa-admin.requests.approve', $r))->assertRedirect();
    expect($r->refresh()->status)->toBe(ActivityStatus::Approved);
});

it('marks a slip claimed', function () {
    $a = admin();
    $r = ActivityRequest::factory()->create(['status' => ActivityStatus::DocsComplete]);
    $slip = app(ReferenceSlipService::class)->generate($r);

    $this->actingAs($a)->post(route('osa-admin.slip.claim', $slip))->assertRedirect();
    expect($slip->refresh()->claimed_at)->not->toBeNull();
});
