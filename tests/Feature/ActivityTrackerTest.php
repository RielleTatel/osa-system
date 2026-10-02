<?php

use App\Enums\ActivityStatus;
use App\Models\ActivityRequest;

it('backfills existing activities as needing confirmation without inventing participant counts', function () {
    $withParticipants = ActivityRequest::factory()->create(['status' => ActivityStatus::Approved]);
    $withParticipants->participants()->createMany([['full_name' => 'One'], ['full_name' => 'Two']]);
    $unknown = ActivityRequest::factory()->create();
    $migration = require database_path('migrations/2026_10_02_000001_add_activity_tracking.php');
    $migration->down();
    $migration->up();

    $this->actingAs(admin())->get("/tracker/activities/{$withParticipants->id}")
        ->assertOk()->assertSee('Needs confirmation')->assertSee('Expected participants</dt><dd>2</dd>', false);
    $this->get("/tracker/activities/{$unknown->id}")->assertSee('Not provided');
});

it('captures the initial expected participant count and starts new submissions pending', function () {
    $response = $this->actingAs(officer())->post('/org/requests', validRequestPayload([
        'participants' => [['full_name' => 'First person'], ['full_name' => 'Second person']],
    ]))->assertRedirect();
    $id = basename($response->headers->get('Location'));
    $this->actingAs(admin())->get("/tracker/activities/{$id}")->assertOk()
        ->assertSee('Pending')->assertSee('Expected participants</dt><dd>2</dd>', false);
});

it('lets admins record progress with visible history without changing approval', function () {
    $actor = admin();
    $activity = ActivityRequest::factory()->create(['status' => ActivityStatus::Approved]);
    $this->actingAs($actor)->patch("/tracker/activities/{$activity->id}", [
        'progress' => 'completed', 'remarks' => 'Finished the outreach.',
    ])->assertRedirect();
    $this->get("/tracker/activities/{$activity->id}")->assertOk()
        ->assertSee('Completed')->assertSee('Approved')->assertSee('Finished the outreach.')
        ->assertSee($actor->name)->assertSee('Pending → Completed');
});

it('shows submitted activities across approval stages and excludes drafts', function () {
    ActivityRequest::factory()->create(['title' => 'Unfinished outreach']);
    ActivityRequest::factory()->create(['title' => 'Denied outing', 'status' => ActivityStatus::Denied]);
    ActivityRequest::factory()->create(['title' => 'Private draft', 'status' => ActivityStatus::Draft]);

    $this->actingAs(admin())->get('/tracker')->assertOk()
        ->assertSee('Unfinished outreach')->assertSee('Denied outing')
        ->assertSee('Not approved')->assertDontSee('Private draft');
});

it('filters by inclusive schedule overlap, approval, progress, organization and campus', function () {
    $matching = ActivityRequest::factory()->create([
        'title' => 'Cross-month workshop', 'date_start' => '2026-09-28', 'date_end' => '2026-10-03',
        'progress' => 'ongoing', 'status' => ActivityStatus::Approved,
    ]);
    ActivityRequest::factory()->create(['title' => 'Outside period', 'date_start' => '2026-09-01', 'date_end' => '2026-09-30']);
    ActivityRequest::factory()->create(['title' => 'Wrong progress', 'date_start' => '2026-10-01', 'date_end' => '2026-10-01']);

    $this->actingAs(director())->get('/tracker?'.http_build_query([
        'from' => '2026-10-03', 'to' => '2026-10-31', 'approval' => 'approved',
        'progress' => 'ongoing', 'organization_id' => $matching->organization_id, 'activity_type' => 'in_campus',
    ]))->assertOk()->assertSee('Cross-month workshop')->assertDontSee('Outside period')->assertDontSee('Wrong progress');
});

it('blocks non OSA roles from the tracker and director from progress editing', function () {
    $activity = ActivityRequest::factory()->create();
    foreach ([officer(), moderatorFor($activity)] as $user) {
        $this->actingAs($user)->get('/tracker')->assertForbidden();
        $this->get("/tracker/activities/{$activity->id}")->assertForbidden();
        $this->patch("/tracker/activities/{$activity->id}", ['progress' => 'completed'])->assertForbidden();
    }
    $this->actingAs(director())->get("/tracker/activities/{$activity->id}")->assertOk()->assertDontSee('Save progress');
    $this->patch("/tracker/activities/{$activity->id}", ['progress' => 'completed'])->assertForbidden();
});

it('validates progress and preserves an unchanged history on no-op updates', function () {
    $activity = ActivityRequest::factory()->create();
    $this->actingAs(admin())->patch("/tracker/activities/{$activity->id}", ['progress' => 'approved'])
        ->assertSessionHasErrors('progress');
    $this->patch("/tracker/activities/{$activity->id}", ['progress' => 'pending'])->assertRedirect();
    $this->get("/tracker/activities/{$activity->id}")->assertSee('No tracking changes recorded.');
    $this->patch("/tracker/activities/{$activity->id}", ['progress' => 'cancelled', 'remarks' => '<script>alert(1)</script>']);
    $this->get("/tracker/activities/{$activity->id}")->assertSee('Pending → Cancelled')
        ->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
});

it('rejects invalid report filters', function (array $filters, string $field) {
    $this->actingAs(admin())->get('/tracker?'.http_build_query($filters))->assertRedirect()->assertSessionHasErrors($field);
})->with([
    [['from' => '2026-10-31', 'to' => '2026-10-01'], 'to'],
    [['from' => 'not-a-date'], 'from'],
    [['progress' => 'approved'], 'progress'],
    [['approval' => 'completed'], 'approval'],
    [['activity_type' => 'invalid'], 'activity_type'],
    [['organization_id' => 999999], 'organization_id'],
]);

it('filters each attribute independently', function (array $filters, array $excluded) {
    ActivityRequest::factory()->create(['title' => 'Included request', 'date_start' => '2026-10-01', 'date_end' => '2026-10-03']);
    ActivityRequest::factory()->create(['title' => 'Excluded request', 'date_start' => '2026-10-01', 'date_end' => '2026-10-03', ...$excluded]);
    $this->actingAs(admin())->get('/tracker?'.http_build_query($filters))->assertOk()->assertSee('Included request')->assertDontSee('Excluded request');
})->with([
    [['from' => '2026-10-03'], ['date_start' => '2026-09-01', 'date_end' => '2026-10-02']],
    [['to' => '2026-10-01'], ['date_start' => '2026-10-02', 'date_end' => '2026-10-03']],
    [['approval' => 'submitted'], ['status' => ActivityStatus::Denied]],
    [['progress' => 'pending'], ['progress' => 'ongoing']],
    [['activity_type' => 'in_campus'], ['activity_type' => 'off_campus']],
]);

it('hides draft and unsubmitted records from direct tracking URLs', function () {
    $draft = ActivityRequest::factory()->create(['status' => ActivityStatus::Draft]);
    $unsubmitted = ActivityRequest::factory()->create(['submitted_at' => null]);
    foreach ([$draft, $unsubmitted] as $activity) {
        $this->actingAs(admin())->get("/tracker/activities/{$activity->id}")->assertNotFound();
        $this->patch("/tracker/activities/{$activity->id}", ['progress' => 'completed'])->assertNotFound();
    }
});
