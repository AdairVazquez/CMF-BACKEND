<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\LogoutAllRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\TwoFactorConfirmRequest;
use App\Http\Requests\Auth\TwoFactorDisableRequest;
use App\Http\Requests\Auth\TwoFactorRecoveryRequest;
use App\Http\Requests\Auth\TwoFactorVerifyRequest;
use App\Http\Requests\Auth\VerifyResetCodeRequest;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * Login con email y password
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            email: $request->email,
            password: $request->password,
            ip: $request->ip(),
            device: $request->device_name ?? $request->header('User-Agent')
        );

        if (!$result['success']) {
            return $this->errorResponse(
                $result['message'],
                [],
                isset($result['locked']) ? 423 : 401
            );
        }

        if (isset($result['requires_2fa']) && $result['requires_2fa']) {
            return $this->successResponse([
                'requires_2fa' => true,
                'token' => $result['token'],
            ], $result['message']);
        }

        return $this->successResponse([
            'token' => $result['token'],
            'user' => $result['user'],
        ], $result['message']);
    }

    /**
     * Verificar código 2FA
     */
    public function verify2FA(TwoFactorVerifyRequest $request): JsonResponse
    {
        $result = $this->authService->verify2FA(
            token: $request->token,
            code: $request->code
        );

        if (!$result['success']) {
            return $this->errorResponse($result['message'], [], 401);
        }

        return $this->successResponse([
            'token' => $result['token'],
            'user' => $result['user'],
        ], $result['message']);
    }

    /**
     * Usar código de recuperación 2FA
     */
    public function useRecoveryCode(TwoFactorRecoveryRequest $request): JsonResponse
    {
        $result = $this->authService->useRecoveryCode(
            token: $request->token,
            recoveryCode: $request->recovery_code
        );

        if (!$result['success']) {
            return $this->errorResponse($result['message'], [], 401);
        }

        return $this->successResponse([
            'token' => $result['token'],
            'user' => $result['user'],
            'remaining_codes' => $result['remaining_codes'],
        ], $result['message']);
    }

    /**
     * Iniciar configuración de 2FA con TOTP (retorna QR code para escanear con app autenticadora)
     */
    public function enable2FA(Request $request): JsonResponse
    {
        $result = $this->authService->enable2FA($request->user());

        if (!$result['success']) {
            return $this->errorResponse($result['message'], [], 400);
        }

        return $this->successResponse([
            'qr_code' => $result['qr_code'],
            'secret'  => $result['secret'],
        ], $result['message']);
    }

    /**
     * Confirmar y activar 2FA
     */
    public function confirm2FA(TwoFactorConfirmRequest $request): JsonResponse
    {
        $result = $this->authService->confirm2FA(
            user: $request->user(),
            code: $request->code
        );

        if (!$result['success']) {
            return $this->errorResponse($result['message'], [], 400);
        }

        return $this->successResponse([
            'recovery_codes' => $result['recovery_codes'],
        ], $result['message']);
    }

    /**
     * Desactivar 2FA
     */
    public function disable2FA(TwoFactorDisableRequest $request): JsonResponse
    {
        $result = $this->authService->disable2FA($request->user());

        return $this->successResponse(null, $result['message']);
    }

    /**
     * Obtener datos del usuario autenticado
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['roles.permissions', 'company']);

        return $this->successResponse([
            'user' => $user,
        ], 'Usuario autenticado');
    }

    /**
     * Cerrar sesión actual
     */
    public function logout(Request $request): JsonResponse
    {
        $result = $this->authService->logout($request->user());

        return $this->successResponse(null, $result['message']);
    }

    /**
     * Cerrar todas las sesiones
     */
    public function logoutAll(LogoutAllRequest $request): JsonResponse
    {
        $result = $this->authService->logoutAllDevices($request->user());

        return $this->successResponse(null, $result['message']);
    }

    /**
     * Solicitar recuperación de contraseña
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $result = $this->authService->forgotPassword($request->email);

        return $this->successResponse(null, $result['message']);
    }

    /**
     * Verificar código de recuperación y obtener reset_token temporal
     */
    public function verifyResetCode(VerifyResetCodeRequest $request): JsonResponse
    {
        $result = $this->authService->verifyResetCode(
            email: $request->email,
            code: $request->code
        );

        if (!$result['success']) {
            return $this->errorResponse($result['message'], [], 400);
        }

        return $this->successResponse([
            'reset_token' => $result['reset_token'],
            'expires_at'  => $result['expires_at'],
        ], $result['message']);
    }

    /**
     * Restablecer contraseña usando reset_token (obtenido desde verify-reset-code)
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $result = $this->authService->resetPassword(
            resetToken: $request->reset_token,
            newPassword: $request->password
        );

        if (!$result['success']) {
            return $this->errorResponse($result['message'], [], 400);
        }

        return $this->successResponse(null, $result['message']);
    }

    /**
     * Refrescar información del usuario
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user()->load(['roles.permissions', 'company']);

        return $this->successResponse([
            'user' => $user,
        ], 'Datos actualizados');
    }
}
