<?php
/**
 * Cliente API para comunicarse con el Backend Laravel
 * Tu Mercado SENA
 */

// URL base de la API de Laravel
define('API_BASE_URL', 'http://127.0.0.1:8000/api');

/**
 * Realiza una petición HTTP a la API
 * 
 * @param string $endpoint Endpoint de la API (sin /api/)
 * @param string $method Método HTTP (GET, POST, PATCH, DELETE)
 * @param array $data Datos a enviar en el body
 * @param string|null $token Token JWT para autenticación
 * @return array Respuesta de la API
 */
function apiRequest($endpoint, $method = 'GET', $data = [], $token = null) {
    $url = API_BASE_URL . $endpoint;
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    
    // Agregar token de autenticación si existe
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    switch (strtoupper($method)) {
        case 'POST':
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            break;
        case 'PATCH':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            break;
        case 'DELETE':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            break;
        case 'GET':
        default:
            if (!empty($data)) {
                $url .= '?' . http_build_query($data);
                curl_setopt($ch, CURLOPT_URL, $url);
            }
            break;
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        return [
            'success' => false,
            'error' => 'Error de conexión: ' . $error,
            'http_code' => 0
        ];
    }
    
    $decoded = json_decode($response, true);
    
    return [
        'success' => $httpCode >= 200 && $httpCode < 300,
        'data' => $decoded,
        'http_code' => $httpCode
    ];
}

/**
 * Guarda el token JWT en la sesión
 */
function saveToken($token, $expiresIn = 86400) {
    $_SESSION['jwt_token'] = $token;
    $_SESSION['token_expires'] = time() + $expiresIn;
}

/**
 * Obtiene el token JWT de la sesión
 */
function getToken() {
    if (isset($_SESSION['jwt_token']) && isset($_SESSION['token_expires'])) {
        if (time() < $_SESSION['token_expires']) {
            return $_SESSION['jwt_token'];
        }
        // Token expirado, limpiar
        unset($_SESSION['jwt_token']);
        unset($_SESSION['token_expires']);
    }
    return null;
}

/**
 * Verifica si hay un token válido
 */
function hasValidToken() {
    return getToken() !== null;
}

/**
 * Elimina el token de la sesión
 */
function clearToken() {
    unset($_SESSION['jwt_token']);
    unset($_SESSION['token_expires']);
}

// ============================================
// FUNCIONES DE AUTENTICACIÓN
// ============================================

/**
 * Inicia el proceso de registro (envía código al correo)
 */
function apiIniciarRegistro($email, $password, $passwordConfirmation, $nickname, $descripcion = '', $link = '', $imagen = '') {
    $data = [
        'email' => $email,
        'password' => $password,
        'password_confirmation' => $passwordConfirmation,
        'nickname' => $nickname,
        'rol_id' => 3,  // prosumer
        'estado_id' => 1,  // activo
        'device_name' => 'web'
    ];
    
    // Solo agregar campos opcionales si tienen valor
    if (!empty($descripcion)) {
        $data['descripcion'] = $descripcion;
    }
    if (!empty($link)) {
        $data['link'] = $link;
    }
    if (!empty($imagen)) {
        $data['imagen'] = $imagen;
    }
    
    return apiRequest('/auth/iniciar-registro', 'POST', $data);
}

/**
 * Completa el registro con el código de verificación
 */
function apiCompletarRegistro($cuentaId, $clave, $datosEncriptados) {
    $data = [
        'cuenta_id' => $cuentaId,
        'clave' => $clave,
        'datosEncriptados' => $datosEncriptados,
        'device_name' => 'web'
    ];
    
    return apiRequest('/auth/register', 'POST', $data);
}

/**
 * Inicia sesión
 */
function apiLogin($email, $password) {
    $data = [
        'email' => $email,
        'password' => $password,
        'device_name' => 'web'
    ];
    
    return apiRequest('/auth/login', 'POST', $data);
}

/**
 * Cierra sesión
 */
function apiLogout($allDevices = false) {
    $token = getToken();
    if (!$token) {
        return ['success' => false, 'error' => 'No hay sesión activa'];
    }
    
    $data = ['all_devices' => $allDevices];
    return apiRequest('/auth/logout', 'POST', $data, $token);
}

/**
 * Obtiene el usuario autenticado
 */
function apiGetMe() {
    $token = getToken();
    if (!$token) {
        return ['success' => false, 'error' => 'No hay sesión activa'];
    }
    
    return apiRequest('/auth/me', 'GET', [], $token);
}

/**
 * Obtiene el perfil público de un usuario
 */
function apiGetPerfilPublico($id) {
    return apiRequest("/auth/perfil-publico/{$id}", 'GET');
}

/**
 * Refresca el token
 */
function apiRefreshToken() {
    $token = getToken();
    if (!$token) {
        return ['success' => false, 'error' => 'No hay sesión activa'];
    }
    
    return apiRequest('/auth/refresh', 'POST', [], $token);
}

// ============================================
// FUNCIONES DE RECUPERACIÓN DE CONTRASEÑA
// ============================================

/**
 * Inicia el proceso de recuperación de contraseña
 */
function apiValidarCorreo($email) {
    $data = ['email' => $email];
    return apiRequest('/auth/recuperar-contrasena/validar-correo', 'POST', $data);
}

/**
 * Valida el código de recuperación
 */
function apiValidarClaveRecuperacion($cuentaId, $clave) {
    $data = [
        'cuenta_id' => $cuentaId,
        'clave' => $clave
    ];
    return apiRequest('/auth/recuperar-contrasena/validar-clave-recuperacion', 'POST', $data);
}

/**
 * Restablece la contraseña
 */
function apiReestablecerPassword($cuentaId, $password, $passwordConfirmation) {
    $data = [
        'cuenta_id' => $cuentaId,
        'password' => $password,
        'password_confirmation' => $passwordConfirmation
    ];
    return apiRequest('/auth/recuperar-contrasena/reestablecer-contrasena', 'PATCH', $data);
}

// ============================================
// FUNCIONES DE PERFIL
// ============================================

/**
 * Edita el perfil del usuario
 */
function apiEditarPerfil($userId, $data) {
    $token = getToken();
    if (!$token) {
        return ['success' => false, 'error' => 'No hay sesión activa'];
    }
    
    return apiRequest('/editar-perfil/' . $userId, 'PATCH', $data, $token);
}

// ============================================
// FUNCIONES DE BLOQUEADOS
// ============================================

/**
 * Obtiene los usuarios bloqueados
 */
function apiGetBloqueados() {
    $token = getToken();
    if (!$token) {
        return ['success' => false, 'error' => 'No hay sesión activa'];
    }
    
    return apiRequest('/bloqueados', 'GET', [], $token);
}

/**
 * Bloquea un usuario
 */
function apiBloquearUsuario($bloqueadorId, $bloqueadoId) {
    $token = getToken();
    if (!$token) {
        return ['success' => false, 'error' => 'No hay sesión activa'];
    }
    
    $data = [
        'bloqueador_id' => $bloqueadorId,
        'bloqueado_id' => $bloqueadoId
    ];
    
    return apiRequest('/bloqueados', 'POST', $data, $token);
}

/**
 * Desbloquea un usuario
 */
function apiDesbloquearUsuario($bloqueadoId) {
    $token = getToken();
    if (!$token) {
        return ['success' => false, 'error' => 'No hay sesión activa'];
    }
    
    return apiRequest('/bloqueados/' . $bloqueadoId, 'DELETE', [], $token);
}
?>
