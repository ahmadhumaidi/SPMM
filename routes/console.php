<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('spmm:invoices:send-expiry-reminders')->everyFifteenMinutes();
Schedule::command('spmm:invoices:expire-pending')->everyFifteenMinutes();
Schedule::command('spmm:news:generate-ai-draft')->dailyAt('07:00')->withoutOverlapping();
