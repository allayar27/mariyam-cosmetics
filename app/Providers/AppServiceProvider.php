<?php

namespace App\Providers;

use App\Models\v1\Attendance;
use App\Observers\v1\AttendanceObserver;
use App\Observers\v1\UpdateAttendanceObserver;
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
        Attendance::observe( AttendanceObserver::class);
    }
}
