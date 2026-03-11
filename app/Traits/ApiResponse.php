<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    /**
     * Respuesta exitosa genérica
     */
    protected function successResponse($data, string $message = 'Operación exitosa', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Respuesta de error genérica
     */
    protected function errorResponse(string $message, array $errors = [], int $statusCode = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Respuesta cuando no se encuentra un registro
     */
    protected function notFoundResponse(string $message = 'Registro no encontrado'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 404);
    }

    /**
     * Respuesta de no autorizado (401)
     */
    protected function unauthorizedResponse(string $message = 'No autorizado'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 401);
    }

    /**
     * Respuesta de sin permisos (403)
     */
    protected function forbiddenResponse(string $message = 'Sin permisos para esta acción'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 403);
    }

    /**
     * Respuesta de error de validación (422)
     */
    protected function validationErrorResponse(array $errors): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Error de validación',
            'errors' => $errors,
        ], 422);
    }

    /**
     * Respuesta con datos paginados
     */
    protected function paginatedResponse(LengthAwarePaginator $paginator, string $message = 'OK'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
        ], 200);
    }
}
