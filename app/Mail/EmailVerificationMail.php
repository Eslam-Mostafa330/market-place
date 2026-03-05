<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $verifyUrl;
    public string $userName;
    public string $appName;

    /**
     * Create a new message instance.
     */
    public function __construct(string $verifyUrl, string $userName = '')
    {
        $this->verifyUrl = $verifyUrl;
        $this->userName  = $userName;
        $this->appName   = "Marketplace";
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify Your Email - ' . $this->appName,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.email-verification',
            with: [
                'verifyUrl' => $this->verifyUrl,
                'userName'  => $this->userName,
                'appName'   => $this->appName,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromPath(public_path('assets/images/img.png'))
                ->withMime('image/png')
                ->as('logo.png'),
        ];
    }
}
