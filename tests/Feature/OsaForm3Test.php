<?php

use App\Models\ActivityRequest;
use App\Services\ChecklistService;

it('org files OSA Form 3 for an off-campus request', function () {
    $u = officer();
    $r = ActivityRequest::factory()->offCampus()->create(['organization_id' => $u->organization_id]);
    app(ChecklistService::class)->seedFor($r);

    $payload = [
        'program_name' => 'Coastal Outreach', 'course' => 'BSCS', 'destination_venue' => 'Vitali',
        'inclusive_dates' => 'Sept 12–13, 2026', 'number_of_students' => 40,
        'personnel_in_charge' => 'Ms. Reyes',
        'compliance' => collect(range(0, 10))->map(fn () => ['compliance' => '1', 'remarks' => ''])->all(),
    ];

    $this->actingAs($u)->post(route('org.osa-form-3.store', $r), $payload)->assertRedirect();

    expect($r->osaForm3->complianceItems)->toHaveCount(11)
        ->and($r->checklistItems()->where('item_name', ChecklistService::ITEM_OSA_FORM_3)->sole()->status->value)->toBe('submitted');
});

it('rejects OSA Form 3 on in-campus requests', function () {
    $u = officer();
    $r = ActivityRequest::factory()->create(['organization_id' => $u->organization_id]);

    $this->actingAs($u)->get(route('org.osa-form-3.create', $r))->assertNotFound();
});

it('moderator approval stamps the form', function () {
    $r = ActivityRequest::factory()->offCampus()->create();
    $form = $r->osaForm3()->create([
        'program_name' => 'X', 'course' => 'Y', 'destination_venue' => 'Z',
        'inclusive_dates' => 'dates', 'number_of_students' => 10, 'personnel_in_charge' => 'P',
    ]);
    $m = moderatorFor($r);

    $this->actingAs($m)->post(route('moderator.osa-form-3.decide', $form), ['decision' => 'approved'])
        ->assertRedirect();

    $form->refresh();
    expect($form->moderator_approval_status)->toBe('approved')
        ->and($form->moderator_id)->toBe($m->id)
        ->and($form->moderator_approved_at)->not->toBeNull();
});

it('unassigned moderators cannot approve a form', function () {
    $r = ActivityRequest::factory()->offCampus()->create();
    $form = $r->osaForm3()->create([
        'program_name' => 'X', 'course' => 'Y', 'destination_venue' => 'Z',
        'inclusive_dates' => 'dates', 'number_of_students' => 10, 'personnel_in_charge' => 'P',
    ]);
    $stranger = \App\Models\User::factory()->create(['role' => \App\Enums\Role::Moderator]);

    $this->actingAs($stranger)->post(route('moderator.osa-form-3.decide', $form), ['decision' => 'approved'])
        ->assertForbidden();
});
