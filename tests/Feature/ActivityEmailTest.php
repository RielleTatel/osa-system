<?php

use App\Enums\Role;
use App\Jobs\SendActivityEmail;
use App\Mail\ActivityOfficeMail;
use App\Models\ActivityRequest;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

function failingActivitySmtp(): void
{
    Mail::mailer('smtp')->setSymfonyTransport(new class extends AbstractTransport
    {
        protected function doSend(SentMessage $message): void
        {
            throw new TransportException('SMTP temporarily unavailable');
        }

        public function __toString(): string
        {
            return 'test-smtp';
        }
    });
}

function workActivityEmail(): void
{
    Artisan::call('queue:work', [
        'connection' => 'activity-email',
        '--queue' => 'activity-email',
        '--once' => true,
        '--sleep' => 0,
    ]);
}

it('emails staff directors and the office once per address after submission in the background', function () {
    Mail::fake();
    $staff = admin();
    $director = director();
    $officer = officer();
    User::factory()->create(['role' => Role::Moderator]);
    config(['activity-mail.office_address' => '  '.strtoupper($staff->email).'  ']);

    $this->actingAs($officer)->post(route('org.requests.store'), validRequestPayload())->assertRedirect();
    Mail::assertNothingSent();

    workActivityEmail();
    workActivityEmail();
    workActivityEmail();

    Mail::assertSent(ActivityOfficeMail::class, 2);
    foreach ([$staff, $director] as $recipient) {
        Mail::assertSent(ActivityOfficeMail::class, fn ($mail) => $mail->hasTo(strtolower($recipient->email)));
    }
});

it('emails only directors once when digital documents become complete', function () {
    Mail::fake();
    $staff = admin();
    $director = director();
    config(['activity-mail.office_address' => 'office@example.edu']);
    $this->actingAs(officer())->post(route('org.requests.store'), validRequestPayload())->assertRedirect();
    for ($i = 0; $i < 3; $i++) {
        workActivityEmail();
    }
    Mail::fake();
    $activity = ActivityRequest::sole();
    $moderator = moderatorFor($activity);
    $this->actingAs($moderator)->post(route('moderator.requests.decide', $activity), ['decision' => 'approved'])->assertRedirect();
    workActivityEmail();
    Mail::assertNothingSent();
    $this->actingAs($staff)->post(route('osa-admin.requests.start-review', $activity))->assertRedirect();

    $items = $activity->checklistItems()->where('is_physical', false)->get();
    foreach ($items as $item) {
        $this->patch(route('osa-admin.checklist.update', $item), ['status' => 'verified'])->assertRedirect();
    }
    $this->patch(route('osa-admin.checklist.update', $items->last()), ['status' => 'verified'])->assertRedirect();
    Mail::assertNothingSent();
    workActivityEmail();
    workActivityEmail();

    Mail::assertSent(ActivityOfficeMail::class, 1);
    Mail::assertSent(ActivityOfficeMail::class, function ($mail) use ($director) {
        return $mail->hasTo($director->email)
            && str_contains($mail->render(), 'Ready for director review');
    });
});

it('accepts submission during an SMTP outage and retries later without resending success', function () {
    failingActivitySmtp();
    config(['activity-mail.office_address' => null]);
    $staff = admin();
    $this->actingAs(officer())->post(route('org.requests.store'), validRequestPayload())->assertRedirect();
    workActivityEmail();

    Mail::fake();
    workActivityEmail();
    Mail::assertNothingSent();
    $this->travel(61)->seconds();
    workActivityEmail();
    workActivityEmail();
    Mail::assertSent(ActivityOfficeMail::class, fn ($mail) => $mail->hasTo($staff->email));
    Mail::assertSent(ActivityOfficeMail::class, 1);
});

it('shows exhausted failures to OSA staff and directors without exposing SMTP exceptions', function () {
    failingActivitySmtp();
    config(['activity-mail.office_address' => null]);
    $staff = admin();
    $this->actingAs(officer())->post(route('org.requests.store'), validRequestPayload())->assertRedirect();
    workActivityEmail();
    foreach ([61, 301, 901, 3601] as $seconds) {
        $this->travel($seconds)->seconds();
        workActivityEmail();
    }

    foreach ([$staff, director()] as $viewer) {
        $this->actingAs($viewer)->get(route('activity-email.index', ['status' => 'failed']))
            ->assertOk()->assertSee($staff->email)->assertSee('Failed')
            ->assertSee('Acquaintance Party')->assertSee('5 attempts')
            ->assertSee('Office mailbox is not configured')
            ->assertDontSee('SMTP temporarily unavailable');
    }
    $this->get(route('activity-email.index', ['status' => 'sent']))->assertOk()->assertDontSee('Acquaintance Party');
    $this->actingAs(officer())->get(route('activity-email.index'))->assertForbidden();
    $this->actingAs(User::factory()->create(['role' => Role::Moderator]))->get(route('activity-email.index'))->assertForbidden();

    Mail::fake();
    Artisan::call('queue:retry', ['id' => ['all']]);
    workActivityEmail();
    Mail::assertSent(ActivityOfficeMail::class, 1);
    $this->actingAs($staff)->get(route('activity-email.index', ['status' => 'sent']))
        ->assertOk()->assertSee('Acquaintance Party')->assertSee('6 attempts');
    $this->get(route('activity-email.index', ['status' => 'failed']))->assertDontSee('Acquaintance Party');
});

