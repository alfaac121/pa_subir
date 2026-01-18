<?php

// =========================================================
// CONFIGURACIÓN DE LA BASE DE DATOS Y TIEMPO
// =========================================================

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tu_mercado_sena');

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =========================================================
// FUNCIONES DE CONEXIÓN Y UTILIDAD
// =========================================================

/**
 * Conexión a la base de datos
 */
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die("Error de conexión: " . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");
        // Establece la zona horaria de la conexión SQL para que coincida con PHP
        $conn->query("SET time_zone = '-05:00'");
        return $conn;
    } catch (Exception $e) {
        die("Error de conexión: " . $e->getMessage());
    }
}
/**
 * Obtiene la ruta completa del avatar o el avatar por defecto
 */
/**
 * Obtiene la ruta completa del avatar o el avatar por defecto
 */
function getAvatarUrl($imagen) {
    if (empty($imagen)) {
        return 'assets/images/avatars/defa.jpg';
    }
    
    // Si ya trae la ruta, no la repetimos
    if (strpos($imagen, 'assets/images/avatars/') === 0) {
        $fullPath = $imagen;
    } else {
        $fullPath = 'assets/images/avatars/' . $imagen;
    }

    if (file_exists($fullPath)) {
        return $fullPath;
    }
    
    return 'assets/images/avatars/defa.jpg';
}
/**
 * Verifica si el usuario está logueado
 */
function isLoggedIn() {
    return isset($_SESSION['usuario_id']);
}

/**
 * Obtener información del usuario actual
 */
function getCurrentUser() {
    if (!isset($_SESSION['usuario_id'])) {
        return null;
    }

    $conn = getDBConnection();
    $id = $_SESSION['usuario_id'];

   $query = "
        SELECT 
            u.id,
            u.nickname,
            u.imagen,
            u.descripcion,
            u.link,
            u.estado_id,
            c.email AS correo
        FROM usuarios u
        INNER JOIN cuentas c ON u.cuenta_id = c.id
        WHERE u.id = ?
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $stmt->close();
    $conn->close();

    return $user ?: null;;
}


function isSellerFavorite($votante_id, $vendedor_id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id FROM favoritos WHERE votante_id = ? AND votado_id = ?");
    $stmt->bind_param("ii", $votante_id, $vendedor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}
function forceLightTheme() {
    echo "<script>
        localStorage.setItem('theme', 'light');
        document.documentElement.setAttribute('data-theme', 'light');
    </script>";
}

/**
 * Sanitizar entrada
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Formatear precio (Ej: 1.234.567 COP)
 */
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . ' COP';
}


// =========================================================
// FUNCIONES DE FECHA Y AVATAR
// =========================================================

/**
 * Formatea un timestamp de base de datos a tiempo relativo (Ej: hace 5 minutos)
 */
function formato_tiempo_relativo($timestamp_db) {
    // Configurar la zona horaria del servidor (¡MUY IMPORTANTE!)
    date_default_timezone_set('America/Bogota'); 
    
    $tiempo_mensaje = strtotime($timestamp_db);
    $tiempo_actual = time();
    $diferencia = $tiempo_actual - $tiempo_mensaje;

    $segundos_por_minuto = 60;
    $segundos_por_hora = 3600;
    $segundos_por_dia = 86400;

    if ($diferencia < 30) {
        return "Ahora";
    } elseif ($diferencia < $segundos_por_minuto) {
        return "hace " . $diferencia . " segundos";
    } elseif ($diferencia < ($segundos_por_minuto * 60)) {
        // Minutos
        $minutos = round($diferencia / $segundos_por_minuto);
        if ($minutos == 1) {
            return "hace 1 minuto";
        }
        return "hace " . $minutos . " minutos";
    } elseif ($diferencia < $segundos_por_dia) {
        // Horas
        $horas = round($diferencia / $segundos_por_hora);
        if ($horas == 1) {
            return "hace 1 hora";
        }
        return "hace " . $horas . " horas";
    } else {
        // Si es más de un día, mostramos la fecha corta
        return date('d M', $tiempo_mensaje); // Ej: 14 Nov
    }
}

function getProductImage($productId) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT imagen 
        FROM fotos 
        WHERE producto_id = ? 
        ORDER BY id ASC 
        LIMIT 1
    ");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $row = $result->fetch_assoc();
    return $row ? "uploads/" . $row['imagen'] : "assets/images/default-product.jpg";
}
function getProductMainImage($producto_id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT imagen FROM fotos WHERE producto_id = ? ORDER BY id ASC LIMIT 1");
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();

    return $res ? "uploads/" . $res['imagen'] : "images/placeholder.jpg";
}

/**
 * Obtiene la URL del avatar del usuario.
 * (Actualizado para usar una imagen por defecto)
 * @param int $userId El ID del usuario.
 * @return string La URL del avatar (o un placeholder).
 */

?>