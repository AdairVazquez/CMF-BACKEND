<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AttendanceType;
use App\Enums\EmployeeStatus;
use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    use ApiResponse;

    /**
     * Resumen de asistencia del día actual
     * Retorna: { present, absent, late, total }
     */
    public function today(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->isSuperAdmin()
            ? ($request->integer('company_id') ?: null)
            : $user->company_id;

        $today = Carbon::today();

        // Total de empleados activos
        $employeeQuery = Employee::where('status', EmployeeStatus::ACTIVO);
        if ($companyId) {
            $employeeQuery->where('company_id', $companyId);
        }
        if ($request->filled('department_id')) {
            $employeeQuery->where('department_id', $request->integer('department_id'));
        }
        $totalEmployees = $employeeQuery->count();

        // IDs de empleados activos
        $activeEmployeeIds = (clone $employeeQuery)->pluck('id');

        // Empleados con al menos una entrada hoy
        $logsQuery = AttendanceLog::whereDate('recorded_at', $today)
            ->whereIn('employee_id', $activeEmployeeIds);

        if ($companyId) {
            $logsQuery->where('company_id', $companyId);
        }

        // Empleados que marcaron entrada
        $presentIds = (clone $logsQuery)
            ->where('type', AttendanceType::ENTRADA)
            ->pluck('employee_id')
            ->unique();

        $presentCount = $presentIds->count();

        // Empleados con entrada tardía (después de las 9:00 AM como umbral por defecto)
        $lateThreshold = $today->copy()->setTime(9, 0, 0);

        $lateCount = AttendanceLog::whereDate('recorded_at', $today)
            ->where('type', AttendanceType::ENTRADA)
            ->whereIn('employee_id', $activeEmployeeIds)
            ->where('recorded_at', '>', $lateThreshold)
            ->select('employee_id')
            ->distinct()
            ->count();

        $absentCount = $totalEmployees - $presentCount;

        return $this->successResponse([
            'present' => $presentCount,
            'absent'  => max(0, $absentCount),
            'late'    => $lateCount,
            'total'   => $totalEmployees,
            'date'    => $today->toDateString(),
        ], 'Resumen de asistencia de hoy obtenido.');
    }

    /**
     * Registros de asistencia de un empleado específico
     */
    public function employee(Request $request, int $employeeId): JsonResponse
    {
        $user = $request->user();

        $employee = Employee::find($employeeId);

        if (!$employee) {
            return $this->notFoundResponse('Empleado no encontrado.');
        }

        // Verificar acceso al empleado
        if (!$user->isSuperAdmin() && $employee->company_id !== $user->company_id) {
            return $this->forbiddenResponse('Sin permisos para ver este empleado.');
        }

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->string('start_date'))
            : Carbon::now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->string('end_date'))
            : Carbon::now()->endOfMonth();

        $logs = AttendanceLog::with('device')
            ->where('employee_id', $employeeId)
            ->whereBetween('recorded_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->orderBy('recorded_at', 'desc')
            ->paginate($request->integer('per_page', 30));

        return $this->paginatedResponse($logs, 'Asistencia del empleado obtenida.');
    }
}
