<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class WelcomeEmail extends Mailable
{
    use Queueable, SerializesModels;

    // Public properties are automatically available in the blade view
    public $user;
    public $appName;
    public $appLogo;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, $appName, $appLogo)
    {
        $this->user = $user;
        $this->appName = $appName;
        $this->appLogo = $appLogo;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to WealthTrack! 🎉',
        );
    }

    /**
     * Get the message content definition.
     */
    // public function content(): Content
    // {
    //     return new Content(
    //         view: 'emails.welcome', // Target template file location
    //     );
    // }

    public function build()
    {
        return $this->subject("Welcome to " . $this->appName . "!")
                    ->view('emails.welcome'); // Aapka blade template path
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
