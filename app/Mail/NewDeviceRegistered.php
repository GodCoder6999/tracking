<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewDeviceRegistered extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $deviceName,
        public string $platform,
        public string $dealerName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Action Required] New Device Registration — ' . $this->deviceName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-device',
        );
    }
}
