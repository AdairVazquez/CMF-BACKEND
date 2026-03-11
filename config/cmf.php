<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CMF - Configuración del Sistema
    |--------------------------------------------------------------------------
    |
    | Configuración centralizada del sistema CMF (Control y Monitoreo de Fuerza Laboral)
    | Esta configuración permite que el sistema funcione en diferentes entornos:
    | - Desarrollo local (XAMPP sin Redis)
    | - VPS (Ubuntu con Redis)
    | - Hosting compartido (sin Redis, sin SSH)
    |
    */

    'version' => '1.0.0',

    /*
    |--------------------------------------------------------------------------
    | Fallbacks de Infraestructura
    |--------------------------------------------------------------------------
    |
    | El sistema debe funcionar aunque Redis no esté disponible.
    | Estos son los drivers primary y fallback para cada servicio.
    | El sistema detecta automáticamente y usa el fallback si el primary falla.
    |
    */

    'fallbacks' => [
        'cache' => [
            'primary' => env('CACHE_STORE_PRIMARY', 'redis'),
            'fallback' => env('CACHE_STORE_FALLBACK', 'file'),
        ],
        'queue' => [
            'primary' => env('QUEUE_CONNECTION_PRIMARY', 'redis'),
            'fallback' => env('QUEUE_CONNECTION_FALLBACK', 'database'),
        ],
        'session' => env('SESSION_DRIVER', 'file'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Redis
    |--------------------------------------------------------------------------
    */

    'redis' => [
        'auto_detect' => env('REDIS_AUTO_DETECT', true), // Detecta automáticamente
        'timeout' => env('REDIS_TIMEOUT', 100), // milisegundos
        'retry_after' => env('REDIS_RETRY_AFTER', 3), // intentos
        'reconnect_interval' => env('REDIS_RECONNECT_INTERVAL', 300), // segundos (5 minutos)
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Límites de solicitudes por minuto para diferentes endpoints.
    |
    */

    'rate_limiting' => [
        'login' => env('RATE_LIMIT_LOGIN', 5),
        'api' => env('RATE_LIMIT_API', 60),
        'nfc' => env('RATE_LIMIT_NFC', 30),
        'health' => env('RATE_LIMIT_HEALTH', 10),
        'login_lockout_minutes' => env('RATE_LIMIT_LOGIN_LOCKOUT', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Dispositivos NFC
    |--------------------------------------------------------------------------
    */

    'nfc' => [
        'device_timeout_minutes' => env('NFC_DEVICE_TIMEOUT', 5),
        'max_events_per_minute' => env('NFC_MAX_EVENTS', 30),
        'require_device_key' => env('NFC_REQUIRE_DEVICE_KEY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Check
    |--------------------------------------------------------------------------
    */

    'health' => [
        'expose_metrics' => env('HEALTH_EXPOSE_METRICS', false), // false en producción
        'critical_free_space_mb' => env('HEALTH_CRITICAL_SPACE', 100),
        'warning_free_space_mb' => env('HEALTH_WARNING_SPACE', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Warming
    |--------------------------------------------------------------------------
    |
    | Datos que se precargan en cache al iniciar el sistema.
    |
    */

    'cache_warming' => [
        'enabled' => env('CACHE_WARMING_ENABLED', true),
        'items' => [
            'permissions' => true,
            'company_modules' => true,
            'active_devices' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Seguridad
    |--------------------------------------------------------------------------
    */

    'security' => [
        'force_https' => env('FORCE_HTTPS', false),
        'api_key_header' => 'X-Device-Key',
        'max_login_attempts' => env('MAX_LOGIN_ATTEMPTS', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logs
    |--------------------------------------------------------------------------
    */

    'logging' => [
        'cache_fallback' => env('LOG_CACHE_FALLBACK', true),
        'failed_logins' => env('LOG_FAILED_LOGINS', true),
        'nfc_events' => env('LOG_NFC_EVENTS', true),
        'queue_failures' => env('LOG_QUEUE_FAILURES', true),
    ],

];