it('sends a safe activity summary with the office reply address and a protected link', function () {
    Mail::fake();
    config(['activity-mail.office_address' => 'office@example.edu', 'mail.from.address' => 'osa-system@example.edu']);
    $officer = officer();
    $this->actingAs($officer)->post(route('org.requests.store'), validRequestPayload([
        'title' => 'Activity <script>alert(1)</script>',
    ]))->assertRedirect();
    workActivityEmail();
    $activity = ActivityRequest::sole();

    Mail::assertSent(ActivityOfficeMail::class, function ($mail) use ($officer, $activity) {
        $html = $mail->render();
        expect($mail->hasTo('office@example.edu'))->toBeTrue()
            ->and($mail->hasFrom('osa-system@example.edu', 'OSA Activity System'))->toBeTrue()
            ->and($mail->hasReplyTo('office@example.edu'))->toBeTrue()
            ->and($mail->attachments)->toBeEmpty()
            ->and($mail->cc)->toBeEmpty()
            ->and($mail->bcc)->toBeEmpty()
            ->and($html)->toContain(e($officer->organization->name), 'Activity &lt;script&gt;', '#'.$activity->id,
                $activity->date_start->toDateString(), route('tracker.show', $activity))
            ->not->toContain('<script>', 'Juan Dela Cruz');

        return true;
    });
    $this->actingAs($officer)->get(route('tracker.show', $activity))->assertForbidden();
    auth()->logout();
    $this->get(route('tracker.show', $activity))->assertRedirect(route('login'));
    $this->get(route('activity-email.index'))->assertRedirect(route('login'));
});

it('discards delivery work if the surrounding submission transaction rolls back', function () {
    Mail::fake();
    config(['activity-mail.office_address' => 'office@example.edu']);
    $staff = admin();
    $officer = officer();
    DB::beginTransaction();
    $this->actingAs($officer)->post(route('org.requests.store'), validRequestPayload())->assertRedirect();
    DB::rollBack();

    workActivityEmail();
    Mail::assertNothingSent();
    $this->actingAs($staff)->get(route('activity-email.index'))->assertOk()->assertDontSee('Acquaintance Party');
});

it('does not send an already accepted delivery again if its job is replayed', function () {
    Mail::fake();
    config(['activity-mail.office_address' => 'office@example.edu']);
    $this->actingAs(officer())->post(route('org.requests.store'), validRequestPayload())->assertRedirect();
    workActivityEmail();
    $mail = Mail::sent(ActivityOfficeMail::class)->sole();
    app()->call([new SendActivityEmail($mail->delivery->id), 'handle']);
    Mail::assertSent(ActivityOfficeMail::class, 1);
});

it('does not announce invalid submissions or repeated OSA Form 3 saves', function () {
    Mail::fake();
    admin();
    director();
    config(['activity-mail.office_address' => 'office@example.edu']);
    $officer = officer();
    $this->actingAs($officer)->post(route('org.requests.store'), validRequestPayload(['title' => '']))
        ->assertSessionHasErrors('title');
    $activity = ActivityRequest::factory()->offCampus()->create(['organization_id' => $officer->organization_id]);
    moderatorFor($activity);
    $payload = [
        'program_name' => 'Coastal Outreach', 'course' => 'BSCS', 'destination_venue' => 'Vitali',
        'inclusive_dates' => 'Sept 12–13, 2026', 'number_of_students' => 40,
        'personnel_in_charge' => 'Ms. Reyes',
        'compliance' => array_fill(0, 11, ['compliance' => '1', 'remarks' => '']),
    ];
    $this->post(route('org.osa-form-3.store', $activity), $payload)->assertRedirect();
    $this->post(route('org.osa-form-3.store', $activity), $payload)->assertRedirect();
    workActivityEmail();
    Mail::assertNothingSent();
});

it('continues sending other recipients while one recipient is awaiting retry', function () {
    failingActivitySmtp();
    $staff = admin();
    config(['activity-mail.office_address' => 'office@example.edu']);
    $this->actingAs(officer())->post(route('org.requests.store'), validRequestPayload())->assertRedirect();
    workActivityEmail();

    Mail::fake();
    workActivityEmail();
    Mail::assertSent(ActivityOfficeMail::class, fn ($mail) => $mail->hasTo('office@example.edu'));
    Mail::assertNotSent(ActivityOfficeMail::class, fn ($mail) => $mail->hasTo($staff->email));
    $this->travel(61)->seconds();
    workActivityEmail();
    Mail::assertSent(ActivityOfficeMail::class, 2);
    $this->actingAs($staff)->get(route('activity-email.index', ['status' => 'sent']))
        ->assertOk()->assertSee('office@example.edu')->assertSee($staff->email);
});
