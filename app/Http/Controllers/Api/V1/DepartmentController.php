<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    use ApiResponse;

    /**
     * Lista de departamentos de la empresa
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Department::withCount('employees')->orderBy('name');

        if ($user->isSuperAdmin()) {
            if ($request->filled('company_id')) {
                $query->where('company_id', $request->integer('company_id'));
            }
        } else {
            $query->where('company_id', $user->company_id);
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->integer('branch_id'));
        }

        return $this->successResponse($query->get(), 'Departamentos obtenidos correctamente.');
    }
}
