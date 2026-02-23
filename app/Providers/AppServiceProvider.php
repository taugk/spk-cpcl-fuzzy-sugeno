<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Observers\UserObserver;
use App\Models\Cpcl;
use App\Observers\CpclObserver;

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
        // Menghubungkan Model User dengan UserObserver
        User::observe(UserObserver::class);
        Cpcl::observe(CpclObserver::class);
    }
}