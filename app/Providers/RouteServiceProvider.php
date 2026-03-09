<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Rate limiter para la API general (60 requests por minuto por usuario autenticado)
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(config('cmf.rate_limiting.api', 60))
                ->by($request->user()?->id ?: $request->ip());
        });

        // Rate limiter para login (5 intentos por minuto por IP)
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(config('cmf.rate_limiting.login', 5))
                ->by($request->ip())
                ->response(function () {
                    $minutes = config('cmf.rate_limiting.login_lockout_minutes', 15);
                    return response()->json([
                        'success' => false,
                        'message' => "Demasiados intentos de inicio de sesión. Bloqueado por {$minutes} minutos.",
                    ], 429);
                });
        });

        // Rate limiter para dispositivos NFC (30 requests por minuto por device_id)
        RateLimiter::for('nfc', function (Request $request) {
            return Limit::perMinute(config('cmf.rate_limiting.nfc', 30))
                ->by($request->input('device_id') ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Dispositivo enviando demasiadas solicitudes.',
                    ], 429);
                });
        });

        // Rate limiter para health check (10 requests por minuto por IP)
        RateLimiter::for('health', function (Request $request) {
            return Limit::perMinute(config('cmf.rate_limiting.health', 10))
                ->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Límite de solicitudes alcanzado.',
                    ], 429);
                });
        });
    }
}
