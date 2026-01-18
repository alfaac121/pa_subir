<?php

namespace App\Services\Auth;

use App\Contracts\Auth\Repositories\ICuentaRepository;
use App\Contracts\Auth\Repositories\UserRepositoryInterface;
use App\Contracts\Auth\Services\IRegistroService;
use App\DTOs\Auth\Registro\RegisterDTO;
use App\Models\Cuenta;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\JWTGuard;



class RegistroService implements IRegistroService
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private ICuentaRepository $cuentaRepository,
        private UserRepositoryInterface $userRepository,
        private CorreoService $correoService,
        private JWTGuard $jwt 
    )
    {}

    /**
     * PASO 1: Iniciar el proceso de registro
     * - Valida que el correo sea institucional
     * - Genera una clave de verificación 
     * - Guarda el correo y la clave en la BD
     * - Envía el correo con la clave
     * 
     * @param string $email - Correo del usuario a registrar
     * @param string $password - Password del usuario
     * @return array {success: bool, message: string}
     */
    public function iniciarRegistro(string $email, string $password): array
    {
        DB::beginTransaction();
    
        $cuentaRegistrada = null;
        try {
            Log::info('Iniciando proceso de registro en el servicio',[
                'correo' => $email
            ]);
            // Si el correo tiene un registro vigente, NO crees uno nuevo
            if ($this->cuentaRepository->isCuentaRegistrada($email)) {
                Log::warning('Usuario ya cuenta con un código de registro', [
                    'correo' => $email
                ]);
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Ya se envió un código. Revisa tu correo.'
                ];
            }

            // Si existe pero expirado → actualizar clave
            $cuentaRegistrada = $this->cuentaRepository->findByCorreo($email);

            $clave = Cuenta::generarClave();

            if ($cuentaRegistrada) {
                // actualizar y renovar fecha
                $cuentaRegistrada = $this->cuentaRepository->actualizarClave($cuentaRegistrada, $clave);
            } else {
                // crear nuevo registro 
                $cuentaRegistrada = $this->cuentaRepository->createOrUpdate($email, $clave, $password);
            }

            if (!$cuentaRegistrada) {
                // Esto puede ocurrir si el createOrUpdate falla en crear el registro.
                Log::error('Fallo crítico: El repositorio no devolvió la Cuenta.', ['correo' => $email]);
                // Lanzar una excepción de fallo de registro
                throw new \Exception('Fallo al crear o actualizar el registro de la cuenta.');
            }

            // Enviar correo
            $emailEnviado = $this->correoService->enviarCodigoVerificacion($cuentaRegistrada->email,$clave);

            if (!$emailEnviado) {
                Log::error('Erro en el servicio de registro',[
                    'correo' => $email
                ]);
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'No se pudo enviar el código. Intenta más tarde.',
                    'data' => null
                ];
            }

            Log::info('Inicio de registro realizado correctamente',[
                'correo' => $cuentaRegistrada->email
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Código enviado correctamente',
                'data' => [
                    'cuenta_id' => $cuentaRegistrada->id,
                    'expira_en' => $cuentaRegistrada->fecha_clave->addMinutes(10)->toDateTimeString(),
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error iniciarRegistro', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'cuenta_id' => $cuentaRegistrada->id ?? 'N/A'
            ]);

            throw $e;
        }    
    }

    /**
     * PASO 2: Verificar el código de verificación
     * - Busca el correo en la tabla correos
     * - Valida que la clave coincida y no haya expirado
     * 
     * @param string $correoExistente - Correo para validación
     * @param string $clave - Código que llegara desde el front-end
     * @return array ['success' => bool, 'message' => string]
     */
    public function verificarClave(string $correoExistente, string $clave): array
    {
        try {
            // Buscar el correo en la base de datos
            $correoExistente = $this->cuentaRepository->findByCorreo($correoExistente);

            if (!$correoExistente) {
                throw new ModelNotFoundException("El registro de verificación para el correo {$correoExistente} no fue encontrado.");
            }

            // Verificar si la clave ha expirado
            if ($correoExistente->hasExpired()) {
                return [
                    'success' => false,
                    'message' => 'La clave ha expirado. Solicita una nueva clave',
                    'data' => null,
                ];
            }

            // Verificar que la clave coincida
            if (!$correoExistente->isValidClave($clave)) {
                return [
                    'success' => false,
                    'message' => 'La clave es incorrecta, intenta nuevamente',
                    'data' => null
                ];
            }

            // Clave verificada correctamente
            return [
                'success' => true,
                'message' => 'Código verificado correctamente',
                'data' => [
                    'correo' => $correoExistente,
                    'clave_verificada' => true
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Error al verificar clave', [
                'correo' => $correoExistente ?? null,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Terminar el proceso de registro priorizando las transacciones para que no haya datos volando
     * @param string $datosEncriptado - Datos del formulario encriptados
     * @param string $clave - Código que le llega al usuario a su correo
     * @param int $cuenta_id - ID de la cuenta que recibe el usuario en la respuesta JSON anterior
     * @param string $dispositivo - Dispositivo de donde ingreso el usuario
     * @return array{status: bool, data: array{user:Usuario, token: string, token_type: string, expires_in: int}}
     */

    public function terminarRegistro(string $datosEncriptados, string $clave, int $cuenta_id, string $dispositivo): array
    {
        try {
            $data = decrypt($datosEncriptados);
            $dto = RegisterDTO::fromArray($data);
            $cuenta = $this->cuentaRepository->findById($cuenta_id);
            $registro = $this->verificarClave($dto->email, $clave);
    
            if (!$registro['success']) {
                throw ValidationException::withMessages(['clave' => [$registro['message']]]);
            }

            // Si el usuario ya existe, simplemente loguearlo y devolver sus datos
            if ($this->userRepository->exists($cuenta_id)) {
                $usuarioExistente = $this->userRepository->findByIdCuenta($cuenta_id);
                $token = $this->jwt->fromUser($cuenta);
                
                return [
                    'success' => true,
                    'data' => [
                        'user' => $usuarioExistente,
                        'token' => $token,
                        'token_type' => 'bearer',
                        'expires_in' => $this->jwt->factory()->getTTL() * 60
                    ]
                ];
            }

            // Iniciar transacción
            DB::beginTransaction();

            $usuario = $this->userRepository->create([
                'cuenta_id' => $cuenta->id,
                'nickname' => $dto->nickname,
                'imagen' => $dto->imagen ?? '',
                'descripcion' => $dto->descripcion ?? '',
                'link' => $dto->link ?? '',
                'rol_id' => $dto->rol_id ?? 3,
                'estado_id' => $dto->estado_id ?? 1
            ]);

            if (!$usuario) {
                Log::error('Error Registro Service: terminarRegistro');
                if (DB::transactionLevel() > 0) {
                    DB::rollBack();
                }
                throw new \Exception('Error al crear usuario');
            }

            $token = $this->jwt->fromUser($cuenta);
            $this->jwt->setToken($token);
            $payload = $this->jwt->getPayload();
            $jti = $payload->get('jti');
            $expiresIn = $this->jwt->factory()->getTTL() * 60;

            $registroToken = DB::table('tokens_de_sesion')->insert([
                'cuenta_id' => $cuenta->id,
                'dispositivo' => $dispositivo,
                'jti' => $jti,
                'ultimo_uso' => Carbon::now()
            ]);

            if (!$registroToken) {
                Log::error('Error al registrar el token de inicio de sesión en la tabla');

                if (DB::transactionLevel() > 0) {
                    DB::rollBack();
                    throw new \Tymon\JWTAuth\Exceptions\JWTException("Error al registrar token de registro");
                }
            }

            // Si todo fue exitoso envia los datos a la BD
            DB::commit();

            return [
                'success' => true,
                'data' => [
                    'user' => $usuario,
                    'token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => $expiresIn
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Error en RegistroService@terminarRegistro: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
 