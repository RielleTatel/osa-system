<?php

use App\Enums\ActivityStatus;
use App\Models\ActivityRequest;

it('archive searches approved requests by title', function () {
    $a = admin();
    ActivityRequest::factory()->create(['status' => ActivityStatus::Approved, 'title' => 'Coastal Cleanup']);
    ActivityRequest::factory()->create(['status' => ActivityStatus::Approved, 'title' => 'Chess Cup']);
    ActivityRequest::factory()->create(['status' => ActivityStatus::Submitted, 'title' => 'Coastal Pending']);

    $this->actingAs($a)->get(route('archive.index', ['q' => 'Coastal']))
        ->assertOk()
        ->assertSee('Coastal Cleanup')
        ->assertDontSee('Chess Cup')
        ->assertDontSee('Coastal Pending');
});

it('blocks org officers from the archive', function () {
    $this->actingAs(officer())->get(route('archive.index'))->assertForbidden();
});
