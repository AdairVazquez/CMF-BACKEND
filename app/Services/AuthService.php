<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Autenticar usuario y generar token
     */
    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        // Verificar que el usuario existe y la contraseña es correcta
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciales incorrectas'],
            ]);
        }

        // Verificar que el usuario está activo
        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Usuario inactivo'],
            ]);
        }

        // Verificar que la empresa está activa (excepto super_admin)
        if (!$user->isSuperAdmin() && $user->company) {
            if ($user->company->status->value !== 'activo') {
                throw ValidationException::withMessages([
                    'email' => ['La empresa no está activa'],
                ]);
            }
        }

        // Generar token de Sanctum
        $token = $user->createToken('web-access')->plainTextToken;

        return $this->formatUserResponse($user, $token);
    }

    /**
     * Cerrar sesión y revocar token
     */
    public function logout(User $user): bool
    {
        // Revocar el token actual
        $user->currentAccessToken()->delete();

        return true;
    }

    /**
     * Obtener información del usuario autenticado
     */
    public function me(User $user): array
    {
        return $this->formatUserResponse($user);
    }

    /**
     * Refrescar token del usuario
     */
    public function refresh(User $user): array
    {
        // Revocar el token actual
        $user->currentAccessToken()->delete();

        // Generar nuevo token
        $token = $user->createToken('web-access')->plainTextToken;

        return $this->formatUserResponse($user, $token);
    }

    /**
     * Formatear respuesta del usuario con sus relaciones
     */
    public function formatUserResponse(User $user, ?string $token = null): array
    {
        // Cargar relaciones necesarias
        $user->load(['roles.permissions', 'company.companyModules']);

        // Obtener el primer rol del usuario
        $role = $user->roles->first();

        // Obtener todos los permisos del usuario
        $permissions = $user->roles
            ->flatMap(fn($role) => $role->permissions)
            ->pluck('slug')
            ->unique()
            ->values()
            ->toArray();

        // Formatear datos de la empresa (solo si no es super admin)
        $company = null;
        if (!$user->isSuperAdmin() && $user->company) {
            $activeModules = $user->company->companyModules()
                ->where('is_active', true)
                ->pluck('module_name')
                ->toArray();

            $company = [
                'id' => $user->company->id,
                'name' => $user->company->name,
                'status' => $user->company->status->label(),
                'plan' => $user->company->plan,
                'modules' => $activeModules,
            ];
        }

        $response = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'company_id' => $user->company_id,
                'is_super_admin' => $user->is_super_admin,
                'role' => $role ? $role->slug : null,
                'role_name' => $role ? $role->name : null,
                'permissions' => $permissions,
                'company' => $company,
            ],
        ];

        // Agregar token si se proporciona
        if ($token) {
            $response['token'] = $token;
            $response['token_type'] = 'Bearer';
        }

        return $response;
    }
}
