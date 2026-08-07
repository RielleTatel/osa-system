<?php

namespace App\Notifications;

use App\Models\ActivityRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DocumentsMissing extends Notification
{
    use Queueable;

    /**
     * @param  array<int, string>  $missing
     */
    public function __construct(public ActivityRequest $request, public array $missing = []) {}

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
            'message' => 'Missing documents: '.implode(', ', $this->missing),
        ];
    }
}
