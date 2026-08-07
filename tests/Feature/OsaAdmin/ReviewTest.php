<?php

use App\Enums\ActivityStatus;
use App\Models\ActivityRequest;
use App\Notifications\PacketReadyForPhysicalStage;
use App\Services\ChecklistService;
use Illuminate\Support\Facades\Notification;

it('filters the queue by status', function () {
    $a = admin();
    $endorsed = ActivityRequest::factory()->create(['status' => ActivityStatus::ModeratorEndorsed]);
    $submitted = ActivityRequest::factory()->create(['status' => ActivityStatus::Submitted]);

    $this->actingAs($a)->get(route('osa-admin.queue', ['status' => 'moderator_endorsed']))
        ->assertOk()->assertSee($endorsed->title)->assertDontSee($submitted->title);
});

it('starts review moving the request to osa_reviewing', function () {
    $a = admin();
    $r = ActivityRequest::factory()->create(['status' => ActivityStatus::ModeratorEndorsed]);

    $this->actingAs($a)->post(route('osa-admin.requests.start-review', $r))->assertRedirect();
    expect($r->refresh()->status)->toBe(ActivityStatus::OsaReviewing);
});

it('verifying the last digital item auto-advances to docs_complete and notifies', function () {
    Notification::fake();
    $a = admin();
    $r = ActivityRequest::factory()->create(['status' => ActivityStatus::OsaReviewing]);
    app(ChecklistService::class)->seedFor($r);
    $digital = $r->checklistItems()->where('is_physical', false)->get();
    $digital->each->update(['status' => 'submitted']);

    foreach ($digital as $item) {
        $this->actingAs($a)->patch(route('osa-admin.checklist.update', $item), ['status' => 'verified'])->assertRedirect();
    }

    expect($r->refresh()->status)->toBe(ActivityStatus::DocsComplete);
    Notification::assertSentTo($a, PacketReadyForPhysicalStage::class);
});

it('admin can request revisions', function () {
    $a = admin();
    $r = ActivityRequest::factory()->create(['status' => ActivityStatus::OsaReviewing]);

    $this->actingAs($a)->post(route('osa-admin.requests.decide', $r),
        ['decision' => 'revision_requested', 'remarks' => 'Missing venue details'])->assertRedirect();

    expect($r->refresh()->status)->toBe(ActivityStatus::RevisionNeeded);
});

it('blocks non-admins from the queue', function () {
    $this->actingAs(officer())->get(route('osa-admin.queue'))->assertForbidden();
});
