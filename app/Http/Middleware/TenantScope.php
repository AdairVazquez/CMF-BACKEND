<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TenantScope
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && !$user->is_super_admin && !$user->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario sin empresa asignada',
            ], 403);
        }

        return $next($request);
    }
}
