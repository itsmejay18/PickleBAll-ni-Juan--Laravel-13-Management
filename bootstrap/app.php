<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpFoundation\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // W7 fix: read TRUSTED_PROXIES from $_ENV directly (safe at bootstrap time).
        // Set TRUSTED_PROXIES in your .env to your actual load balancer IPs in production.
        // Defaults to '*' for local/testing convenience.
        $trustedProxies = $_ENV['TRUSTED_PROXIES'] ?? '*';
        $middleware->trustProxies(at: $trustedProxies, headers: Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO
        );

        // Apply baseline security headers and force HTTPS in production.
        $middleware->web(append: [
            SecurityHeaders::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'payments/xpaylink/webhook',
        ]);

        // Convenient role/permission gates on routes.
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Production-grade JSON responses for API-style requests; HTML pages otherwise.
        $exceptions->shouldRenderJsonWhen(function ($request) {
            return $request->expectsJson() || $request->is('api/*');
        });
    })->create();
