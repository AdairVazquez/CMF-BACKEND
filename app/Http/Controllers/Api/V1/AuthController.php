<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponse;

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * POST /api/v1/auth/login
     * Iniciar sesión y obtener token de acceso
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->validated());

            return $this->successResponse(
                $result,
                'Inicio de sesión exitoso'
            );
        } catch (ValidationException $e) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->errors(),
                401
            );
        }
    }

    /**
     * POST /api/v1/auth/logout
     * Cerrar sesión y revocar token actual
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->successResponse(
            null,
            'Sesión cerrada correctamente'
        );
    }

    /**
     * GET /api/v1/auth/me
     * Obtener información del usuario autenticado
     */
    public function me(Request $request): JsonResponse
    {
        $result = $this->authService->me($request->user());

        return $this->successResponse($result);
    }

    /**
     * POST /api/v1/auth/refresh
     * Refrescar token de acceso
     */
    public function refresh(Request $request): JsonResponse
    {
        $result = $this->authService->refresh($request->user());

        return $this->successResponse(
            $result,
            'Token refrescado exitosamente'
        );
    }
}
