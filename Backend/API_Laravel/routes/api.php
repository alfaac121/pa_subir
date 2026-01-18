<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UsuarioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/**
 * RUTAS PÚBLICAS (Sin autenticación)
 * Cualquiera puede entrar a ellas
 */
Route::prefix('auth')->group(function()  {      

    // POST api/auth/iniciar-registro
    // Inicia el proceso de registro en donde se le envia al usuario un código de verificación 
    // A su correo electronico
    Route::post('/iniciar-registro', [AuthController::class, 'iniciarRegistro']);


    // POST /api/auth/register
    // Valida que el código enviado sea correcto y si es asi
    // Registra al usuario en el sistema
    Route::post('/register', [AuthController::class, 'register']);

    // POST /api/auth/login
    // Inicia sesión y retornar un token
    Route::post('/login', [AuthController::class, 'login']);

    Route::prefix('recuperar-contrasena')->group(function() {
        /**
         * Endpoint que valida el correo del usuario en la base de datos y envia el 
         * Código de recuperación al correo del usuario
         * 
         * POST
         *  RUTA: /api/auth/recuperar-contrasena/validar-correo
         * 
         */
        Route::post('/validar-correo', [AuthController::class, 'iniciarProcesoPassword']);
    
        /**
         * Endpoint que valida el código que ingresa el usuario al front-end
         * 
         * POST
         * RUTA: /api/auth/recuperar-contrasena/validar-clave-recuperacion
         */
        Route::post('/validar-clave-recuperacion', [AuthController::class, 'validarClavePassword']);
    
        /**
         * Endpoint que recibe la nueva contraseña del usuario y actualiza en la base 
         * De datos
         * 
         *PATCH
         *RUTA: /api/auth/recuperar-contrasena/reestablecer-contrasena
         */
        Route::patch('/reestablecer-contrasena', [AuthController::class, 'reestablecerPassword']);
    });
    
    // Perfil público de cualquier usuario
    Route::get('/perfil-publico/{id}', [UsuarioController::class, 'verPerfilPublico']);

});

/**
 * RUTAS PROTEGIDAS (Requieren autenticación)
 * 
 * El middleware personalizado "jwtVerify" verifica el token.
 * 
 */
Route::middleware('jwtVerify')->group(function (){

    // === AUTENTICACIÓN ===
    Route::prefix('auth')->group(function () {
        // Cerrar sesión
        Route::post('/logout', [AuthController::class, 'logout']);

        // Refrescar token
        Route::post('/refresh', [AuthController::class, 'refresh']);

        // Obtener usuario autenticado
        Route::get('/me', [AuthController::class, 'me']);
    });

        // === EDITAR PERFIL ===
    Route::patch("/editar-perfil/{id}", [UsuarioController::class, 'update']);

    // === BLOQUEADOS    ===
    Route::get('bloqueados', [UsuarioController::class, 'obtenerBloqueadosPorUsuario']);
    Route::post('bloqueados', [UsuarioController::class, 'bloquearUsuario']);
    Route::delete('bloqueados/{bloqueado_id}', [UsuarioController::class, 'desbloquearUsuario']);

});


/**
 * RUTAS DE PRUEBA
 * 
 * GET /api/ping
 */
Route::get('/ping', function () {
    return response()->json([
        'message' => 'pong',
        'timestamp' => now()->toIso8601String()
    ]);
});
