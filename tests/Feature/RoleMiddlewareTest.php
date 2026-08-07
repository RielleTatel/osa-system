<?php

use App\Enums\Role;
use App\Models\User;

it('blocks the wrong role', function () {
    $officer = User::factory()->create(['role' => Role::OrgOfficer]);
    $this->actingAs($officer)->get('/osa-admin/queue')->assertForbidden();
});

it('allows the right role', function () {
    $admin = User::factory()->create(['role' => Role::OsaAdmin]);
    $this->actingAs($admin)->get('/osa-admin/queue')->assertOk();
});

it('redirects /dashboard by role', function () {
    $mod = User::factory()->create(['role' => Role::Moderator]);
    $this->actingAs($mod)->get('/dashboard')->assertRedirect(route('moderator.queue'));
});
