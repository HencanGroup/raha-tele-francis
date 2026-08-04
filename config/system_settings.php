<?php

/**
 * System-wide configuration defaults.
 *
 * These values are used as fallbacks when no DB row exists for a given key.
 * The AppServiceProvider::boot() method loads all rows from the
 * `system_settings` table and merges them into config('system_settings.*'),
 * overriding these defaults at runtime.
 *
 * Access anywhere:
 *   config('system_settings.platform_commission_percent', 30)
 */

return [

    /*
     * Commission split — platform keeps this percentage of every transaction;
     * the escort receives (100 - this) %.
     */
    'platform_commission_percent' => env('PLATFORM_COMMISSION_PERCENT', 30),

    /*
     * Minimum credits an escort must accrue before requesting a withdrawal.
     */
    'minimum_withdrawal_credits' => env('MINIMUM_WITHDRAWAL_CREDITS', 500),

    /*
     * Number of days before purchased credits expire.
     */
    'credit_expiry_days' => env('CREDIT_EXPIRY_DAYS', 365),

    /*
     * KES value of a single credit — used to convert escort earnings
     * (credits) into the M-Pesa B2C payout amount.
     */
    'credit_value_kes' => env('CREDIT_VALUE_KES', 5),

    /*
     * Credits charged to unlock an escort's phone number.
     */
    'phone_unlock_cost' => env('PHONE_UNLOCK_COST', 5),

    /*
     * Credits charged per paid message sent to an escort.
     */
    'message_cost' => env('MESSAGE_COST', 1),

];
