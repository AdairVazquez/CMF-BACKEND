<?php

use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\DepartmentController;
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

    // Verificación 2FA (público con rate limiting)
    Route::post('/auth/two-factor/verify', [AuthController::class, 'verify2FA'])
        ->middleware('throttle:login');

    Route::post('/auth/two-factor/recovery', [AuthController::class, 'useRecoveryCode'])
        ->middleware('throttle:login');

    // Recuperación de contraseña
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:5,1');

    Route::post('/auth/verify-reset-code', [AuthController::class, 'verifyResetCode'])
        ->middleware('throttle:5,1');

    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])
        ->middleware('throttle:5,1');

    // Endpoint para dispositivos NFC (se implementará después)
    // Route::post('/nfc/register', [NfcController::class, 'register'])
    //     ->middleware('throttle:nfc');

});

// Rutas protegidas (requieren autenticación con Sanctum)
Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {

    // Autenticación
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/logout-all', [AuthController::class, 'logoutAll']);

    // 2FA (requiere autenticación)
    Route::post('/auth/two-factor/enable', [AuthController::class, 'enable2FA']);
    Route::post('/auth/two-factor/confirm', [AuthController::class, 'confirm2FA']);
    Route::post('/auth/two-factor/disable', [AuthController::class, 'disable2FA']);



    // Companie's endopoints
    Route::post('/companies', [CompanyController::class, 'store']);
    Route::get('/companies', [CompanyController::class, 'index']);
    Route::get('/companies/{company}', [CompanyController::class, 'show']);
    Route::put('/companies/{company}', [CompanyController::class, 'update']);
    Route::delete('/companies/{company}', [CompanyController::class, 'destroy']);

    // Branche's endpoints
    Route::get('/branches', [BranchController::class, 'index']);
    Route::post('/branches', [BranchController::class, 'store']);
    Route::get('/branches/{branch}', [BranchController::class, 'show']);
    Route::put('/branches/{branch}', [BranchController::class, 'update']);
    Route::delete('/branches/{branch}', [BranchController::class, 'destroy']);

    //  Department's endpoints
    Route::get('/departments', [DepartmentController::class, 'index']);
    Route::post('/departments', [DepartmentController::class, 'store']);
    Route::put('/departments/{department}', [DepartmentController::class, 'update']);
    Route::get('/departments/{department}', [DepartmentController::class, 'show']);
    Route::delete('/departments/{department}', [DepartmentController::class, 'destroy']);

    

    // Rutas con tenant scope (se agregarán después)
    // Route::middleware('tenant.scope')->group(function () {
    //     // Empresas, sucursales, empleados, etc.
    // });
});
