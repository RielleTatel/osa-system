<?php

use App\Enums\ActivityStatus;
use App\Enums\Role;
use App\Models\ActivityRequest;
use App\Models\User;
use App\Notifications\NewRequestAwaitingEndorsement;
use App\Notifications\StatusChanged;
use Illuminate\Support\Facades\Notification;

it('endorsing records an approval and advances status', function () {
    $r = ActivityRequest::factory()->create();
    $m = moderatorFor($r);

    $this->actingAs($m)->post(route('moderator.requests.decide', $r), ['decision' => 'approved'])
        ->assertRedirect();

    expect($r->refresh()->status)->toBe(ActivityStatus::ModeratorEndorsed)
        ->and($r->approvals()->sole()->stage->value)->toBe('moderator_endorsement');
});

it('revision request sets revision_needed and notifies the org officer', function () {
    Notification::fake();
    $r = ActivityRequest::factory()->create();
    $m = moderatorFor($r);

    $this->actingAs($m)->post(route('moderator.requests.decide', $r),
        ['decision' => 'revision_requested', 'remarks' => 'Fix the venue details']);

    expect($r->refresh()->status)->toBe(ActivityStatus::RevisionNeeded);
    Notification::assertSentTo($r->submitter, StatusChanged::class);
});

it('requires remarks unless approving', function () {
    $r = ActivityRequest::factory()->create();
    $m = moderatorFor($r);

    $this->actingAs($m)->post(route('moderator.requests.decide', $r), ['decision' => 'rejected'])
        ->assertSessionHasErrors('remarks');
});

it('unassigned moderators cannot decide', function () {
    $r = ActivityRequest::factory()->create();
    $stranger = User::factory()->create(['role' => Role::Moderator]);

    $this->actingAs($stranger)->post(route('moderator.requests.decide', $r), ['decision' => 'approved'])
        ->assertForbidden();
});

it('submitting a request notifies assigned moderators', function () {
    Notification::fake();
    $u = officer();
    $m = User::factory()->create(['role' => Role::Moderator]);
    $m->moderatedOrganizations()->attach($u->organization_id);

    $this->actingAs($u)->post(route('org.requests.store'), validRequestPayload());

    Notification::assertSentTo($m, NewRequestAwaitingEndorsement::class);
});

it('shows only pending submitted requests for assigned orgs in the queue', function () {
    $r = ActivityRequest::factory()->create(['status' => ActivityStatus::Submitted]);
    $m = moderatorFor($r);
    $otherOrg = ActivityRequest::factory()->create(['status' => ActivityStatus::Submitted]);

    $this->actingAs($m)->get(route('moderator.queue'))
        ->assertOk()->assertSee($r->title)->assertDontSee($otherOrg->title);
});
