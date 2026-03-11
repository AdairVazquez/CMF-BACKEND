<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAccountLocked
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->isAccountLocked()) {
            $minutes = now()->diffInMinutes($user->locked_until);
            
            return response()->json([
                'success' => false,
                'message' => "Cuenta bloqueada. Intenta de nuevo en {$minutes} minutos.",
            ], 423);
        }

        return $next($request);
    }
}
