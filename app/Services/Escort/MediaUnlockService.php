<?php

namespace App\Services\Escort;

use App\Models\EscortResource;
use App\Models\MediaUnlock;
use App\Models\User;
use App\Services\Commission\CommissionService;
use App\Services\Credit\CreditService;
use Illuminate\Support\Facades\DB;

/**
 * Service handling credit deduction, commission splitting, and ledger
 * entry for private media unlocks.
 *
 * Members pay credits to view private escort photos/videos. The cost
 * is read from config('system_settings.media_unlock_cost') (default 5).
 * Commission split follows the same 30/70 pattern as phone unlock and
 * paid messages.
 */
class MediaUnlockService
{
    public function __construct(
        private readonly CommissionService $commissionService,
        private readonly CreditService $creditService,
    ) {}

    /**
     * Whether the member has already paid to unlock this media item.
     */
    public function hasUnlocked(User $user, EscortResource $resource): bool
    {
        return MediaUnlock::where('user_id', $user->id)
            ->where('escort_resource_id', $resource->id)
            ->exists();
    }

    /**
     * Get all media IDs the member has unlocked for a given escort.
     *
     * @return array<int> Unlocked escort_resource IDs.
     */
    public function getUnlockedIds(User $user, int $escortId): array
    {
        return MediaUnlock::where('user_id', $user->id)
            ->whereHas('resource', fn ($q) => $q->where('escort_id', $escortId))
            ->pluck('escort_resource_id')
            ->toArray();
    }

    /**
     * Process a media unlock credit flow:
     *
     * 0. No-op when the member already unlocked this media (idempotency)
     * 1. Deduct credits from the member wallet
     * 2. Write a 'usage' CreditTransaction referencing the media
     * 3. Split commission: credit the escort + platform
     * 4. Create the MediaUnlock record
     */
    public function unlock(User $user, EscortResource $resource): void
    {
        // Idempotency guard — already paid = free no-op.
        if ($this->hasUnlocked($user, $resource)) {
            return;
        }

        $cost = (float) config('system_settings.media_unlock_cost', 5);
        $split = $this->commissionService->split($cost);
        $escort = $resource->escort;

        DB::transaction(function () use ($user, $resource, $cost, $split, $escort): void {
            // 1-2. Deduct from member wallet and write the usage ledger entry.
            $this->creditService->spendCredits(
                $user,
                $cost,
                EscortResource::class,
                $resource->id,
                'Media unlock: '.$resource->caption,
            );

            // 3a. Credit the escort's earnings.
            $this->creditService->creditEscort(
                $escort,
                $split['escort'],
                EscortResource::class,
                $resource->id,
                'Commission for media unlock #'.$resource->id,
            );

            // 3b. Record the platform's cut.
            $this->creditService->writePlatformCommission(
                $split['platform'],
                EscortResource::class,
                $resource->id,
                'Platform commission for media unlock #'.$resource->id,
            );

            // 4. Create the unlock record.
            MediaUnlock::create([
                'user_id' => $user->id,
                'escort_resource_id' => $resource->id,
                'credits_spent' => $cost,
            ]);
        });
    }

    /**
     * Get the unlock cost in credits.
     */
    public function cost(): float
    {
        return (float) config('system_settings.media_unlock_cost', 5);
    }
}
