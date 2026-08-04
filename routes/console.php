<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily credit-expiry sweep — zeroes wallets past credits_expire_at and
// writes 'expiry' ledger entries. Requires the scheduler cron entry.
Schedule::command('credits:expire')->daily();
