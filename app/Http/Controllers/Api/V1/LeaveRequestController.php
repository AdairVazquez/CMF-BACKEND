<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    use ApiResponse;

    /**
     * Lista de solicitudes de ausencia
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = LeaveRequest::with(['employee'])
            ->orderBy('created_at', 'desc');

        if ($user->isSuperAdmin()) {
            if ($request->filled('company_id')) {
                $query->where('company_id', $request->integer('company_id'));
            }
        } else {
            $query->where('company_id', $user->company_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('department_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('department_id', $request->integer('department_id'));
            });
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }

        $leaves = $query->paginate($request->integer('per_page', 20));

        return $this->paginatedResponse($leaves, 'Solicitudes de ausencia obtenidas.');
    }

    /**
     * Detalle de una solicitud
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $query = LeaveRequest::with(['employee', 'approvedByManager', 'approvedByHr', 'rejectedBy']);

        if (!$user->isSuperAdmin()) {
            $query->where('company_id', $user->company_id);
        }

        $leave = $query->find($id);

        if (!$leave) {
            return $this->notFoundResponse('Solicitud no encontrada.');
        }

        return $this->successResponse($leave, 'Solicitud obtenida correctamente.');
    }
}
