<?php

<<<<<<< HEAD
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    use ApiResponse;

    /**
     * Lista de empresas (solo super_admin)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isSuperAdmin()) {
            return $this->forbiddenResponse('Solo el super administrador puede listar todas las empresas.');
        }

        $companies = Company::withCount(['employees', 'users'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 20));

        return $this->paginatedResponse($companies, 'Empresas obtenidas correctamente.');
    }

    /**
     * Detalle de una empresa
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        // Super admin puede ver cualquier empresa; otros solo la propia
        if (!$user->isSuperAdmin() && $user->company_id !== $id) {
            return $this->forbiddenResponse('Sin permisos para ver esta empresa.');
        }

        $company = Company::withCount(['employees', 'users'])
            ->find($id);

        if (!$company) {
            return $this->notFoundResponse('Empresa no encontrada.');
        }

        return $this->successResponse($company, 'Empresa obtenida correctamente.');
=======
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanyController;
use App\Http\Requests\UpdateCompanyController;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

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
    public function store(StoreCompanyController $request): JsonResponse
    {
        try {
            // 1. Obtener los datos ya validados por el Request
            $data = $request->validated();

            // 2. Manejo del Logo (Si se subió un archivo)
            if ($request->hasFile('logo')) {
                // Generamos un nombre único para evitar que se sobrescriban
                $file = $request->file('logo');
                $fileName = time() . '_' . $file->getClientOriginalName();

                // Lo movemos a public/logos
                $file->move(public_path('logos'), $fileName);

                // Guardamos la ruta relativa en el array de datos
                $data['logo'] = 'logos/' . $fileName;
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
            $data = $request->validated();

            // Si mandan un nuevo logo, borramos el anterior (opcional) y subimos el nuevo
            if ($request->hasFile('logo')) {
                $file = $request->file('logo');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('logos'), $fileName);
                $data['logo'] = 'logos/' . $fileName;
            }

            $company->update($data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Empresa actualizada correctamente.',
                'data'    => $company
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
    public function destroy(Company $company): JsonResponse
    {
        try {
            // 1. Opcional: Eliminar el archivo del logo del disco para no dejar basura
            if ($company->logo && file_exists(public_path($company->logo))) {
                unlink(public_path($company->logo));
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
>>>>>>> a7bd4b1 (cambios problema de branch)
    }
}
