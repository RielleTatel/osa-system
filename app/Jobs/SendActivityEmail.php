<?php

namespace App\Jobs;

use App\Mail\ActivityOfficeMail;
use App\Models\ActivityEmailDelivery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendActivityEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 60;

    public function backoff(): array
    {
        return [60, 300, 900, 3600];
    }

    public function __construct(public int $deliveryId)
    {
        $this->onConnection('activity-email');
        $this->onQueue('activity-email');
        // This database queue shares the workflow transaction. Other workers
        // cannot see its jobs until commit; rollback removes them together.
        $this->beforeCommit();
    }

    public function handle(): void
    {
        $delivery = ActivityEmailDelivery::find($this->deliveryId);
        if (! $delivery || $delivery->sent_at) {
            return;
        }

        $delivery->increment('attempts', 1, ['status' => 'pending', 'last_attempt_at' => now()]);
        Mail::mailer('smtp')->to($delivery->recipient)->send(new ActivityOfficeMail($delivery));
        $delivery->update(['status' => 'sent', 'sent_at' => now()]);
    }

    public function failed(?Throwable $exception): void
    {
        ActivityEmailDelivery::whereKey($this->deliveryId)->whereNull('sent_at')->update(['status' => 'failed']);
    }
}
