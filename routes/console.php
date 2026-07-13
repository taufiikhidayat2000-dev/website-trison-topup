<?php

use App\Jobs\ExpireDeposits;
use App\Jobs\ExpirePayments;
use App\Jobs\ReconcileLinkQuPayments;
use App\Jobs\SyncFlashSaleStatuses;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new ExpirePayments)->hourly();
Schedule::job(new ExpireDeposits)->hourly();
Schedule::job(new ReconcileLinkQuPayments)->everyMinute();
Schedule::job(new SyncFlashSaleStatuses)->everyMinute();
