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
$motivo = trim($_POST['motivo'] ?? '');

if (!$chatId || empty($motivo)) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$conn = getDBConnection();

$stmt = $conn->prepare("SELECT c.id FROM chats c WHERE c.id = ?");
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

$mensaje = "🔄 SOLICITUD DE DEVOLUCIÓN\n\nMotivo: $motivo\n\n⚠️ Esperando respuesta del vendedor...";
$es_comprador_msg = 1;

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

echo json_encode(['success' => true, 'message' => 'Solicitud enviada']);
?>
