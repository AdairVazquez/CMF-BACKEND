<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\Device;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    use ApiResponse;

    /**
     * Lista de dispositivos con campo is_online calculado
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Device::with('branch')->orderBy('name');

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

        $devices = $query->get()->map(function (Device $device) {
            return array_merge($device->toArray(), [
                'is_online' => $device->isOnline(),
            ]);
        });

        return $this->successResponse($devices, 'Dispositivos obtenidos correctamente.');
    }

    /**
     * Eventos recientes de NFC (últimos registros de asistencia con info del empleado)
     */
    public function recentEvents(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = AttendanceLog::with(['employee', 'device'])
            ->orderBy('recorded_at', 'desc')
            ->limit(50);

        if ($user->isSuperAdmin()) {
            if ($request->filled('company_id')) {
                $query->where('company_id', $request->integer('company_id'));
            }
        } else {
            $query->where('company_id', $user->company_id);
        }

        $events = $query->get()->map(function (AttendanceLog $log) {
            return [
                'id'            => $log->id,
                'device_id'     => $log->device_id,
                'device_name'   => $log->device?->name,
                'employee_id'   => $log->employee_id,
                'employee_name' => $log->employee?->full_name,
                'type'          => $log->type->value,
                'recorded_at'   => $log->recorded_at?->toIso8601String(),
            ];
        });

        return $this->successResponse($events, 'Eventos recientes obtenidos correctamente.');
    }
}
