<?php

use App\Models\ActivityRequest;
use App\Services\ChecklistService;

it('seeds the in-campus checklist', function () {
    $r = ActivityRequest::factory()->create();
    app(ChecklistService::class)->seedFor($r);

    $names = $r->checklistItems()->pluck('item_name');
    expect($names)->toContain('List of Participants', "Parent's Consent (1 copy, wet signature)",
        'Pink Form — FORM A1.1 (wet signatures)')
        ->not->toContain('OSA Form 3 (digital form)');
    expect($r->checklistItems()->where('is_physical', true)->count())->toBe(3);
});

it('seeds the off-campus checklist with bundled Blue Form + Certificate of Compliance', function () {
    $r = ActivityRequest::factory()->offCampus()->create();
    app(ChecklistService::class)->seedFor($r);

    $names = $r->checklistItems()->pluck('item_name');
    expect($names)->toContain('OSA Form 3 (digital form)',
        'Blue Form — FORM A2.2 + Certificate of Compliance (wet signatures, notarized)',
        "Parent's Consent (3 notarized copies, wet signature)");
    expect($names->filter(fn ($n) => str_contains($n, 'Certificate of Compliance')))->toHaveCount(1);
});

it('is complete when all digital items are submitted even if physical items are pending', function () {
    $r = ActivityRequest::factory()->create();
    $svc = app(ChecklistService::class);
    $svc->seedFor($r);

    expect($svc->isComplete($r))->toBeFalse();
    $r->checklistItems()->where('is_physical', false)->update(['status' => 'submitted']);
    expect($svc->isComplete($r->refresh()))->toBeTrue();
});

it('marks a specific item submitted', function () {
    $r = ActivityRequest::factory()->create();
    $svc = app(ChecklistService::class);
    $svc->seedFor($r);

    $svc->markSubmitted($r, ChecklistService::ITEM_PARTICIPANT_LIST);
    expect($r->checklistItems()->where('item_name', ChecklistService::ITEM_PARTICIPANT_LIST)->sole()->status->value)
        ->toBe('submitted');
});
