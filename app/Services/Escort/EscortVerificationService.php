<?php

namespace App\Services\Escort;

use App\Mail\Admin\EscortVerificationMail;
use App\Models\Escort;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Business logic for the escort verification/approval queue.
 *
 * Centralises every verification state change so no Filament action or form
 * mutates verification fields directly (AGENTS.md: verification must delegate
 * to a Service). verify() and reject() each run in a transaction and notify
 * the escort by email, so the admin view stays a thin trigger.
 */
class EscortVerificationService
{
    /**
     * Approve a pending escort — marks it verified, reactivates the user
     * account, and emails the escort an approval notification.
     */
    public function verify(Escort $escort): void
    {
        DB::transaction(function () use ($escort): void {
            $escort->update([
                'verification_status' => 'verified',
                'is_verified' => true,
            ]);

            // Reactivate the account in case it was previously suspended or
            // inactive — approval should unblock the escort immediately.
            $escort->user?->update(['status' => 'active']);
        });

        if ($escort->user?->email) {
            Mail::to($escort->user->email)->queue(
                new EscortVerificationMail($escort, approved: true),
            );
        }
    }

    /**
     * Reject a pending escort — marks it rejected and emails the escort a
     * rejection notification with the admin's reason (if any).
     */
    public function reject(Escort $escort, ?string $reason = null): void
    {
        DB::transaction(function () use ($escort): void {
            $escort->update([
                'verification_status' => 'rejected',
                'is_verified' => false,
            ]);
        });

        if ($escort->user?->email) {
            Mail::to($escort->user->email)->queue(
                new EscortVerificationMail($escort, approved: false, reason: $reason),
            );
        }
    }
}
