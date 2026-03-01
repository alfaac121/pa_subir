/**
 * Configuración de APIs para JavaScript
 * 
 * Este archivo controla qué sistema de API se utiliza en el frontend
 * Debe estar sincronizado con config_api.php
 */

// ============================================
// CONFIGURACIÓN PRINCIPAL
// ============================================

const API_CONFIG = {
    // Usar API de Laravel (true) o PHP nativa (false)
    USE_LARAVEL: false,
    
    // URL base de la API de Laravel
    LARAVEL_URL: 'http://localhost:8000/api/',
    
    // URL base de la API de PHP (se obtiene de window.BASE_URL)
    get PHP_URL() {
        return (window.BASE_URL || '') + 'api/';
    },
    
    // Obtener URL activa según configuración
    get ACTIVE_URL() {
        return this.USE_LARAVEL ? this.LARAVEL_URL : this.PHP_URL;
    }
};

// ============================================
// FUNCIONES HELPER
// ============================================

/**
 * Obtiene la URL de la API según la configuración
 * 
 * @returns {string} URL base de la API activa
 */
function getApiUrl() {
    return API_CONFIG.ACTIVE_URL;
}

/**
 * Obtiene la URL completa de un endpoint específico
 * 
 * @param {string} endpoint - Nombre del endpoint (ej: 'productos.php' o 'productos')
 * @returns {string} URL completa del endpoint
 */
function getApiEndpoint(endpoint) {
    const baseUrl = getApiUrl();
    
    // Si usa Laravel, remover la extensión .php si existe
    if (API_CONFIG.USE_LARAVEL) {
        endpoint = endpoint.replace('.php', '');
    }
    
    return baseUrl + endpoint;
}

/**
 * Verifica si se está usando la API de Laravel
 * 
 * @returns {boolean}
 */
function isUsingLaravelApi() {
    return API_CONFIG.USE_LARAVEL;
}

/**
 * Verifica si se está usando la API de PHP
 * 
 * @returns {boolean}
 */
function isUsingPhpApi() {
    return !API_CONFIG.USE_LARAVEL;
}

/**
 * Obtiene los headers necesarios para las peticiones a la API
 * 
 * @returns {Object} Headers para fetch
 */
function getApiHeaders() {
    const headers = {
        'Accept': 'application/json'
    };
    
    // Si usa Laravel y hay token de autenticación, agregarlo
    if (API_CONFIG.USE_LARAVEL) {
        const token = localStorage.getItem('api_token');
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }
        headers['Content-Type'] = 'application/json';
    }
    
    return headers;
}

/**
 * Mapeo de endpoints entre PHP y Laravel
 * Útil cuando los nombres de endpoints difieren entre sistemas
 */
const ENDPOINT_MAPPING = {
    // Autenticación
    'login.php': 'auth/login',
    'register.php': 'auth/register',
    'logout.php': 'auth/logout',
    
    // Productos
    'productos.php': 'productos',
    'crear_producto.php': 'productos/crear',
    'editar_producto.php': 'productos/editar',
    'eliminar_producto.php': 'productos/eliminar',
    
    // Chats
    'chats.php': 'chats',
    'enviar_mensaje.php': 'mensajes/enviar',
    'obtener_mensajes.php': 'mensajes/obtener',
    'eliminar_chat.php': 'chats/eliminar',
    
    // Confirmaciones y Devoluciones
    'solicitar_confirmacion.php': 'transacciones/solicitar-confirmacion',
    'responder_confirmacion.php': 'transacciones/responder-confirmacion',
    'solicitar_devolucion.php': 'transacciones/solicitar-devolucion',
    'responder_devolucion.php': 'transacciones/responder-devolucion',
    
    // Denuncias
    'denunciar_usuario.php': 'denuncias/crear',
    
    // Usuarios
    'perfil.php': 'usuarios/perfil',
    'editar_perfil.php': 'usuarios/editar',
    
    // Otros
    'toggle_silencio.php': 'chats/toggle-silencio',
    'cerrar_chats_automatico.php': 'chats/cerrar-automatico',
};

/**
 * Obtiene el endpoint correcto según el sistema de API activo
 * 
 * @param {string} phpEndpoint - Nombre del endpoint PHP
 * @returns {string} Endpoint correcto según la configuración
 */
function mapEndpoint(phpEndpoint) {
    if (!API_CONFIG.USE_LARAVEL) {
        return phpEndpoint;
    }
    
    return ENDPOINT_MAPPING[phpEndpoint] || phpEndpoint;
}

/**
 * Realiza una petición a la API con la configuración correcta
 * 
 * @param {string} endpoint - Endpoint a llamar
 * @param {Object} options - Opciones de fetch
 * @returns {Promise} Promesa con la respuesta
 */
async function apiRequest(endpoint, options = {}) {
    const url = getApiEndpoint(mapEndpoint(endpoint));
    
    // Configurar headers
    const headers = {
        ...getApiHeaders(),
        ...(options.headers || {})
    };
    
    // Si usa Laravel y el body no es FormData, convertir a JSON
    let body = options.body;
    if (API_CONFIG.USE_LARAVEL && body && !(body instanceof FormData)) {
        if (typeof body === 'string' && body.includes('=')) {
            // Convertir URL encoded a JSON
            const params = new URLSearchParams(body);
            const jsonBody = {};
            for (const [key, value] of params) {
                jsonBody[key] = value;
            }
            body = JSON.stringify(jsonBody);
        } else if (typeof body === 'object') {
            body = JSON.stringify(body);
        }
    }
    
    // Realizar petición
    const response = await fetch(url, {
        ...options,
        headers,
        body
    });
    
    // Parsear respuesta
    const data = await response.json();
    
    return {
        ok: response.ok,
        status: response.status,
        data
    };
}

/**
 * Obtiene información sobre la configuración actual de la API
 * 
 * @returns {Object} Información de configuración
 */
function getApiInfo() {
    return {
        using_laravel: API_CONFIG.USE_LARAVEL,
        api_url: getApiUrl(),
        api_type: API_CONFIG.USE_LARAVEL ? 'Laravel' : 'PHP Nativo',
        laravel_url: API_CONFIG.LARAVEL_URL,
        php_url: API_CONFIG.PHP_URL,
    };
}

// ============================================
// LOGGING (solo en desarrollo)
// ============================================

if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    console.log('🔧 API Configuration:', getApiInfo());
}

// ============================================
// EXPORTAR (si se usa como módulo)
// ============================================

if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        API_CONFIG,
        getApiUrl,
        getApiEndpoint,
        isUsingLaravelApi,
        isUsingPhpApi,
        getApiHeaders,
        mapEndpoint,
        apiRequest,
        getApiInfo
    };
}
