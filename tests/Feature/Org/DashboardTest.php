<?php

use App\Models\ActivityRequest;

it('shows only own-org requests on the dashboard', function () {
    $u = officer();
    $mine = ActivityRequest::factory()->create(['organization_id' => $u->organization_id]);
    $other = ActivityRequest::factory()->create();

    $this->actingAs($u)->get(route('org.dashboard'))
        ->assertOk()
        ->assertSee($mine->title)
        ->assertDontSee($other->title);
});

it('renders the request detail with timeline and checklist', function () {
    $u = officer();
    $r = ActivityRequest::factory()->create(['organization_id' => $u->organization_id]);
    app(\App\Services\ChecklistService::class)->seedFor($r);

    $this->actingAs($u)->get(route('org.requests.show', $r))
        ->assertOk()
        ->assertSee($r->title)
        ->assertSee('List of Participants');
});

it("blocks viewing another org's request", function () {
    $u = officer();
    $other = ActivityRequest::factory()->create();

    $this->actingAs($u)->get(route('org.requests.show', $other))->assertForbidden();
});
