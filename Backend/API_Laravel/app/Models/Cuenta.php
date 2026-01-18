<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use App\Models\Usuario;
use Carbon\Carbon;

class Cuenta extends Authenticatable implements JWTSubject
{
    
    protected $table = 'cuentas';

    public $timestamps = false;
    protected $casts = [
        'email' => 'string',
        'notifica_correo' => 'boolean',
        'notifica_push' => 'boolean',
        'uso_datos' => 'boolean',
        'fecha_clave' => 'datetime:Y-m-d H:i:s'
    ];

    protected $hidden = [
        'password'
    ];

    protected $fillable = [
        'email',
        'password',
        'clave',
        'notifica_correo',
        'notifica_push',
        'uso_datos',
        'pin',
        'fecha_clave'
    ];

    public function usuario(){
        return $this->hasOne(Usuario::class, 'cuenta_id', 'id');
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        $usuario = $this->usuario;
        return [
            'usuario_id' => $usuario ? $usuario->id : null,
            'estado' => $usuario ? $usuario->estado?->nombre : 'pendiente',
            'rol' => $usuario ? $usuario->rol?->nombre : 'usuario'
        ];
    }

     /*
     * Verifica si la clave ha expirado
     * 
     * @return bool
     */
    public function hasExpired(): bool
    {
        $duracion = 10; // minutos que dura el código
        return now()->greaterThan(Carbon::parse($this->fecha_clave)->addMinutes($duracion));
}
    /**
     * Genera una clave aleatoria de 6 caracteres (números y letras)
     * Ejemplo: A3F7K2, B9D4E1
     */
    public static function generarClave(): string
    {
        $caracteres = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $clave = '';
        
        for ($i = 0; $i < 6; $i++) {
            $clave .= $caracteres[rand(0, strlen($caracteres) - 1)];
        }
        
        return $clave;
    }
    

    /**
     * Verifica si la clave es valida
     * 
     * @param string $inputClave - Clave ingresada por el usuario
     * @return bool - true si coincide
     */
    public function isValidClave(string $inputClave): bool
    {
        // Comparación sin distinción de mayúsculas/minúsculas
        // strcasecmp devuelve 0 si son iguales
        return strcasecmp($this->clave, $inputClave) === 0;
    }

    /**
     * Generar nueva clave y extender expiración
     * 
     * Se usa para "Reenviar clave"
     * 
     * @return string - nueva clave generada
     */
    public function regenerateClave(): string
    {
        // Generar nueva clave alfanúmerica de 6 caracteres
        $this->clave = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6));

        // Extender expiración a 10 minutos más
        $this->fecha_clave = Carbon::now();

        $this->save();
        return $this->clave;       
    }
}
