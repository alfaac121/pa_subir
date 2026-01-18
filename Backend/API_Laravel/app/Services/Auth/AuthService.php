<?php

namespace App\Services\Auth;

use App\Contracts\Auth\Services\IAuthService;
use App\DTOs\Auth\Registro\RegisterDTO;
use App\DTOs\Auth\LoginDTO;
use App\Models\Usuario;
use App\Contracts\Auth\Repositories\ICuentaRepository;

;
use App\Contracts\Auth\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\JWTGuard;
use App\DTOs\Auth\recuperarContrasena\ClaveDto;
use App\DTOs\Auth\recuperarContrasena\CorreoDto;
use App\DTOs\Auth\recuperarContrasena\NuevaContrasenaDto;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Contracts\Auth\Services\IRegistroService;

/**
 * AuthService - Servicio de autenticación
 * 
 * RESPONSABILIDADES:
 * - Contiene toda la lógica de negocio relacionada con autenticación
 * - Coordina entre repositorios, modelos y validaciones
 * - Maneja la creación de tokens de Sanctum
 * - NO interactúa directamente con HTTP (eso es del Controller)
 * 
 * PATRÓN DE DISEÑO:
 * Este es un "Service" en la arquitectura de capas.
 * Los servicios contienen la lógica compleja que no pertenece
 * ni a los modelos ni a los controladores.
 * 
 * VENTAJAS:
 * - Reutilizable: puedes llamar estos métodos desde console, jobs, etc.
 * - Testeable: puedes hacer unit tests sin simular HTTP requests
 * - Mantenible: la lógica está en un solo lugar
 * - Cumple con Single Responsibility Principle (SOLID)
 */

