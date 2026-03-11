<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Support\Facades\Log;

class BranchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            // Recuperar todas las empresas (paginadas para mejor rendimiento)
            $branches = Branch::latest()->paginate(10);

            return response()->json([
                'status' => 'success',
                'data'   => $branches
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
    public function store(StoreBranchRequest $request): JsonResponse
    {
        try {

            $data = $request->validated();

            $branch = Branch::create($data);


            return response()->json([
                'status'  => 'success',
                'message' => 'Sucursal registrada correctamente.',
                'data'    => $branch
            ], 201);
        } catch (Exception $e) {
            //Registro del error en los logs
            Log::error("Error al crear la Sucursal: " . $e->getMessage());

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
    public function show(Branch $branch): JsonResponse
    {
        try {
            return response()->json([
                'status'  => 'success',
                'data'    => $branch
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No se pudo obtener el detalle de la Sucursal.'
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBranchRequest $request, Branch $branch): JsonResponse
    {
        try {
            $data = $request->validated();

            $branch->update($data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Empresa actualizada correctamente.',
                'data'    => $branch
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
    public function destroy(Branch $branch): JsonResponse
    {
        try {
            // 2. Eliminar el registro de la base de datos
            $branch->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Sucursal eliminada correctamente.'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error al intentar eliminar la sucursal: ' . $e->getMessage()
            ], 500);
        }
    }
}
