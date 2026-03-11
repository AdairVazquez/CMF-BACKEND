<?php

<<<<<<< HEAD
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\LeaveRequestController;
use App\Http\Controllers\Api\V1\ReportController;
=======
<<<<<<< HEAD
use App\Http\Controllers\Api\V1\AuthController;
=======
use App\Http\Controllers\Api\BranchController;
>>>>>>> 034424d (Endpoint Branches)
>>>>>>> ba058bc (Endpoint Branches)
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CompanyController;

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
<<<<<<< HEAD
    
    // ─── Empresas ─────────────────────────────────────────────────────────────
    Route::get('/companies', [CompanyController::class, 'index']);
    Route::get('/companies/{id}', [CompanyController::class, 'show']);

    // ─── Empleados ────────────────────────────────────────────────────────────
    Route::get('/employees', [EmployeeController::class, 'index']);
    Route::get('/employees/{id}', [EmployeeController::class, 'show']);

    // ─── Sucursales ───────────────────────────────────────────────────────────
    Route::get('/branches', [BranchController::class, 'index']);

    // ─── Departamentos ────────────────────────────────────────────────────────
    Route::get('/departments', [DepartmentController::class, 'index']);

    // ─── Dispositivos ─────────────────────────────────────────────────────────
    Route::get('/devices', [DeviceController::class, 'index']);
    Route::get('/devices/events/recent', [DeviceController::class, 'recentEvents']);

    // ─── Asistencia ───────────────────────────────────────────────────────────
    Route::get('/attendance/today', [AttendanceController::class, 'today']);
    Route::get('/attendance/{employeeId}', [AttendanceController::class, 'employee']);

    // ─── Solicitudes de ausencia ──────────────────────────────────────────────
    Route::get('/leave-requests', [LeaveRequestController::class, 'index']);
    Route::get('/leave-requests/{id}', [LeaveRequestController::class, 'show']);

    // ─── Reportes ─────────────────────────────────────────────────────────────
    Route::get('/reports/attendance/weekly', [ReportController::class, 'attendanceWeekly']);
=======

    // Rutas con tenant scope (se agregarán después)
    // Route::middleware('tenant.scope')->group(function () {
    //     // Empresas, sucursales, empleados, etc.
    // });
>>>>>>> 6de5c82 (Implementacion de documentacion de 2AF y archivo api más su configuración para futuras conexiones)
});


Route::prefix('v1')->group(function () {

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

});
