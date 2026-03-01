<?php
ob_start();
require_once '../config.php';
ob_end_clean();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$user = getCurrentUser();
$chatId = intval($_POST['chat_id'] ?? 0);
$accion = $_POST['accion'] ?? '';

if (!$chatId || !in_array($accion, ['aceptar', 'rechazar'])) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$conn = getDBConnection();

// Obtener información del chat para determinar el rol del usuario
$stmt = $conn->prepare("
    SELECT c.id, c.comprador_id, p.vendedor_id 
    FROM chats c
    INNER JOIN productos p ON c.producto_id = p.id
    WHERE c.id = ?
");
$stmt->bind_param("i", $chatId);
$stmt->execute();
$result = $stmt->get_result();
$chat = $result->fetch_assoc();
$stmt->close();

if (!$chat) {
    echo json_encode(['success' => false, 'message' => 'Chat no encontrado']);
    $conn->close();
    exit;
}

// Determinar si el usuario es comprador o vendedor
$es_comprador = ($user['id'] == $chat['comprador_id']);

if ($accion === 'aceptar') {
    $mensaje = "✅ DEVOLUCIÓN ACEPTADA\n\nHe aceptado la devolución.";
    $msg = 'Devolución aceptada';
    
    // Cambiar el estado del chat a "devuelto" (estado_id = 8)
    $stmt_estado = $conn->prepare("UPDATE chats SET estado_id = 8 WHERE id = ?");
    $stmt_estado->bind_param("i", $chatId);
    $stmt_estado->execute();
    $stmt_estado->close();
} else {
    $mensaje = "❌ DEVOLUCIÓN RECHAZADA\n\nHe rechazado la devolución.";
    $msg = 'Devolución rechazada';
}

// El mensaje se envía con el rol correcto del usuario que responde
$es_comprador_msg = $es_comprador ? 1 : 0;

$stmt_msg = $conn->prepare("INSERT INTO mensajes (chat_id, es_comprador, mensaje) VALUES (?, ?, ?)");
$stmt_msg->bind_param("iis", $chatId, $es_comprador_msg, $mensaje);

if (!$stmt_msg->execute()) {
    echo json_encode(['success' => false, 'message' => 'Error al enviar mensaje']);
    $stmt_msg->close();
    $conn->close();
    exit;
}

$stmt_msg->close();
$conn->close();

echo json_encode(['success' => true, 'message' => $msg]);
?>
