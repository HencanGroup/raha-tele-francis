<?php

namespace App\Mail\Admin;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Confirmation email sent to an escort immediately after self-registration.
 *
 * Informs the applicant that their profile is under review and will be
 * activated once an admin approves it. Queued via Mail::queue() so the
 * registration response is never blocked by SMTP latency.
 */
class EscortRegistrationConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('admin/mail.escort_registration_confirmed.subject', [
                'name' => $this->user->first_name ?: $this->user->name,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.admin.escort-registration-confirmed',
        );
    }
}
