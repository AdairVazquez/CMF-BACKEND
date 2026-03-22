<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Endpoints\UpdateEmployeeRequest;
use App\Http\Requests\StoreEmployeeRequest;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            // Recuperar todas los departamentos (paginadas para mejor rendimiento)
            $employees = Employee::latest()->paginate(10);

            return response()->json([
                'status' => 'success',
                'data'   => $employees
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No se pudieron recuperar las empresas.'
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        try {

            $data = $request->validated();

            $employee = Employee::create($data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Empleado registrado correctamente.',
                'data'    => $employee
            ], 201);
        } catch (Exception $e) {
            //Registro del error en los logs
            Log::error("Error al crear el Empleado: " . $e->getMessage());

            // Respuesta de error para el frontend (500 Internal Server Error)
            return response()->json([
                'status'  => 'error',
                'message' => 'Ocurrió un error inesperado al procesar el registro.',
                // 'error' => $e->getMessage() // Solo actívalo en desarrollo para debuggear
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee): JsonResponse
    {
        try {
            return response()->json([
                'status'  => 'success',
                'data'    => $employee
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No se pudo obtener el detalle del Empleado.'
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse
    {
        try {
            $data = $request->validated();

            $employee->update($data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Empleado actualizado correctamente.',
                'data'    => $employee
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error al actualizar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee): JsonResponse
    {
        try {
            // 2. Eliminar el registro de la base de datos
            $employee->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Empleado eliminado correctamente.'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error al intentar eliminar la sucursal: ' . $e->getMessage()
            ], 500);
        }
    }
}
