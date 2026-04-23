<?php

namespace App\Providers;

use App\Contracts\Notifications\WhatsAppClient;
use App\Models\User;
use App\Services\Notifications\WapifyWhatsAppClient;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(WhatsAppClient::class, WapifyWhatsAppClient::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.admin');

        Gate::define('manage', function (User $user): bool {
            return $user->role === 'admin';
        });
    }
}
