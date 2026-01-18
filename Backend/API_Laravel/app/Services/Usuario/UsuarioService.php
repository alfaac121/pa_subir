<?php

namespace App\Services\Usuario;

use App\Contracts\Usuario\Services\IUsuarioService;
use App\Contracts\Usuario\Repositories\IUsuarioRepository;
use App\DTOs\Usuario\EditarPerfil\InputDto;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;


class UsuarioService implements IUsuarioService
{
    public function __construct(
        private IUsuarioRepository $usuarioRepository
    ) 
    {}

    public function update(int $id, InputDto $dto)
    {
        $authUserId = Auth::id();
    
        if ($authUserId !== $id) {
            throw new AuthorizationException(
                'No puedes editar el perfil de otro usuario.'
            );
        }
    
        $usuario = $this->usuarioRepository->findById($id);
    
        if (!$usuario) {
            throw new ModelNotFoundException(
                'Usuario no encontrado'
            );
        }
    
        return $this->usuarioRepository->update($id, $dto->toArray());
    }

    public function verPerfilPublico(int $id)
    {
        $usuario = $this->usuarioRepository->findById($id);

        if (!$usuario) {
            throw new ModelNotFoundException('Usuario no encontrado');
        }

        // Podríamos cargar relaciones aquí, como productos cuando existan modelos de productos
        return [
            'status' => 'success',
            'data' => $usuario
        ];
    }
}
