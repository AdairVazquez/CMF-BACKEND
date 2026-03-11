<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TwoFactorMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->hasTwoFactorEnabled()) {
            if (!session('two_factor_verified')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debes verificar el código 2FA primero',
                ], 403);
            }
        }

        return $next($request);
    }
}
