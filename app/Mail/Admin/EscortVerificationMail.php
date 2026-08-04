<?php

namespace App\Mail\Admin;

use App\Models\Escort;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Approval/rejection notification sent to an escort after admin review.
 *
 * Uses admin/mail translation keys so subjects and body copy are localised.
 * Queued via Mail::queue() so a slow SMTP round-trip never blocks the admin
 * click that triggered the verification change.
 */
class EscortVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Escort $escort,
        public bool $approved,
        public ?string $reason = null,
    ) {}

    public function envelope(): Envelope
    {
        $key = $this->approved
            ? 'admin/mail.escort_verification.approved_subject'
            : 'admin/mail.escort_verification.rejected_subject';

        return new Envelope(
            subject: __($key, ['name' => $this->escort->stage_name ?: $this->escort->user?->name]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.admin.escort-verification',
        );
    }
}
