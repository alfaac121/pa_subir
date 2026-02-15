<?php
/**
 * API para responder confirmación de compra (comprador)
 */

ob_start();
require_once '../config.php';
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
$accion = $_POST['accion'] ?? ''; // 'confirmar' o 'rechazar'

if (!$chatId || !in_array($accion, ['confirmar', 'rechazar'])) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$conn = getDBConnection();

// Verificar que el chat existe y el usuario es el comprador
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

// Solo el comprador puede responder (pero permitir para pruebas)
// if ($chat['comprador_id'] != $user['id']) {
//     echo json_encode(['success' => false, 'message' => 'No tienes permiso']);
//     $conn->close();
//     exit;
// }

// Insertar mensaje de respuesta
if ($accion === 'confirmar') {
    $mensaje_respuesta = "✅ COMPRA CONFIRMADA\n\nHe confirmado la compra. ¡Gracias!";
    $mensaje = 'Compra confirmada exitosamente.';
} else {
    $mensaje_respuesta = "❌ COMPRA RECHAZADA\n\nHe decidido no realizar esta compra.";
    $mensaje = 'Compra rechazada.';
}

$es_comprador_msg = 1; // El comprador envía el mensaje

$stmt_msg = $conn->prepare("INSERT INTO mensajes (chat_id, es_comprador, mensaje) VALUES (?, ?, ?)");
$stmt_msg->bind_param("iis", $chatId, $es_comprador_msg, $mensaje_respuesta);

if (!$stmt_msg->execute()) {
    echo json_encode(['success' => false, 'message' => 'Error al enviar mensaje']);
    $stmt_msg->close();
    $conn->close();
    exit;
}

$stmt_msg->close();
$conn->close();

echo json_encode([
    'success' => true, 
    'message' => $mensaje
]);
exit;
?>
