<?php

namespace App\Services\Chat;

use App\Models\Conversation;
use App\Models\CreditTransaction;
use App\Models\Message;
use App\Models\User;
use App\Services\Commission\CommissionService;
use App\Services\Credit\CreditService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Service handling credit checks, deductions, commission splitting,
 * and ledger entries for paid messages and paid conversations.
 *
 * Commission split is delegated to CommissionService (reads
 * config('system_settings.platform_commission_percent') — default 30%
 * platform / 70% escort). All wallet/ledger movements delegate to
 * CreditService so the ledger is written exactly once per spend.
 *
 * Every credit movement is wrapped in the caller's DB transaction and
 * writes an immutable CreditTransaction ledger entry.
 */
class ChatCreditService
{
    public function __construct(
        private readonly CommissionService $commissionService,
        private readonly CreditService $creditService,
    ) {}

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
     * 3. Credit the sender's escort earnings (escort share of cost)
     * 4. Update conversation totals
     * 5. Mark the message as paid
     *
     * Must be called within an outer DB::transaction.
     */
    public function processUnlockPayment(User $payer, Message $message, Conversation $conversation): void
    {
        $cost = (float) $message->credit_cost;
        $split = $this->commissionService->split($cost);
        $escortShare = $split['escort'];

        $transaction = $this->creditService->spendCredits(
            $payer,
            $cost,
            Message::class,
            $message->id,
            'Unlocked message #'.$message->id.' in conversation #'.$conversation->id,
        );

        $senderEscort = $message->sender?->escortProfile;
        if ($senderEscort) {
            $this->creditService->creditEscort(
                $senderEscort,
                $escortShare,
                Message::class,
                $message->id,
                'Commission for unlocked message #'.$message->id,
            );
        }

        // Record the platform's cut explicitly — powers the admin Platform
        // Earnings widget straight from the ledger.
        $this->creditService->writePlatformCommission(
            $split['platform'],
            Message::class,
            $message->id,
            'Platform commission for unlocked message #'.$message->id,
        );

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
     * 3. Split commission and credit the escort (escort share of cost)
     * 4. Update conversation totals (total_credits_spent, total_earnings)
     * 5. Mark the message as paid and attach the transaction ID
     *
     * Must be called within an outer DB::transaction — the caller
     * is responsible for wrapping both message creation and this call.
     */
    public function processPaidMessage(User $sender, Conversation $conversation, Message $message): void
    {
        $cost = $this->getMessageCost();
        $split = $this->commissionService->split($cost);
        $escortShare = $split['escort'];

        // 1-2. Deduct from member wallet and write the usage ledger entry.
        $transaction = $this->creditService->spendCredits(
            $sender,
            $cost,
            Message::class,
            $message->id,
            'Paid message in conversation #'.$conversation->id,
        );

        // 3. Credit the escort's earnings and write their 'commission' ledger row.
        $escort = $conversation->otherUser($sender->id)?->escortProfile;
        if ($escort) {
            $this->creditService->creditEscort(
                $escort,
                $escortShare,
                Message::class,
                $message->id,
                'Commission for paid message #'.$message->id,
            );
        }

        // 3b. Record the platform's cut explicitly — powers the admin
        //     Platform Earnings widget straight from the ledger.
        $this->creditService->writePlatformCommission(
            $split['platform'],
            Message::class,
            $message->id,
            'Platform commission for paid message #'.$message->id,
        );

        // 4. Update conversation aggregates.
        $conversation->increment('total_credits_spent', $cost);
        $conversation->increment('total_earnings', $escortShare);

        if (! $conversation->credit_payer_id) {
            $conversation->updateQuietly(['credit_payer_id' => $sender->id]);
        }

        // 5. Mark the message as paid.
        $message->update([
            'is_paid' => true,
            'payment_verified' => true,
            'credit_transaction_id' => $transaction->id,
        ]);
    }
}
