<?php
/**
 * API para responder a solicitud de devolución (vendedor)
 * RF03-017 - Tu Mercado SENA
 */

// Iniciar output buffering para evitar salida antes del JSON
ob_start();

require_once '../config.php';
require_once '../includes/email_functions.php';
require_once '../includes/notification_system.php';

// Limpiar cualquier salida previa
ob_end_clean();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$user = getCurrentUser();
$chatId = intval($_POST['chat_id'] ?? 0);
$accion = $_POST['accion'] ?? ''; // 'aceptar' o 'rechazar'
$respuesta = trim($_POST['respuesta'] ?? '');

if (!$chatId) {
    echo json_encode(['success' => false, 'message' => 'ID de transacción inválido']);
    exit;
}

if (!in_array($accion, ['aceptar', 'rechazar'])) {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    exit;
}

$conn = getDBConnection();

// Verificar que el chat existe y pertenece al usuario como vendedor
$stmt = $conn->prepare("
    SELECT 
        c.id,
        c.estado_id,
        c.comprador_id,
        c.cantidad,
        p.nombre as producto_nombre,
        p.vendedor_id,
        p.id as producto_id,
        p.disponibles,
        u.nickname as comprador_nombre,
        cu.email as comprador_email
    FROM chats c
    INNER JOIN productos p ON c.producto_id = p.id
    INNER JOIN usuarios u ON c.comprador_id = u.id
    INNER JOIN cuentas cu ON u.cuenta_id = cu.id
    WHERE c.id = ?
");
$stmt->bind_param("i", $chatId);
$stmt->execute();
$result = $stmt->get_result();
$chat = $result->fetch_assoc();
$stmt->close();

if (!$chat) {
    echo json_encode(['success' => false, 'message' => 'Transacción no encontrada']);
    $conn->close();
    exit;
}

// Solo el vendedor puede responder
if ($chat['vendedor_id'] != $user['id']) {
    echo json_encode(['success' => false, 'message' => 'No tienes permiso para esta acción']);
    $conn->close();
    exit;
}

// Verificar que la transacción esté en estado "devolviendo" (7)
if ($chat['estado_id'] != 7) {
    echo json_encode(['success' => false, 'message' => 'Esta transacción no tiene solicitud de devolución pendiente']);
    $conn->close();
    exit;
}

// Iniciar transacción
$conn->begin_transaction();

try {
    if ($accion === 'aceptar') {
        // Cambiar estado a "devuelto" (8)
        $stmt = $conn->prepare("UPDATE chats SET estado_id = 8, calificacion = NULL, comentario = NULL WHERE id = ?");
        $stmt->bind_param("i", $chatId);
        $stmt->execute();
        $stmt->close();
        
        // ❌ STOCK NO SE RESTAURA - Eliminado por solicitud del usuario
        
        // Notificar al comprador
        $mensajeNotif = "Tu solicitud de devolución para '{$chat['producto_nombre']}' fue ACEPTADA.";
        if (!empty($respuesta)) {
            $mensajeNotif .= " Mensaje del vendedor: $respuesta";
        }
        $stmt = $conn->prepare("
            INSERT INTO notificaciones (usuario_id, motivo_id, mensaje, visto) 
            VALUES (?, 16, ?, 0)
        ");
        $stmt->bind_param("is", $chat['comprador_id'], $mensajeNotif);
        $stmt->execute();
        $stmt->close();
        
        $mensaje = 'Devolución aceptada.';
        
    } else {
        // Rechazar: volver a estado "vendido" (5)
        $stmt = $conn->prepare("UPDATE chats SET estado_id = 5 WHERE id = ?");
        $stmt->bind_param("i", $chatId);
        $stmt->execute();
        $stmt->close();
        
        // Notificar al comprador
        $mensajeNotif = "Tu solicitud de devolución para '{$chat['producto_nombre']}' fue RECHAZADA.";
        if (!empty($respuesta)) {
            $mensajeNotif .= " Motivo: $respuesta";
        }
        $stmt = $conn->prepare("
            INSERT INTO notificaciones (usuario_id, motivo_id, mensaje, visto) 
            VALUES (?, 16, ?, 0)
        ");
        $stmt->bind_param("is", $chat['comprador_id'], $mensajeNotif);
        $stmt->execute();
        $stmt->close();
        
        $mensaje = 'Devolución rechazada. La transacción permanece como vendida.';
    }
    
    // ENVIAR MENSAJE AUTOMÁTICO AL CHAT ANTES DE COMMIT (como si lo enviara el vendedor)
    if ($accion === 'aceptar') {
        $mensaje_sistema = "✅ DEVOLUCIÓN ACEPTADA\n\nHe aceptado tu solicitud de devolución.";
        if (!empty($respuesta)) {
            $mensaje_sistema .= "\n\nMensaje: $respuesta";
        }
    } else {
        $mensaje_sistema = "❌ DEVOLUCIÓN RECHAZADA\n\nHe rechazado tu solicitud de devolución.";
        if (!empty($respuesta)) {
            $mensaje_sistema .= "\n\nMotivo: $respuesta";
        }
    }
    
    $es_comprador_msg = 0; // El mensaje lo envía el vendedor
    $stmt_msg = $conn->prepare("INSERT INTO mensajes (chat_id, es_comprador, mensaje) VALUES (?, ?, ?)");
    $stmt_msg->bind_param("iis", $chatId, $es_comprador_msg, $mensaje_sistema);
    
    if (!$stmt_msg->execute()) {
        throw new Exception("Error al insertar mensaje: " . $stmt_msg->error);
    }
    $stmt_msg->close();
    
    $conn->commit();
    
    // Cerrar conexión antes de notificaciones
    $conn->close();
    
    // RF05-003, RF05-005, RF05-006: Enviar notificación al comprador
    // Usar output buffering para evitar salida HTML
    ob_start();
    try {
        if (function_exists('notificarDevolucionRespondida')) {
            @notificarDevolucionRespondida($chat['comprador_id'], $chat['producto_nombre'], $accion === 'aceptar');
        }
    } catch (Exception $e) {
        error_log("Error en notificaciones: " . $e->getMessage());
    }
    ob_end_clean();
    
    echo json_encode([
        'success' => true, 
        'message' => $mensaje
    ]);
    exit;
    
} catch (Exception $e) {
    $conn->rollback();
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Error al procesar la respuesta: ' . $e->getMessage()]);
    exit;
}
?>