<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Endpoints\StoreCompanyRequest;
use App\Http\Requests\Endpoints\UpdateCompanyController;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Storage;
class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            // Recuperar todas las empresas (paginadas para mejor rendimiento)
            $companies = Company::latest()->paginate(10);

            return response()->json([
                'status' => 'success',
                'data'   => $companies
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
    public function store(StoreCompanyRequest $request): JsonResponse
    {
        try {
            // 1. Obtener los datos ya validados por el Request
            $data = $request->validated();

            // 2. Manejo del Logo (Si se subió un archivo)
            if ($request->hasFile('logo')) {
                // Usamos el disco 'public' para consistencia. 
                // Esto guarda en storage/app/public/logos y funciona con Storage::delete()
                $data['logo'] = $request->file('logo')->store('logos', 'public');
            }

            // 3. Crear la empresa en la base de datos
            // Nota: Gracias al $casts en el modelo, 'modules' se guarda como JSON automáticamente
            $company = Company::create($data);

            // 4. Respuesta exitosa (201 Created)
            return response()->json([
                'status'  => 'success',
                'message' => 'Empresa registrada correctamente.',
                'data'    => $company
            ], 201);
        } catch (Exception $e) {
            // 5. Registro del error en los logs (storage/logs/laravel.log)
            Log::error("Error al crear empresa: " . $e->getMessage());

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
    public function show(Company $company): JsonResponse
    {
        try {
            return response()->json([
                'status'  => 'success',
                'data'    => $company
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No se pudo obtener el detalle de la empresa.'
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */

    public function update(UpdateCompanyController $request, Company $company): JsonResponse
    {
        try {
            // Validamos los datos
            $data = $request->validated();

            // Si llega un nuevo logo, reemplazar el anterior
            if ($request->hasFile('logo')) {
                // Borrar logo anterior si existe
                if ($company->logo) {
                    Storage::disk('public')->delete($company->logo);
                }

                // Subir el nuevo logo al disco 'public/logos'
                $data['logo'] = $request->file('logo')->store('logos', 'public');
            }

            // Actualizar la empresa con los datos nuevos
            $company->update($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Empresa actualizada correctamente.',
                'data' => $company
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al actualizar: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company): JsonResponse
    {
        try {
            // 1. Opcional: Eliminar el archivo del logo del disco para no dejar basura
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }

            // 2. Eliminar el registro de la base de datos
            $company->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Empresa eliminada correctamente.'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error al intentar eliminar la empresa: ' . $e->getMessage()
            ], 500);
        }
    }
}
