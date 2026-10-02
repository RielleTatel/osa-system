<?php

namespace App\Services;

use App\Enums\Role;
use App\Jobs\SendActivityEmail;
use App\Models\ActivityEmailDelivery;
use App\Models\ActivityRequest;
use App\Models\User;

class ActivityEmailService
{
    public static function officeAddress(): ?string
    {
        $address = strtolower(trim((string) config('activity-mail.office_address')));

        return filter_var($address, FILTER_VALIDATE_EMAIL) ? $address : null;
    }

    public function submitted(ActivityRequest $request): void
    {
        $recipients = User::whereIn('role', [Role::OsaAdmin, Role::OsaDirector])->pluck('email');
        if ($office = self::officeAddress()) {
            $recipients->push($office);
        }

        $this->record($request, 'submitted', $recipients->all());
    }

    public function directorReady(ActivityRequest $request): void
    {
        $this->record($request, 'director_ready', User::where('role', Role::OsaDirector)->pluck('email')->all());
    }

    /** Call inside the transaction that records the workflow event. */
    private function record(ActivityRequest $request, string $event, array $recipients): void
    {
        $summary = [
            'organization' => $request->organization->name,
            'title' => $request->title,
            'date_start' => $request->date_start->toDateString(),
            'date_end' => $request->date_end->toDateString(),
        ];

        foreach (array_unique(array_map(fn ($email) => strtolower(trim($email)), $recipients)) as $email) {
            $delivery = ActivityEmailDelivery::firstOrCreate([
                'activity_request_id' => $request->id,
                'event' => $event,
                'recipient' => $email,
            ], ['summary' => $summary]);

            if ($delivery->wasRecentlyCreated) {
                SendActivityEmail::dispatch($delivery->id);
            }
        }
    }
}
