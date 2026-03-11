<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    use ApiResponse;

    /**
     * Lista de sucursales de la empresa
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Branch::withCount(['employees', 'devices'])
            ->orderBy('name');

        if ($user->isSuperAdmin()) {
            if ($request->filled('company_id')) {
                $query->where('company_id', $request->integer('company_id'));
            }
        } else {
            $query->where('company_id', $user->company_id);
        }

        if ($request->boolean('active_only', false)) {
            $query->where('is_active', true);
        }

        return $this->successResponse($query->get(), 'Sucursales obtenidas correctamente.');
    }
}
