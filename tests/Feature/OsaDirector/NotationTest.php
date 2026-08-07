<?php

use App\Enums\ActivityStatus;
use App\Models\ActivityRequest;

it('director notation moves the request to awaiting_physical', function () {
    $d = director();
    $r = ActivityRequest::factory()->create(['status' => ActivityStatus::DocsComplete]);

    $this->actingAs($d)->post(route('osa-director.requests.decide', $r), ['decision' => 'approved'])
        ->assertRedirect();

    expect($r->refresh()->status)->toBe(ActivityStatus::AwaitingPhysical)
        ->and($r->approvals()->sole()->stage->value)->toBe('osa_director_notation');
});

it('director cannot note a request that is not docs_complete', function () {
    $d = director();
    $r = ActivityRequest::factory()->create(['status' => ActivityStatus::Submitted]);

    $this->actingAs($d)->post(route('osa-director.requests.decide', $r), ['decision' => 'approved'])
        ->assertStatus(422);
});

it('shows docs_complete requests in the notation queue', function () {
    $d = director();
    $ready = ActivityRequest::factory()->create(['status' => ActivityStatus::DocsComplete]);
    $notReady = ActivityRequest::factory()->create(['status' => ActivityStatus::OsaReviewing]);

    $this->actingAs($d)->get(route('osa-director.queue'))
        ->assertOk()->assertSee($ready->title)->assertDontSee($notReady->title);
});

it('blocks non-directors', function () {
    $this->actingAs(admin())->get(route('osa-director.queue'))->assertForbidden();
});
