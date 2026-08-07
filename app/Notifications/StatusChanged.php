<?php

namespace App\Notifications;

use App\Models\ActivityRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StatusChanged extends Notification
{
    use Queueable;

    public function __construct(public ActivityRequest $request, public ?string $remarks = null) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'activity_request_id' => $this->request->id,
            'title' => $this->request->title,
            'message' => 'Status: '.$this->request->status->label()
                .($this->remarks ? " — {$this->remarks}" : ''),
        ];
    }
}
