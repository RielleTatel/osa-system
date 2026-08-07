<?php

use App\Enums\Role;
use App\Models\ActivityRequest;
use App\Models\User;

it('stores a request, seeds checklist, saves participants and schedule', function () {
    $u = officer();

    $this->actingAs($u)->post(route('org.requests.store'), validRequestPayload())->assertRedirect();

    $r = ActivityRequest::sole();
    expect($r->status->value)->toBe('submitted')
        ->and($r->organization_id)->toBe($u->organization_id)
        ->and($r->submitted_by)->toBe($u->id)
        ->and($r->participants)->toHaveCount(1)
        ->and($r->scheduleItems)->toHaveCount(1)
        ->and($r->checklistItems()->count())->toBeGreaterThan(0);
});

it('rejects activities starting less than 3 days from now', function () {
    $u = officer();

    $this->actingAs($u)->post(route('org.requests.store'), validRequestPayload([
        'date_start' => now()->addDay()->toDateString(),
        'date_end' => now()->addDay()->toDateString(),
    ]))->assertSessionHasErrors('date_start');
});

it('blocks non-org roles from creating', function () {
    $mod = User::factory()->create(['role' => Role::Moderator]);
    $this->actingAs($mod)->get(route('org.requests.create'))->assertForbidden();
});

it('blocks officers of non-accredited orgs', function () {
    $u = officer();
    $u->organization->update(['accreditation_status' => 'suspended']);
    $this->actingAs($u)->get(route('org.requests.create'))->assertForbidden();
});
