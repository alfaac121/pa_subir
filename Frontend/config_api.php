<?php
/**
 * Configuración de APIs
 * 
 * Este archivo controla qué sistema de API se utiliza en el frontend
 */

// ============================================
// CONFIGURACIÓN PRINCIPAL
// ============================================

/**
 * Usar API de Laravel
 * 
 * true  = Usa las APIs de Laravel (Backend/API_Laravel)
 * false = Usa las APIs de PHP nativas (Frontend/api)
 */
define('USE_LARAVEL_API', false);

/**
 * URL base de la API de Laravel
 * Ajusta esta URL según tu configuración de Laravel
 */
define('LARAVEL_API_URL', 'http://localhost:8000/api/');

/**
 * URL base de la API de PHP
 * Por defecto usa la carpeta api del frontend
 */
define('PHP_API_URL', getBaseUrl() . 'api/');

// ============================================
// FUNCIONES HELPER
// ============================================

/**
 * Obtiene la URL de la API según la configuración
 * 
 * @return string URL base de la API activa
 */
function getApiUrl() {
    return USE_LARAVEL_API ? LARAVEL_API_URL : PHP_API_URL;
}

/**
 * Obtiene la URL completa de un endpoint específico
 * 
 * @param string $endpoint Nombre del endpoint (ej: 'productos.php' o 'productos')
 * @return string URL completa del endpoint
 */
function getApiEndpoint($endpoint) {
    $baseUrl = getApiUrl();
    
    // Si usa Laravel, remover la extensión .php si existe
    if (USE_LARAVEL_API) {
        $endpoint = str_replace('.php', '', $endpoint);
    }
    
    return $baseUrl . $endpoint;
}

/**
 * Verifica si se está usando la API de Laravel
 * 
 * @return bool
 */
function isUsingLaravelApi() {
    return USE_LARAVEL_API;
}

/**
 * Verifica si se está usando la API de PHP
 * 
 * @return bool
 */
function isUsingPhpApi() {
    return !USE_LARAVEL_API;
}

/**
 * Obtiene los headers necesarios para las peticiones a la API
 * 
 * @return array Headers para fetch/curl
 */
function getApiHeaders() {
    $headers = [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json'
    ];
    
    // Si usa Laravel y hay token de autenticación, agregarlo
    if (USE_LARAVEL_API && isset($_SESSION['api_token'])) {
        $headers['Authorization'] = 'Bearer ' . $_SESSION['api_token'];
    }
    
    return $headers;
}

/**
 * Mapeo de endpoints entre PHP y Laravel
 * Útil cuando los nombres de endpoints difieren entre sistemas
 */
function getEndpointMapping() {
    return [
        // Autenticación
        'login.php' => 'auth/login',
        'register.php' => 'auth/register',
        'logout.php' => 'auth/logout',
        
        // Productos
        'productos.php' => 'productos',
        'crear_producto.php' => 'productos/crear',
        'editar_producto.php' => 'productos/editar',
        'eliminar_producto.php' => 'productos/eliminar',
        
        // Chats
        'chats.php' => 'chats',
        'enviar_mensaje.php' => 'mensajes/enviar',
        'obtener_mensajes.php' => 'mensajes/obtener',
        'eliminar_chat.php' => 'chats/eliminar',
        
        // Confirmaciones y Devoluciones
        'solicitar_confirmacion.php' => 'transacciones/solicitar-confirmacion',
        'responder_confirmacion.php' => 'transacciones/responder-confirmacion',
        'solicitar_devolucion.php' => 'transacciones/solicitar-devolucion',
        'responder_devolucion.php' => 'transacciones/responder-devolucion',
        
        // Denuncias
        'denunciar_usuario.php' => 'denuncias/crear',
        
        // Usuarios
        'perfil.php' => 'usuarios/perfil',
        'editar_perfil.php' => 'usuarios/editar',
        
        // Otros
        'toggle_silencio.php' => 'chats/toggle-silencio',
        'cerrar_chats_automatico.php' => 'chats/cerrar-automatico',
    ];
}

/**
 * Obtiene el endpoint correcto según el sistema de API activo
 * 
 * @param string $phpEndpoint Nombre del endpoint PHP
 * @return string Endpoint correcto según la configuración
 */
function mapEndpoint($phpEndpoint) {
    if (!USE_LARAVEL_API) {
        return $phpEndpoint;
    }
    
    $mapping = getEndpointMapping();
    return isset($mapping[$phpEndpoint]) ? $mapping[$phpEndpoint] : $phpEndpoint;
}

// ============================================
// INFORMACIÓN DEL SISTEMA
// ============================================

/**
 * Obtiene información sobre la configuración actual de la API
 * 
 * @return array Información de configuración
 */
function getApiInfo() {
    return [
        'using_laravel' => USE_LARAVEL_API,
        'api_url' => getApiUrl(),
        'api_type' => USE_LARAVEL_API ? 'Laravel' : 'PHP Nativo',
        'laravel_url' => LARAVEL_API_URL,
        'php_url' => PHP_API_URL,
    ];
}

?>
