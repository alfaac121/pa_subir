<?php

namespace App\Repositories\Auth;

use App\Contracts\Auth\Repositories\ICuentaRepository;
use App\Models\Usuario;
use App\Models\Cuenta;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class CuentaRepository implements ICuentaRepository
{
    public function createOrUpdate(string $email, string $clave, string $password): Cuenta
    {
        Log::info('Iniciando proceso de creación de la cuenta en el CuentaRepository', [
            ['email' => $email]
        ]);

        return Cuenta::updateOrCreate(
            ['email' => $email],
            [
                'password' => Hash::make($password),
                'clave' => $clave,
                'fecha_clave' => Carbon::now()
            ],
        );
    }

    public function isCorreoVigente(string $email):bool
    {
        return Cuenta::where('email', $email)
            ->where('fecha_clave', '>', now()->subMinutes(10))
            ->exists();
    }

    public function isCuentaRegistrada(string $email): bool
    {
        $cuentaModelo = Cuenta::where('email', $email)->first();

        if (!$cuentaModelo) {
            return false;
        }

        return Usuario::where('cuenta_id', $cuentaModelo->id)->exists();
    }

    public function findByCorreo(string $email): ?Cuenta
    {
        return Cuenta::where('email', $email)->first();
    }

    public function findById(int $id): ?Cuenta
    {
        return Cuenta::where('id', $id)->first();
    }

    public function extenderExpiracion(Cuenta $cuentaModelo): Cuenta
    {
        $cuentaModelo->fecha_clave = now()->addMinutes(10);

        $cuentaModelo->save();
        return $cuentaModelo->refresh();
    }

    public function actualizarClave(Cuenta $cuentaModelo, string $nuevaClave): ?Cuenta
    {
        $cuentaModelo->update([
            'clave' => $nuevaClave,
            'fecha_clave' => Carbon::now(),
        ]);

        return $cuentaModelo->fresh();
    }
}
