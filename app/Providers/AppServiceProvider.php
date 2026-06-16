<?php

namespace App\Providers;


use App\Events\TaskReassigned;
use App\Listeners\SendTaskAssignedNotification;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
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
        Event::listen(
            TaskReassigned::class,
            SendTaskAssignedNotification::class
        );
        // Limiter for Auth *(Login/Register)
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->input('email').$request->ip());
        }); 
    }
}
