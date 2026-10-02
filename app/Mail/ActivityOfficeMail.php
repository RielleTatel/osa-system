<?php

namespace App\Mail;

use App\Models\ActivityEmailDelivery;
use App\Services\ActivityEmailService;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ActivityOfficeMail extends Mailable
{
    public function __construct(public ActivityEmailDelivery $delivery) {}

    public function envelope(): Envelope
    {
        $office = ActivityEmailService::officeAddress();

        return new Envelope(
            from: new Address(config('mail.from.address'), 'OSA Activity System'),
            replyTo: $office ? [new Address($office)] : [],
            subject: ($this->delivery->event === 'director_ready'
                ? 'Activity ready for director review'
                : 'Activity submitted — awaiting endorsement').' | Request #'.$this->delivery->activity_request_id,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.activity-office');
    }
}
