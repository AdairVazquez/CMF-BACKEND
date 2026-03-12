<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Endpoints\UpdateDepartmentRequest;
use App\Http\Requests\StoreDepartmentRequest;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Support\Facades\Log;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            // Recuperar todas los departamentos (paginadas para mejor rendimiento)
            $departments = Department::latest()->paginate(10);

            return response()->json([
                'status' => 'success',
                'data'   => $departments
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
    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        try {

            $data = $request->validated();

            $department = Department::create($data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Departamento registrado correctamente.',
                'data'    => $department
            ], 201);
        } catch (Exception $e) {
            //Registro del error en los logs
            Log::error("Error al crear el Departamento: " . $e->getMessage());

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
    public function show(Department $department): JsonResponse
    {
        try {
            return response()->json([
                'status'  => 'success',
                'data'    => $department
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No se pudo obtener el detalle del Departamento.'
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDepartmentRequest $request, Department $department): JsonResponse
    {
        try {
            $data = $request->validated();

            $department->update($data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Departamento actualizado correctamente.',
                'data'    => $department
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
    public function destroy(Department $department): JsonResponse
    {
        try {
            // 2. Eliminar el registro de la base de datos
            $department->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Departamento eliminado correctamente.'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error al intentar eliminar la sucursal: ' . $e->getMessage()
            ], 500);
        }
    }
}
