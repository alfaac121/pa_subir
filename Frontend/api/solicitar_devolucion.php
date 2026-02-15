<?php
/**
 * API para solicitar devolución
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
$motivo = trim($_POST['motivo'] ?? '');

if (!$chatId || empty($motivo)) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$conn = getDBConnection();

// Buscar el ID del vendedor del producto
$stmt = $conn->prepare("
    SELECT c.id, c.comprador_id, p.vendedor_id, p.nombre as producto_nombre
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

// Solo el comprador puede solicitar devolución (pero permitir para pruebas)
// if ($chat['comprador_id'] != $user['id']) {
//     echo json_encode(['success' => false, 'message' => 'No tienes permiso']);
//     $conn->close();
//     exit;
// }

// Insertar mensaje de devolución (enviado por el comprador al vendedor)
$mensaje_sistema = "🔄 SOLICITUD DE DEVOLUCIÓN\n\nMotivo: $motivo\n\n⚠️ Esperando respuesta del vendedor...";
$es_comprador_msg = 1; // El comprador envía el mensaje

$stmt_msg = $conn->prepare("INSERT INTO mensajes (chat_id, es_comprador, mensaje) VALUES (?, ?, ?)");
$stmt_msg->bind_param("iis", $chatId, $es_comprador_msg, $mensaje_sistema);

if (!$stmt_msg->execute()) {
    echo json_encode(['success' => false, 'message' => 'Error al enviar mensaje']);
    $stmt_msg->close();
    $conn->close();
    exit;
}

$mensaje_id = $stmt_msg->insert_id;
$stmt_msg->close();
$conn->close();

echo json_encode([
    'success' => true, 
    'message' => 'Solicitud enviada al vendedor',
    'mensaje_id' => $mensaje_id
]);
exit;
?>