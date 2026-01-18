<?php

namespace App\Http\Controllers\Api;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Usuario\EditarPerfilRequest;
use App\Http\Requests\Usuario\BloquearUsuarioRequest;
use App\DTOs\Usuario\EditarPerfil\InputDto as EditarPerfilInputDto;
use App\Contracts\Usuario\Services\IUsuarioService;
use App\Contracts\Usuario\Services\IBloqueadoService;
use App\DTOs\Usuario\Bloqueados\InputDto as BloqueadoInputDto;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UsuarioController
{
    public function __construct(
        private IUsuarioService $usuarioService,
        private IBloqueadoService $bloqueadoService
    ) 
    {}

    public function verPerfilPublico(int $id)
    {
        try {
            $perfil = $this->usuarioService->verPerfilPublico($id);
            return response()->json($perfil);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Usuario no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Ocurrió un error al obtener el perfil'
            ], 500);
        }
    }

    public function update(int $id, EditarPerfilRequest $request)
    {
        $dto = EditarPerfilInputDto::fromRequest($request->validated());

        $perfil = $this->usuarioService->update($id, $dto);

        if (!$perfil) {
            response()->json([
                'status' => "Error",
                'message' => 'No se pudo completar la acción'
            ], 401);
        }

        return response()->json($perfil);
    }

    // == MODULO DE BLOQUEO DE USUARIOS == //

    public function bloquearUsuario(BloquearUsuarioRequest $request)
    {
        try{
            $dto = BloqueadoInputDto::fromRequest($request->validated());
    
            $resultado = $this->bloqueadoService->ejecutarBloqueo($dto);
    
            return response()->json($resultado, 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => "Error",
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function desbloquearUsuario(BloquearUsuarioRequest $request)
    {
        try { 
            $dto = BloqueadoInputDto::fromRequest($request->validated());
    
            $resultado = $this->bloqueadoService->ejecutarDesbloqueo($dto);
            
            return response()->json($resultado, 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => "Error",
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function obtenerBloqueadosPorUsuario()
    {
        try {
            $bloqueados = $this->bloqueadoService->solicitarBloqueadosPorUsuario(Auth::id());

            return response()->json($bloqueados);

        } catch (AuthorizationException $e) {
            return response()->json([
                'status' => "Error",
                'message' => $e->getMessage()
            ], 403);

        } catch (\Exception $e) {
            return response()->json([
                'status' => "Error",
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
