<?php

use App\Enums\ActivityStatus;
use App\Models\ActivityRequest;
use App\Models\ChecklistItem;

it('creates a request with relations', function () {
    $request = ActivityRequest::factory()->create();
    $request->checklistItems()->create(['item_name' => 'List of Participants', 'is_physical' => false]);

    expect($request->organization)->not->toBeNull()
        ->and($request->submitter)->not->toBeNull()
        ->and($request->submitter->organization_id)->toBe($request->organization_id)
        ->and($request->status)->toBe(ActivityStatus::Submitted)
        ->and($request->checklistItems)->toHaveCount(1)
        ->and(ChecklistItem::first()->status->value)->toBe('pending');
});

it('supports the off-campus factory state and hasOne relations', function () {
    $request = ActivityRequest::factory()->offCampus()->create();
    $request->osaForm3()->create([
        'program_name' => 'Outreach', 'course' => 'BSCS', 'destination_venue' => 'Vitali',
        'inclusive_dates' => 'Sept 12-13', 'number_of_students' => 40, 'personnel_in_charge' => 'Ms. Reyes',
    ]);

    expect($request->isOffCampus())->toBeTrue()
        ->and($request->osaForm3->program_name)->toBe('Outreach')
        ->and(ActivityRequest::status(ActivityStatus::Submitted)->count())->toBe(1);
});
