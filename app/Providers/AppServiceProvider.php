<?php

namespace App\Providers;

use App\Contracts\Notifications\WhatsAppClient;
use App\Models\User;
use App\Services\Notifications\WapifyWhatsAppClient;
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
        Gate::define('manage', function (User $user): bool {
            return $user->role === 'admin';
        });
    }
}
