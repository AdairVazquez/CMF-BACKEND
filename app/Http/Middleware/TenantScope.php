<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TenantScope
{
    /**
     * Handle an incoming request.
     *
     * Middleware que asegura que cada usuario solo acceda a datos de su empresa
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado',
            ], 401);
        }

        // Si es super admin, permitir acceso a todo
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Si el usuario tiene company_id, inyectarlo en el request para uso posterior
        if ($user->company_id) {
            $request->merge(['auth_company_id' => $user->company_id]);
        }

        return $next($request);
    }
}
