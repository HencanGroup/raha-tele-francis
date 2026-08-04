<?php

namespace App\Services\Commission;

/**
 * Central commission-split calculator for the platform economy.
 *
 * Every transaction where a member spends credits on an escort service is
 * split between the platform and the escort using
 * config('system_settings.platform_commission_percent') (default 30% platform /
 * 70% escort). Reading from system_settings keeps the split configurable from
 * the admin UI and env, and gives chat, phone-unlock, and future features one
 * shared source of truth for the math.
 */
class CommissionService
{
    /**
     * Split a credit amount into platform and escort shares.
     *
     * @param  float  $amount  Total credits spent by the member.
     * @return array{platform: float, escort: float} Platform keep + escort payout.
     */
    public function split(float $amount): array
    {
        $platformShare = $this->platformShare($amount);

        return [
            'platform' => $platformShare,
            'escort' => $amount - $platformShare,
        ];
    }

    /**
     * The escort's share of a spend — (100 - platform)% of the amount.
     */
    public function escortShare(float $amount): float
    {
        return $amount * (1 - $this->platformPercent() / 100);
    }

    /**
     * The platform's commission on a spend — platform% of the amount.
     */
    public function platformShare(float $amount): float
    {
        return $amount * ($this->platformPercent() / 100);
    }

    /**
     * Platform commission percentage from system settings (default 30).
     */
    public function platformPercent(): int
    {
        return (int) config('system_settings.platform_commission_percent', 30);
    }
}
