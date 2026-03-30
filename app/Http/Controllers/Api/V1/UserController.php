<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Endpoints\StoreUserRequest;
use App\Http\Requests\Endpoints\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $users = User::with(['roles', 'company'])->latest()->paginate(10);

            return response()->json([
                'status' => 'success',
                'data'   => $users
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No se pudieron recuperar los usuarios.'
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        try {

            $data = $request->validated();

            $user = User::create($data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Usuario registrado correctamente.',
                'data'    => $user
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
    public function show(User $user): JsonResponse
    {
        try {
            return response()->json([
                'status'  => 'success',
                'data'    => $user
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
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        try {
            $data = $request->validated();
            if (empty($data['password'])) {
                unset($data['password']);
            } else {
                $data['password'] = bcrypt($data['password']);
            }
            $user->update($data);
            $user->update($data);
            return response()->json([
                'status'  => 'success',
                'message' => 'Empleado actualizado correctamente.',
                'data'    => $user
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
    public function destroy(User $user): JsonResponse
    {
        try {
            // 2. Eliminar el registro de la base de datos
            $user->delete();

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
