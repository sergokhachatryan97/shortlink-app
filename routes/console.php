<?php

use App\Jobs\ExpireShortlinkLinksJob;
use App\Jobs\ExpireSubscriptionsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new ExpireSubscriptionsJob)->daily();
Schedule::job(new ExpireShortlinkLinksJob)->daily();
