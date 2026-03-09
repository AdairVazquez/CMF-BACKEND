<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rutas de la API REST del sistema CMF
| Base URL: /api/v1
|
*/

// Rutas públicas (sin autenticación)
Route::prefix('v1')->group(function () {
    
    // Health Check (público)
    Route::get('/system/health', [\App\Http\Controllers\Api\V1\SystemHealthController::class, 'health'])
        ->middleware('throttle:health');
    
    // Autenticación con rate limiting
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:login');
    
    // Endpoint para dispositivos NFC (se implementará después)
    // Route::post('/nfc/register', [NfcController::class, 'register'])
    //     ->middleware('throttle:nfc');
});

// Rutas protegidas (requieren autenticación con Sanctum)
Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    
    // Autenticación
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    
    // Rutas con tenant scope (se agregarán después)
    // Route::middleware('tenant.scope')->group(function () {
    //     // Empresas, sucursales, empleados, etc.
    // });
});
