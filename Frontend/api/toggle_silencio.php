<?php
require_once '../config.php';

if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$user = getCurrentUser();
$chat_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($chat_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'ID inválido']);
    exit;
}

$conn = getDBConnection();

// Verificar el chat y el rol del usuario
$stmt = $conn->prepare("SELECT comprador_id, silenciado_comprador, silenciado_vendedor FROM chats WHERE id = ?");
$stmt->bind_param("i", $chat_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Chat no encontrado']);
    $stmt->close();
    $conn->close();
    exit;
}

$chat = $result->fetch_assoc();
$es_comprador = ($user['id'] == $chat['comprador_id']);

if ($es_comprador) {
    $nuevo_silencio = $chat['silenciado_comprador'] ? 0 : 1;
    $sql = "UPDATE chats SET silenciado_comprador = ? WHERE id = ?";
} else {
    $nuevo_silencio = $chat['silenciado_vendedor'] ? 0 : 1;
    $sql = "UPDATE chats SET silenciado_vendedor = ? WHERE id = ?";
}

$stmt->close();
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $nuevo_silencio, $chat_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'silenciado' => $nuevo_silencio]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al actualizar']);
}

$stmt->close();
$conn->close();
?>
