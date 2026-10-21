<?php

use Illuminate\Support\Facades\Schedule;

// Hostinger cron (once per minute): php artisan schedule:run. No daemon/Supervisor assumed.
Schedule::command('queue:work database --stop-when-empty --max-time=50 --tries=3')->everyMinute()->withoutOverlapping(5);
Schedule::command('auth:clear-resets')->hourly();
Schedule::command('queue:prune-failed --hours=720')->daily();
