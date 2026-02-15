
<?php
require_once '../config.php';
// Cargar sistema de notificaciones solo si existe (no crítico para el envío)
if (file_exists('../includes/notification_system.php')) {
    require_once '../includes/notification_system.php';
}
header('Content-Type: application/json');

// Establecer zona horaria colombiana (NECESARIO para la función formato_tiempo_relativo)
date_default_timezone_set('America/Bogota');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$chat_id = isset($_POST['chat_id']) ? (int)$_POST['chat_id'] : 0;
$mensaje = isset($_POST['mensaje']) ? trim($_POST['mensaje']) : '';

if ($chat_id <= 0 || empty($mensaje)) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit;
}

$user = getCurrentUser();
$conn = getDBConnection();

// Verificar que el usuario pertenece al chat
$stmt = $conn->prepare("
    SELECT c.comprador_id, p.vendedor_id, c.producto_id, p.nombre as producto_nombre
    FROM chats c
    INNER JOIN productos p ON c.producto_id = p.id
    WHERE c.id = ?
");
$stmt->bind_param("i", $chat_id);
$stmt->execute();

$result = $stmt->get_result();
$chat = $result->fetch_assoc();
$stmt->close();

if (!$chat) {
    echo json_encode(['success' => false, 'error' => 'Chat no encontrado']);
    $conn->close();
    exit;
}

// Determinar rol
$es_comprador = ($user['id'] == $chat['comprador_id']);
$es_vendedor  = ($user['id'] == $chat['vendedor_id']);

if (!$es_comprador && !$es_vendedor) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    $conn->close();
    exit;
}

// Sanitizar mensaje
$mensaje = sanitize($mensaje);

// Determinar rol del mensaje
$es_comprador_msg = $es_comprador ? 1 : 0;
$imagen = ' ';

// ✅ INSERCIÓN CORREGIDA: Dejamos que el campo fecha_registro en MySQL use CURRENT_TIMESTAMP().
$stmt = $conn->prepare("
    INSERT INTO mensajes (es_comprador, chat_id, mensaje, imagen)
    VALUES (?, ?, ?, ?)
");
// Quitamos 's' del bind_param y quitamos el parámetro de fecha
$stmt->bind_param("iiss", $es_comprador_msg, $chat_id, $mensaje, $imagen); 

if ($stmt->execute()) {
    $message_id = $conn->insert_id;

    // Actualizar estado de "visto"
    if ($es_comprador) {
        $stmt2 = $conn->prepare("UPDATE chats SET visto_vendedor = 0, visto_comprador = 1 WHERE id = ?");
    } else {
        $stmt2 = $conn->prepare("UPDATE chats SET visto_comprador = 0, visto_vendedor = 1 WHERE id = ?");
    }
    $stmt2->bind_param("i", $chat_id);
    $stmt2->execute();
    $stmt2->close();

    // RECUPERAR EL MENSAJE Y LA FECHA REAL GUARDADA
    $stmt3 = $conn->prepare("SELECT id, es_comprador, mensaje, fecha_registro FROM mensajes WHERE id = ?");
    $stmt3->bind_param("i", $message_id);
    $stmt3->execute();
    $result = $stmt3->get_result();
    $message = $result->fetch_assoc();
    $stmt3->close();

    // Validar que el mensaje se recuperó correctamente
    if (!$message) {
        $stmt->close();
        $conn->close();
        echo json_encode(['success' => false, 'error' => 'Error al recuperar el mensaje guardado']);
        exit;
    }
    
    // ✅ Formatear el mensaje para que JS lo muestre con saltos de línea y sanitizado
    $message['mensaje'] = nl2br(htmlspecialchars($message['mensaje']));
    
    // ✅ Formatear el tiempo usando la función relativa (será "Ahora")
    $message['tiempo_relativo'] = formato_tiempo_relativo($message['fecha_registro']);
    $message['es_mio'] = 1;

    // ✅ CLAVE: Devolver el objeto $message completo bajo la clave 'message'
    // Preparar respuesta ANTES de intentar notificaciones para asegurar que siempre se devuelva
    $response = [
        'success' => true,
        'message' => $message
    ];
    
    // Cerrar conexión ANTES de intentar notificaciones para evitar conflictos
    $stmt->close();
    $conn->close();
    
    // RF05-003, RF05-005, RF05-006: Enviar notificación automática al destinatario
    // IMPORTANTE: Esto NO debe afectar el envío del mensaje si falla
    // Ejecutar en un bloque separado para que cualquier error no afecte la respuesta
    $notificacion_result = null;
    if (function_exists('notificarNuevoMensaje')) {
        // Usar output buffering para capturar cualquier salida no deseada
        ob_start();
        try {
            $destinatario_id = $es_comprador ? $chat['vendedor_id'] : $chat['comprador_id'];
            $notificacion_result = @notificarNuevoMensaje($chat_id, $user['id'], $destinatario_id, $chat['producto_nombre'] ?? 'un producto');
            // Solo agregar notificación si existe y no hay errores
            if ($notificacion_result && isset($notificacion_result['resultado'])) {
                $response['notificacion'] = $notificacion_result['resultado'];
            }
        } catch (Throwable $e) {
            // Capturar cualquier tipo de error (Exception o Error)
            error_log("Error al enviar notificación: " . $e->getMessage());
        }
        // Descartar cualquier salida capturada de las notificaciones
        ob_end_clean();
    }
    
    // SIEMPRE devolver la respuesta, incluso si las notificaciones fallaron
    echo json_encode($response);
    exit; // Asegurar que no se ejecute código adicional
} else {
    $error_msg = $stmt->error ?? 'Error desconocido al guardar mensaje';
    $stmt->close();
    $conn->close();
    echo json_encode(['success' => false, 'error' => $error_msg]);
    exit;
}
?>