<?php

namespace App\Mail\Admin;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * Approval notification sent to an escort after admin verification.
 *
 * Contains a signed email-verification link so the escort can activate
 * their account. The verification URL is generated via
 * URL::temporarySignedRoute() with a 60-minute expiry, matching the
 * pattern established by UserMail.
 */
class EscortApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    public string $verificationUrl;

    public function __construct(User $user)
    {
        $this->user = $user;

        // Generate a signed verification URL — same pattern as UserMail.
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
            subject: __('admin/mail.escort_approved.subject', [
                'name' => $this->user->first_name ?: $this->user->name,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.admin.escort-approved',
        );
    }
}
