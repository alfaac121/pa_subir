<?php

namespace App\DTOs\Auth\Registro;

final readonly class RegisterDTO
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public string $email,
        public string $password,
        public string $nickname,
        public ?string $imagen = null,
        public int $estado_id = 1,
        public int $rol_id = 3,
        public ?string $descripcion = null,
        public ?string $link = null,
        public string $device_name = 'web'
    ){}

    // Crear una instacia del DTO a partir de un array de datos (procedente del request)
    public static function fromRequest(array $data): self {
        return new self(
            email: $data['email'],
            password: $data['password'],
            nickname: $data['nickname'],
            imagen: $data['imagen'] ?? null,
            rol_id: $data['rol_id'] ?? 3,
            estado_id: $data['estado_id'] ?? 1,
            descripcion: $data['descripcion'] ?? null,
            link: $data['link'] ?? null,
            device_name: $data['device_name'] ?? 'web'
        );
    }

    // Convertir el DTO a un array (para usar en la creación del usuario)
    public function toArray(): array {
        return [
            'email' => $this->email,
            'password' => $this->password,
            'nickname' => $this->nickname,
            'imagen' => $this->imagen ?? '',
            'rol_id' => $this->rol_id,
            'estado_id' => $this->estado_id,
            'descripcion' => $this->descripcion,
            'link' => $this->link,
            'device_name' => $this->device_name
        ];
    }

    public static function fromArray(array $data): self {
        return new self(
            email: $data['email'],
            password: $data['password'],
            nickname: $data['nickname'],
            imagen: $data['imagen'] ?? null,
            rol_id: $data['rol_id'] ?? 3,
            estado_id: $data['estado_id'] ?? 1,
            descripcion: $data['descripcion'] ?? null,
            link: $data['link'] ?? null,
            device_name: $data['device_name'] ?? 'web'
        );
    }
}
