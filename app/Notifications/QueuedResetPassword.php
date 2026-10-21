<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

// Queued: SMTP latency/outage never blocks the request, and response time no longer hints whether the email exists.
class QueuedResetPassword extends ResetPassword implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public $backoff = [60, 300];
}
