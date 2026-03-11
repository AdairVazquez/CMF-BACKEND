<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    use ApiResponse;

    /**
     * Lista de empresas (solo super_admin)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isSuperAdmin()) {
            return $this->forbiddenResponse('Solo el super administrador puede listar todas las empresas.');
        }

        $companies = Company::withCount(['employees', 'users'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 20));

        return $this->paginatedResponse($companies, 'Empresas obtenidas correctamente.');
    }

    /**
     * Detalle de una empresa
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        // Super admin puede ver cualquier empresa; otros solo la propia
        if (!$user->isSuperAdmin() && $user->company_id !== $id) {
            return $this->forbiddenResponse('Sin permisos para ver esta empresa.');
        }

        $company = Company::withCount(['employees', 'users'])
            ->find($id);

        if (!$company) {
            return $this->notFoundResponse('Empresa no encontrada.');
        }

        return $this->successResponse($company, 'Empresa obtenida correctamente.');
    }
}
