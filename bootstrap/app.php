<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // API pura: nunca redirigir a login, siempre responder JSON 401
        $middleware->redirectGuestsTo(fn () => null);

        // Registrar middleware personalizados
        $middleware->alias([
            'tenant.scope' => \App\Http\Middleware\TenantScope::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'account.locked' => \App\Http\Middleware\CheckAccountLocked::class,
            'two.factor' => \App\Http\Middleware\TwoFactorMiddleware::class,
        ]);

        // Headers de seguridad global
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Manejo de excepciones de autenticación

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado. Inicia sesión para continuar.',
                ], 401);
            }

            return redirect()->guest(route('login'));
        });

        // Manejo de excepciones de autorización
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para realizar esta acción.',
            ], 403);
        });

        // Manejo de modelo no encontrado
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'El recurso solicitado no fue encontrado.',
            ], 404);
        });

        // Manejo de validación
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Los datos proporcionados no son válidos.',
                'errors' => $e->errors(),
            ], 422);
        });

        // Manejo de throttle (demasiadas peticiones)
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, $request) {
            $retryAfter = $e->getHeaders()['Retry-After'] ?? 60;
            
            return response()->json([
                'success' => false,
                'message' => "Demasiadas solicitudes. Intenta de nuevo en {$retryAfter} segundos.",
            ], 429);
        });

        // Manejo de errores de base de datos
        $exceptions->render(function (\Illuminate\Database\QueryException $e, $request) {
            \Log::error('Error de base de datos', [
                'message' => $e->getMessage(),
                'sql' => $e->getSql() ?? null,
                'url' => $request->fullUrl(),
                'user_id' => $request->user()?->id,
            ]);

            if (app()->environment('production')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al procesar la solicitud.',
                ], 500);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la solicitud.',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        });

        // Manejo de excepciones generales
        $exceptions->render(function (\Throwable $e, $request) {
            // Loggear todos los errores 500
            if ($e->getCode() >= 500 || $e->getCode() === 0) {
                \Log::error('Error del servidor', [
                    'message' => $e->getMessage(),
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'url' => $request->fullUrl(),
                    'user_id' => $request->user()?->id,
                ]);
            }

            // Respuesta según entorno
            if (app()->environment('production')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error interno del servidor.',
                ], 500);
            }
            
            // En desarrollo, mostrar el error completo
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())->take(5)->toArray(),
            ], 500);
        });
    })->create();
