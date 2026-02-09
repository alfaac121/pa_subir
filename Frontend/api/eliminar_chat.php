<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$user = getCurrentUser();
$conn = getDBConnection();

$chat_id = isset($_POST['chat_id']) ? (int)$_POST['chat_id'] : 0;

if ($chat_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Chat inválido']);
    exit;
}

// Verificar que el usuario pertenece al chat
$stmt = $conn->prepare("
    SELECT c.comprador_id, p.vendedor_id
    FROM chats c
    INNER JOIN productos p ON c.producto_id = p.id
    WHERE c.id = ?
");
$stmt->bind_param("i", $chat_id);
$stmt->execute();
$chat = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$chat) {
    echo json_encode(['success' => false, 'error' => 'Chat no encontrado']);
    $conn->close();
    exit;
}

$es_comprador = $user['id'] == $chat['comprador_id'];
$es_vendedor = $user['id'] == $chat['vendedor_id'];

if (!$es_comprador && !$es_vendedor) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    $conn->close();
    exit;
}

// Marcar como eliminado para este usuario (cambiar estado según rol)
// Estado 5 = eliminado_comprador, Estado 6 = eliminado_vendedor, Estado 3 = eliminado_ambos
$stmt = $conn->prepare("SELECT estado_id FROM chats WHERE id = ?");
$stmt->bind_param("i", $chat_id);
$stmt->execute();
$estado_actual = $stmt->get_result()->fetch_assoc()['estado_id'];
$stmt->close();

$nuevo_estado = $estado_actual;

if ($es_comprador && $estado_actual == 1) {
    $nuevo_estado = 5; // eliminado por comprador
} elseif ($es_vendedor && $estado_actual == 1) {
    $nuevo_estado = 6; // eliminado por vendedor
} elseif (($es_comprador && $estado_actual == 6) || ($es_vendedor && $estado_actual == 5)) {
    $nuevo_estado = 3; // eliminado por ambos
}

$stmt = $conn->prepare("UPDATE chats SET estado_id = ? WHERE id = ?");
$stmt->bind_param("ii", $nuevo_estado, $chat_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Chat eliminado']);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al eliminar']);
}

$stmt->close();
$conn->close();
?>