class AuthService implements IAuthService
{
    /**
     * Constructor con inyección de dependencias 
     * 
     * Laravel automáticamente inyecta una instancia de UserRepository gracias
     * al RepositoryServiceProvider
     */
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private IRegistroService $registroService,
        private ICuentaRepository $cuentaRepository,
        private RecuperarContrasenaService $nuevaPasswordService,
        private JWTGuard $jwt
    )
    {}
    /**
     * Iniciar el proceso de registro, el usuario envia los datos, se obtiene el correo que el usuario ingreso
     * Se valida si el correo no esta en la base de datos y se envia el correo
     * 
     * @param RegisterDTO $dto - Datos del usuario a registrar
     * @return array - Resultado del proceso
     */
    public function iniciarRegistro(RegisterDTO $dto): array
    {
        $correoUsuario = $dto->email;
        $passwordUsuario = $dto->password;
        $inicioProceso =  $this->registroService->iniciarRegistro($correoUsuario, $passwordUsuario);

        if (!$inicioProceso['success']) {
            throw ValidationException::withMessages([
                'inicio_registro' => [$inicioProceso['message']]
                
            ]);
        }

        $datosEncriptados = encrypt($dto->toArray());
        
        return [
            'success' => $inicioProceso['success'],
            'message' => $inicioProceso['message'],
            'cuenta_id' => $inicioProceso['data']['cuenta_id'],
            'expira_en' => $inicioProceso['data']['expira_en'],
            'datosEncriptados' => $datosEncriptados,
        ];
        
    }

    /**
     * Terminar el proceso de registro priorizando las transacciones para que no haya datos volando
     * @param string $datosEncriptado - Datos del formulario encriptados
     * @param string $clave - Código que le llega al usuario a su correo
     * @param int $cuenta_id - ID de la cuenta que recibe el usuario en la respuesta JSON anterior
     * @param string $dispositivo - Dispositivo de donde ingreso el usuario
     * @return array{status: bool, data: array{user:Usuario, token: string, token_type: string, expires_in: int}}
     */
    public function completarRegistro(string $datosEncriptados, string $clave, int $cuenta_id, string $dispositivo): array
    {
        try {
            $registroTerminado = $this->registroService->terminarRegistro($datosEncriptados, $clave, $cuenta_id, $dispositivo);

            if (!$registroTerminado['success']) {
                Log::error('Error en el Registro del usuario', [
                    'cuenta_id' => $cuenta_id,
                    'dispositivo' => $dispositivo,
                    'Archivo' => 'RegistroService.php'
                ]);
                throw new Exception('Error al registrar usuario', 401);
            }

           return $registroTerminado;
            
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Inicio de sesión
     * 
     * PROCESO:
     * 1. Busca el usuario por email
     * 2. Verifica que exista
     * 3. Verifica que la contraseña sea correcta
     * 4. Verifica que el usuario esté activo (no eliminado)
     * 5. Revoca tokens anteriores del mismo dispositivo (seguridad)
     * 6. Crea un nuevo token
     * 7. Actualiza fecha de última actividad (RF010, RNF009)
     * 8. Retorna usuario y token
     * 
     * @param LoginDTO $dto - Credenciales de login
     * @return array{user: Usuario, token: string, login:string}
     * @throws ValidationException - Si las credenciales son inválidas
     */
    public function login(LoginDTO $dto): array
    {
        try{
            Log::info('Inicio del proceso Login', [
                'email' => $dto->email
            ]);

            $cuentaRegistrada = $this->cuentaRepository->findByCorreo($dto->email);

            if(!$cuentaRegistrada) {
                Log::warning('Correo no encontrado en la base de datos', [
                    'correo' => $dto->email
                ]);

                throw ValidationException::withMessages([
                    'login' => ['Correo o contraseña incorrectos']
                ]);
            }
    
            // Válidar si la contraseña es correcta
            // Hash::check() compara la contraseña en texto plano con el hash almacenado
            // Si no coincide, lanzamos excepción con el mismo mensaje genérico
            if (!Hash::check($dto->password, $cuentaRegistrada->password)) {
                Log::warning('Contraseña Incorrecta', [
                    'password' => null
                ]);

                throw ValidationException::withMessages([
                    'login' => ['Correo o contraseña incorrectos']
                ]);
            }
            
            $user = $this->userRepository->findByIdCuenta($cuentaRegistrada->id);
            // Válidar que el usuario este activo
            // estado_id: 1 = activo, 2 = invisible, 3 = eliminado
            if ($user->estado_id === 3) {
                throw ValidationException::withMessages([
                    'login' => ['Esta cuenta ha sido desactivada']
                ]);
            }
    
            // Si el un usuario prosumer intenta entrar a desktop (Unico del admin y master)
            // Lanzar una excepción
            if ($user->rol_id === 1 && $dto->device_name === 'desktop'){
                throw ValidationException::withMessages([
                    'login' => ['No cuentas con el rol para acceder']
                ]);
            }
            
            // Crear un nuevo token
            $token = $this->jwt->fromUser($cuentaRegistrada);
            $this->jwt->setToken($token); 
            $payload = $this->jwt->getPayload();
            $jti = $payload->get('jti');
            $expiresIn = $this->jwt->factory()->getTTL() * 60;
            
            return DB::transaction(function () use ($cuentaRegistrada, $dto, $jti, $user, $token, $expiresIn) {

                DB::table('tokens_de_sesion')
                    ->where('cuenta_id', $cuentaRegistrada->id)
                    ->where('dispositivo', $dto->device_name)
                    ->delete();

                DB::table('tokens_de_sesion')->insert([
                    'cuenta_id'   => $cuentaRegistrada->id,
                    'dispositivo' => $dto->device_name,
                    'jti'         => $jti,
                    'ultimo_uso'  => Carbon::now()
                ]);

                DB::table('usuarios')
                    ->where('id', $cuentaRegistrada->id)
                    ->update([
                        'fecha_reciente' => Carbon::now()
                    ]);

                // 3. Return anidado (resultado final del servicio)
                return [
                    'success' => true,
                    'user' => $user,
                    'token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => $expiresIn,
                ];
            });

        } catch (Exception $e) {
            Log::error('Error al loguearse', [
                'error' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine()
            ]);

            throw $e;
        }
    }

    /**
     * Iniciar el proceso de cambio de contraseña en donde se le enviara al 
     * Usuario un correo con el código de recuperación
     * @param CorreoDto $dto - Correo del usuario
     * @return array{message: string, correo: string, expira_en: string} $data
     */
    public function inicioNuevaPassword(CorreoDto $dto): array
    {
        $inicioProceso = $this->nuevaPasswordService->iniciarProceso($dto->email);

        if (!$inicioProceso['success']) {
            throw ValidationException::withMessages([
                'error' => [$inicioProceso['message']]
            ]);
        }

        return [
            'message' => $inicioProceso['message'],
            'cuenta_id' => $inicioProceso['cuenta_id'],
            'expira_en' => $inicioProceso['expira_en']
        ];
    }

    /**
     * Validar el código de recuperación del usuario
     * @param int $id_cuenta - Id del correo para válidar que la clave de la BD corresponda a la ingresada por el usuario
     * @param ClaveDto $dto - Clave que ingresa el usuario
     * @return array{success: bool, message:string, id_usuario: int, clave_verificada: bool}
     */
    public function validarClaveRecuperacion(int $cuenta_id, ClaveDto $dto): array
    {
        $validarClave = $this->nuevaPasswordService->verificarClaveContrasena($cuenta_id, $dto->clave);

        if(!$validarClave['success']) {
            throw ValidationException::withMessages([
                'error' => [$validarClave['message']]
            ]);
        }

        return [
            'success' => $validarClave['success'],
            'message' => $validarClave['message'],
            'cuenta_id' => $validarClave['cuenta_id'],
            'clave_verificada' => $validarClave['clave_verificada']
        ];
    }

    /**
     * Lógica para cambiar el password del usuario
     * 
     * @param int $cuenta_id - Id de la cuenta a cambiar la contraseña
     * @param NuevaContrasenaDto $dto - Nueva contraseña del usuario 
     * @return array{success: bool, message:string}
     */
    public function nuevaPassword(int $cuenta_id, NuevaContrasenaDto $dto): array {

        $resultado = $this->nuevaPasswordService->actualizarPassword($cuenta_id, $dto->password);

        return $resultado;
    }

    /**
     * Cerrar sesión del usuario 
     * 
     * PROCESO:
     * 1. Recibe el usaurio autenticado (Viene del middleware auth:sanctum)
     * 2. Revoca el token actual
     * 3. Opcionalmente puede revocar todos los tokens del usuario.
     * 
     * @param Usuario $user - Usuario autenticado
     * @param bool $allDevices - true, cierra sesión en todos los dispositivos
     * @return bool - true si se cerro correctamente
     */
    public function logout(bool $allDevice = false): array
    {
        try{
            $token = $this->jwt->getToken();

            if (!$token) {
                return [
                    'status' => false,
                    'message' => 'No hay token activo'
                ];
            }

            try {
                $this->jwt->setToken($token); 
                $payload = $this->jwt->getPayload();
                $jti = $payload->get('jti');
                $cuenta_id = $payload->get('sub');
            
            } catch (Exception $e) {
                // Logs para debbugin
                Log::warning('Token Inválido o expirado',[
                    'archivo' => $e->getFile(),
                    'linea' => $e->getLine()
                ]);

                return [
                    'status' => false,
                    'message' => 'Token Inválido o expirado'
                ];
            }

            DB::beginTransaction();

            if ($allDevice) {
                $revokedCount = DB::table('tokens_de_sesion')
                    ->where('cuenta_id', $cuenta_id)
                    ->delete();
            } else {
                $revokedCount = DB::table('tokens_de_sesion')
                    ->where('jti', $jti)
                    ->delete();
            }

            DB::commit();

            $this->jwt->invalidate($token);
            return [
                'status' => true,
                'message' => 'Sesión(es) cerrada(s) exitosamente',
                'revoked_count' => $revokedCount
            ];

        } catch (Exception $e) {
            Log::error('Error capturado', [
                'file' => $e->getFile(),
            ]);
            throw $e;
        }
    }

    /**
     * Refrescar token JWT
     * 
     * NUEVO MÉTODO (No existe en Sanctum)
     * 
     * PROPÓSITO:
     * Cuando un token está por expirar, el cliente puede "refrescarlo"
     * para obtener uno nuevo sin hacer login otra vez
     * 
     * PROCESO:
     * 1. Recibe el token actual (aunque esté casi expirado)
     * 2. Verifica que sea válido
     * 3. Genera un nuevo token con nueva fecha de expiración
     * 4. Opcionalmente invalida el token anterior (para que no se use)
     * 
     * CONFIGURACIÓN:
     * - refresh_ttl en config/jwt.php define cuánto tiempo después
     *   de expirado aún se puede refrescar (grace period)
     * - Por defecto: 2 semanas
     * 
     * USO EN EL FRONTEND:
     * Antes de que el token expire (ej: 5 min antes), hacer:
     * POST /api/auth/refresh con el token actual
     * 
     * @return array - Nuevo token y metadata
     * @throws JWTException - Si el token no se puede refrescar
     */
    public function refresh(): array
    {
        try {
            // Refresca el token actual
            $newToken = $this->jwt->refresh();

            // Setear el nuevo token para poder leer su payload
            $this->jwt->setToken($newToken);
            $payload = $this->jwt->getPayload();

            // Datos importantes del nuevo token
            $jti = $payload->get('jti');
            $cuentaId = $payload->get('sub'); // ID de la cuenta
            $expiresIn = $this->jwt->factory()->getTTL() * 60;

            // Actualizar token en BD
            DB::table('tokens_de_sesion')
                ->where('cuenta_id', $cuentaId)
                ->update([
                    'jti' => $jti,
                    'ultimo_uso' => Carbon::now(),
                ]);

            return [
                'token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => $expiresIn,
            ];

        } catch (JWTException $e) {
            throw ValidationException::withMessages([
                'token' => ['No se pudo refrescar el token'],
            ]);
        }
    }

    
    
    /**
     * Obtener información del usuario autenticado
     * 
     * @param Usuario $user - Usuario autenticado
     * @return Usuario - Mismo usuario pero con relaciones cargadas si es necesario
     */
    public function getCurrentUser(Usuario $user): Usuario
    {
        // Retornar el usuario 
        return $user;
    }
    
    /**
     * Verificar si un usuario esta "Recientemente conectado" (RNF009)
     * 
     * @param Usuario $user - Usuario a verificar
     * @return bool - true si estuvo activo
    */
    public function isRecentlyActive(Usuario $user): bool
    {
        // now()->subDay-> Retorna la fecha/hora de hace 24 horas
        // isAfter() Verifica si la fecha_reciente es porsterior a las 24 horas
        return Carbon::parse($user->fecha_reciente)->isAfter(now()->subDay());
    }
}
