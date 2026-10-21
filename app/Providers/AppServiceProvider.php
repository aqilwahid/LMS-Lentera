<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Failed jobs stay in failed_jobs (php artisan queue:failed / queue:retry); also surface them in the app log.
        Queue::failing(fn (JobFailed $e) => Log::error('Job gagal: '.$e->job->resolveName(), ['exception' => $e->exception->getMessage()]));
        // Per browser session: participants may share a campus internet IP.
        RateLimiter::for('class-forms', fn (Request $request) => Limit::perMinute(30)->by($request->session()->getId())
        );
    }
}
