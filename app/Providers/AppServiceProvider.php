<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\IncomingMail;
use App\Models\OutgoingMail;
use App\Models\User;
use App\Observers\AuditLogObserver;
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
        IncomingMail::observe(AuditLogObserver::class);
        OutgoingMail::observe(AuditLogObserver::class);
        User::observe(AuditLogObserver::class);
    }
}
