<?php

use App\Models\ActivityRequest;
use App\Notifications\StatusChanged;

it('shows unread notifications and marks them read on view', function () {
    $u = officer();
    $r = ActivityRequest::factory()->create(['organization_id' => $u->organization_id]);
    $u->notify(new StatusChanged($r));

    expect($u->unreadNotifications()->count())->toBe(1);

    $this->actingAs($u)->get(route('notifications.index'))
        ->assertOk()->assertSee($r->title);

    expect($u->fresh()->unreadNotifications()->count())->toBe(0);
});
