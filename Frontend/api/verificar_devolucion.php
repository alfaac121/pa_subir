<?php
/**
 * API para verificar estado de devolución en el chat
 * Devuelve si hay una solicitud pendiente
 */

require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$chat_id = isset($_GET['chat_id']) ? (int)$_GET['chat_id'] : 0;

if (!$chat_id) {
    echo json_encode(['success' => false, 'message' => 'ID de chat inválido']);
    exit;
}

$user = getCurrentUser();
$conn = getDBConnection();

// Obtener información del chat
$stmt = $conn->prepare("
    SELECT 
        c.id,
        c.estado_id,
        c.comprador_id,
        p.vendedor_id,
        p.nombre as producto_nombre
    FROM chats c
    INNER JOIN productos p ON c.producto_id = p.id
    WHERE c.id = ?
");
$stmt->bind_param("i", $chat_id);
$stmt->execute();
$result = $stmt->get_result();
$chat = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$chat) {
    echo json_encode(['success' => false, 'message' => 'Chat no encontrado']);
    exit;
}

// Verificar que el usuario es parte del chat
$es_comprador = $user['id'] == $chat['comprador_id'];
$es_vendedor = $user['id'] == $chat['vendedor_id'];

if (!$es_comprador && !$es_vendedor) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Estado 7 = devolviendo (solicitud pendiente)
$tiene_solicitud_pendiente = ($chat['estado_id'] == 7);

echo json_encode([
    'success' => true,
    'estado_id' => $chat['estado_id'],
    'tiene_solicitud_pendiente' => $tiene_solicitud_pendiente,
    'es_vendedor' => $es_vendedor,
    'es_comprador' => $es_comprador,
    'producto_nombre' => $chat['producto_nombre']
]);
?>
