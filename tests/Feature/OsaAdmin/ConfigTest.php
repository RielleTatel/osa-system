<?php

use App\Enums\Role;
use App\Models\Organization;
use App\Models\User;

it('admin provisions an org officer account', function () {
    $a = admin();
    $org = Organization::factory()->create();

    $this->actingAs($a)->post(route('osa-admin.users.store'), [
        'name' => 'Org Prez', 'email' => 'prez@adzu.edu.ph',
        'role' => 'org_officer', 'organization_id' => $org->id,
    ])->assertRedirect();

    $u = User::where('email', 'prez@adzu.edu.ph')->sole();
    expect($u->role)->toBe(Role::OrgOfficer)->and($u->organization_id)->toBe($org->id);
});

it('requires an organization for org officers', function () {
    $a = admin();

    $this->actingAs($a)->post(route('osa-admin.users.store'), [
        'name' => 'No Org', 'email' => 'noorg@adzu.edu.ph', 'role' => 'org_officer',
    ])->assertSessionHasErrors('organization_id');
});

it('creates and updates organizations', function () {
    $a = admin();

    $this->actingAs($a)->post(route('osa-admin.organizations.store'), ['name' => 'Robotics Club'])->assertRedirect();
    $org = Organization::where('name', 'Robotics Club')->sole();

    $this->actingAs($a)->put(route('osa-admin.organizations.update', $org),
        ['name' => 'Robotics Club', 'accreditation_status' => 'suspended'])->assertRedirect();
    expect($org->refresh()->accreditation_status)->toBe('suspended');
});

it('admin assigns and removes a moderator', function () {
    $a = admin();
    $org = Organization::factory()->create();
    $m = User::factory()->create(['role' => Role::Moderator]);

    $this->actingAs($a)->post(route('osa-admin.moderators.store', $org), ['user_id' => $m->id])->assertRedirect();
    expect($org->moderators()->count())->toBe(1);

    $this->actingAs($a)->delete(route('osa-admin.moderators.destroy', [$org, $m]))->assertRedirect();
    expect($org->moderators()->count())->toBe(0);
});

it('rejects assigning a non-moderator user', function () {
    $a = admin();
    $org = Organization::factory()->create();
    $officer = User::factory()->create(['role' => Role::OrgOfficer]);

    $this->actingAs($a)->post(route('osa-admin.moderators.store', $org), ['user_id' => $officer->id])
        ->assertSessionHasErrors('user_id');
});
