<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('ingestion:run --source=reddit')->weekly()->withoutOverlapping();
Schedule::command('ingestion:run --source=hackernews')->weekly()->withoutOverlapping();
// Runs after ingestion so freshly ingested signals are always clustered and scored
Schedule::command('scoring:run')->weekly()->withoutOverlapping();
