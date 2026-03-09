<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * Verifica que el usuario autenticado tenga el permiso requerido
     *
     * Uso: Route::middleware('permission:employees.view')
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado',
            ], 401);
        }

        // Super admins tienen todos los permisos
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Verificar si el usuario tiene el permiso
        if (!$user->hasPermission($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para realizar esta acción',
            ], 403);
        }

        return $next($request);
    }
}
