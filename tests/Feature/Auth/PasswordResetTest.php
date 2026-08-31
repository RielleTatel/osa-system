<?php

use App\Enums\Role;
use App\Models\PasswordResetRequest;
use App\Models\User;
use App\Notifications\PasswordWasReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

test('reset password request screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response->assertStatus(200);
});

test('submitting the forgot-password form queues a request instead of emailing a link', function () {
    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email])
        ->assertSessionHas('status');

    expect(PasswordResetRequest::where('user_id', $user->id)->where('status', 'pending')->exists())->toBeTrue();
});

test('it does not queue duplicate pending requests for the same user', function () {
    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);
    $this->post('/forgot-password', ['email' => $user->email]);

    expect(PasswordResetRequest::where('user_id', $user->id)->count())->toBe(1);
});

test('osa admin can fulfill a pending request and the user gets a new password plus a notification', function () {
    Notification::fake();

    $admin = User::factory()->create(['role' => Role::OsaAdmin]);
    $user = User::factory()->create(['password' => Hash::make('old-password')]);
    $request = PasswordResetRequest::create(['user_id' => $user->id, 'status' => 'pending']);

    $this->actingAs($admin)
        ->post(route('osa-admin.password-resets.update', $request))
        ->assertRedirect(route('osa-admin.password-resets.index'));

    $request->refresh();
    $user->refresh();

    expect($request->status)->toBe('fulfilled')
        ->and($request->fulfilled_by)->toBe($admin->id)
        ->and(Hash::check('old-password', $user->password))->toBeFalse();

    Notification::assertSentTo($user, PasswordWasReset::class);
});

test('non-admins cannot fulfill password reset requests', function () {
    $officer = User::factory()->create(['role' => Role::OrgOfficer]);
    $user = User::factory()->create();
    $request = PasswordResetRequest::create(['user_id' => $user->id, 'status' => 'pending']);

    $this->actingAs($officer)
        ->post(route('osa-admin.password-resets.update', $request))
        ->assertForbidden();
});
