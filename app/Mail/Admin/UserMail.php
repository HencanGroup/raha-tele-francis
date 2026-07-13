<?php

namespace App\Mail\Admin;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class UserMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    public ?string $password = null;

    public string $verificationUrl;

    public function __construct(User $user, ?string $password = null)
    {
        $this->user = $user;
        $this->password = $password;

        // The post-verification redirect is derived server-side from user_type
        // in VerifyEmailController, so it is intentionally not embedded here.
        $this->verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $this->user->getKey(),
                'hash' => sha1($this->user->getEmailForVerification()),
            ]
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('admin/mail.user.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.admin.user',
        );
    }
}
