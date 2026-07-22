<?php

namespace App\Services\Chat;

use App\Models\Conversation;
use App\Models\CreditTransaction;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Service handling credit checks, deductions, commission splitting,
 * and ledger entries for paid messages and paid conversations.
 *
 * Commission split defaults to 30% platform / 70% escort.
 * Every credit movement is wrapped in a DB transaction and writes an
 * immutable CreditTransaction ledger entry.
 */
class ChatCreditService
{
    /**
     * Platform commission percentage (rest goes to the escort).
     */
    private const COMMISSION_PERCENT = 30;

    /**
     * Check whether the sender is allowed to send a message in this
     * conversation based on credit availability.
     *
     * Free conversations (is_paid_conversation = false) always pass.
     * Paid conversations require the sender to have sufficient credits.
     */
    public function canSendMessage(User $sender, Conversation $conversation): bool
    {
        if (! $conversation->is_paid_conversation) {
            return true;
        }

        $cost = $this->getMessageCost();

        return $sender->hasSufficientCredits($cost);
    }

    /**
     * Process the unlock payment flow when a member (receiver) pays
     * credits to reveal a locked message sent by an escort.
     *
     * 1. Deduct credits from the member (receiver) wallet
     * 2. Write a 'usage' CreditTransaction for the member
     * 3. Credit the sender's escort earnings (70% of cost)
     * 4. Update conversation totals
     * 5. Mark the message as paid
     *
     * Must be called within an outer DB::transaction.
     */
    public function processUnlockPayment(User $payer, Message $message, Conversation $conversation): void
    {
        $cost = (float) $message->credit_cost;
        $escortShare = $cost * (1 - self::COMMISSION_PERCENT / 100);
        $platformShare = $cost - $escortShare;

        $member = $payer->memberProfile;
        $balanceBefore = (float) ($member->credits ?? 0);
        $balanceAfter = $balanceBefore - $cost;

        $member->deductCredits($cost);

        $transaction = CreditTransaction::create([
            'user_id' => $payer->id,
            'type' => 'usage',
            'amount' => $cost,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference_type' => Message::class,
            'reference_id' => $message->id,
            'description' => 'Unlocked message #'.$message->id.' in conversation #'.$conversation->id,
        ]);

        $senderEscort = $message->sender?->escortProfile;
        if ($senderEscort) {
            $senderEscort->increment('earnings', $escortShare);
            $senderEscort->increment('balance', $escortShare);
        }

        $conversation->increment('total_credits_spent', $cost);
        $conversation->increment('total_earnings', $escortShare);

        if (! $conversation->credit_payer_id) {
            $conversation->updateQuietly(['credit_payer_id' => $payer->id]);
        }

        $message->update([
            'is_paid' => true,
            'payment_verified' => true,
            'credit_transaction_id' => $transaction->id,
        ]);
    }

    /**
     * Get the per-message credit cost from configuration.
     */
    public function getMessageCost(): float
    {
        return (float) config('services.system_variables.message_cost', 1);
    }

    /**
     * Query for messages in a conversation with per-side visibility filtering.
     *
     * Hides user-one-deleted from user_one, user-two-deleted from user_two.
     */
    public function visibleMessagesQuery(Conversation $conversation, User $user): Builder
    {
        $column = $conversation->user_one_id === $user->id
            ? 'user_one_deleted'
            : 'user_two_deleted';

        return $conversation->messages()
            ->where($column, false)
            ->getQuery();
    }

    /**
     * Process the full credit flow for a paid message:
     *
     * 1. Deduct credits from the sender (member) wallet
     * 2. Write a 'usage' CreditTransaction for the sender
     * 3. Split commission and credit the escort (70% of cost)
     * 4. Update conversation totals (total_credits_spent, total_earnings)
     * 5. Mark the message as paid and attach the transaction ID
     *
     * Must be called within an outer DB::transaction — the caller
     * is responsible for wrapping both message creation and this call.
     */
    public function processPaidMessage(User $sender, Conversation $conversation, Message $message): void
    {
        $cost = $this->getMessageCost();
        $escortShare = $cost * (1 - self::COMMISSION_PERCENT / 100);
        $platformShare = $cost - $escortShare;

        $member = $sender->memberProfile;
        $balanceBefore = (float) ($member->credits ?? 0);
        $balanceAfter = $balanceBefore - $cost;

        // 1. Deduct from member wallet
        $member->deductCredits($cost);

        // 2. Write usage ledger entry for the member
        $transaction = CreditTransaction::create([
            'user_id' => $sender->id,
            'type' => 'usage',
            'amount' => $cost,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference_type' => Message::class,
            'reference_id' => $message->id,
            'description' => 'Paid message in conversation #'.$conversation->id,
        ]);

        // 3. Credit the escort's earnings
        $escort = $conversation->otherUser($sender->id)?->escortProfile;
        if ($escort) {
            $escort->increment('earnings', $escortShare);
            $escort->increment('balance', $escortShare);
        }

        // 4. Update conversation aggregates
        $conversation->increment('total_credits_spent', $cost);
        $conversation->increment('total_earnings', $escortShare);

        if (! $conversation->credit_payer_id) {
            $conversation->updateQuietly(['credit_payer_id' => $sender->id]);
        }

        // 5. Mark the message as paid
        $message->update([
            'is_paid' => true,
            'payment_verified' => true,
            'credit_transaction_id' => $transaction->id,
        ]);
    }
}
