<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AttendanceType;
use App\Enums\EmployeeStatus;
use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use ApiResponse;

    /**
     * Reporte de asistencia semanal
     * Retorna array con { day, present, absent, late } por día hábil de la semana actual
     */
    public function attendanceWeekly(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->isSuperAdmin()
            ? ($request->integer('company_id') ?: null)
            : $user->company_id;

        $startOfWeek = Carbon::now()->startOfWeek(); // Lunes
        $endOfWeek   = Carbon::now()->endOfWeek();   // Domingo

        // Solo días laborables (lunes a viernes)
        $workDays = CarbonPeriod::create($startOfWeek, $endOfWeek)
            ->filter('isWeekday')
            ->toArray();

        // Total activos
        $employeeQuery = Employee::where('status', EmployeeStatus::ACTIVO);
        if ($companyId) {
            $employeeQuery->where('company_id', $companyId);
        }
        if ($request->filled('department_id')) {
            $employeeQuery->where('department_id', $request->integer('department_id'));
        }
        $totalEmployees = $employeeQuery->count();
        $activeEmployeeIds = (clone $employeeQuery)->pluck('id');

        // Logs de toda la semana
        $logsQuery = AttendanceLog::whereIn('employee_id', $activeEmployeeIds)
            ->whereBetween('recorded_at', [
                $startOfWeek->copy()->startOfDay(),
                $endOfWeek->copy()->endOfDay(),
            ])
            ->where('type', AttendanceType::ENTRADA);

        if ($companyId) {
            $logsQuery->where('company_id', $companyId);
        }

        $allLogs = $logsQuery->get();

        $lateThreshold = '09:00:00';

        $data = collect($workDays)->map(function (Carbon $day) use ($allLogs, $totalEmployees, $lateThreshold) {
            $dayStr = $day->toDateString();

            $dayLogs = $allLogs->filter(fn ($log) => $log->recorded_at->toDateString() === $dayStr);

            $presentIds = $dayLogs->pluck('employee_id')->unique();
            $presentCount = $presentIds->count();

            $lateCount = $dayLogs->filter(fn ($log) =>
                $log->recorded_at->format('H:i:s') > $lateThreshold
            )->pluck('employee_id')->unique()->count();

            return [
                'day'     => $day->isoFormat('ddd D'),
                'date'    => $dayStr,
                'present' => $presentCount,
                'absent'  => max(0, $totalEmployees - $presentCount),
                'late'    => $lateCount,
            ];
        })->values();

        return $this->successResponse($data, 'Reporte semanal obtenido correctamente.');
    }
}
