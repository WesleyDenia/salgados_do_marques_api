<?php

namespace App\Providers;

use App\Contracts\Notifications\WhatsAppClient;
use App\Models\User;
use App\Services\PasswordResetService;
use App\Services\Notifications\SalgadosWhatsAppClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(WhatsAppClient::class, SalgadosWhatsAppClient::class);
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

        RateLimiter::for('password-reset-forgot', function (Request $request) {
            $method = (string) $request->input('method', 'email');
            $identifier = PasswordResetService::normalizeIdentifier($method, (string) $request->input('identifier', ''));
            $maxAttempts = $method === 'whatsapp' ? 3 : 5;

            return Limit::perMinute($maxAttempts)->by($request->ip() . '|' . $method . '|' . $identifier);
        });

        RateLimiter::for('password-reset-otp', function (Request $request) {
            $phone = PasswordResetService::normalizeIdentifier('whatsapp', (string) $request->input('phone', ''));

            return Limit::perMinute(5)->by($request->ip() . '|whatsapp|' . $phone);
        });

        RateLimiter::for('password-reset-token', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip() . '|token|' . sha1((string) $request->input('token', '')));
        });
    }
}
