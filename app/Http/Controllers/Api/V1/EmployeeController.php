<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    use ApiResponse;

    /**
     * Lista de empleados (filtrada por empresa del usuario)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Employee::with(['branch', 'department', 'shift'])
            ->orderBy('last_name');

        // Scope por empresa (super_admin puede filtrar por company_id query param)
        if ($user->isSuperAdmin()) {
            if ($request->filled('company_id')) {
                $query->where('company_id', $request->integer('company_id'));
            }
        } else {
            $query->where('company_id', $user->company_id);
        }

        // Filtros opcionales
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->integer('department_id'));
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->integer('branch_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $employees = $query->paginate($request->integer('per_page', 20));

        return $this->paginatedResponse($employees, 'Empleados obtenidos correctamente.');
    }

    /**
     * Detalle de un empleado
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $query = Employee::with(['branch', 'department', 'shift', 'nfcCard']);

        if (!$user->isSuperAdmin()) {
            $query->where('company_id', $user->company_id);
        }

        $employee = $query->find($id);

        if (!$employee) {
            return $this->notFoundResponse('Empleado no encontrado.');
        }

        return $this->successResponse($employee, 'Empleado obtenido correctamente.');
    }
}
