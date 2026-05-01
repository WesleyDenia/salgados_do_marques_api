<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.login');
            }

            return null;
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->report(function (\Throwable $e) {
            $request = request();

            if (!$request instanceof Request) {
                return;
            }

            if (!$request->is('api/*') && !$request->expectsJson()) {
                return;
            }

            $statusCode = $e instanceof HttpExceptionInterface
                ? $e->getStatusCode()
                : 500;

            if ($statusCode < 500) {
                return;
            }

            $payload = $request->except([
                'password',
                'password_confirmation',
                'new_password',
                'new_password_confirmation',
                'token',
            ]);

            $context = [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'status_code' => $statusCode,
                'method' => $request->method(),
                'path' => $request->path(),
                'route_name' => optional($request->route())->getName(),
                'user_id' => optional($request->user())->id,
                'ip' => $request->ip(),
                'payload_keys' => array_keys($payload),
            ];

            if ($request->filled('method')) {
                $context['reset_method'] = (string) $request->input('method');
            }

            if ($request->filled('identifier')) {
                $context['identifier_hash'] = hash('sha256', Str::lower(trim((string) $request->input('identifier'))));
            }

            if ($request->filled('email')) {
                $context['email_hash'] = hash('sha256', Str::lower(trim((string) $request->input('email'))));
            }

            if ($request->filled('phone')) {
                $context['phone_hash'] = hash('sha256', preg_replace('/\s+/', '', (string) $request->input('phone')));
            }

            Log::error('[API Exception] Unhandled exception', $context);
        });

        $exceptions->renderable(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
        });

        $exceptions->renderable(function (RouteNotFoundException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
        });
    })->create();
