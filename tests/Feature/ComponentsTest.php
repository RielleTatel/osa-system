<?php

use Illuminate\Support\Facades\Blade;

it('renders a status pill with the mapped color', function () {
    $html = Blade::render('<x-status-pill status="moderator_endorsed" />');
    expect($html)->toContain('Moderator endorsed')->toContain('bg-blue-50');
});

it('renders a physical file card with amber pill and physical label', function () {
    $html = Blade::render('<x-file-card label="Medical Certificate" :physical="true" status="pending" />');
    expect($html)->toContain('Medical Certificate')->toContain('Physical')->toContain('bg-amber-50');
});

it('renders the timeline marking completed stages', function () {
    $html = Blade::render('<x-timeline current="osa_reviewing" />');
    expect($html)->toContain('Submitted')->toContain('OSA reviewing')->toContain('Approved');
});
