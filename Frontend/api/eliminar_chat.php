<?php
require_once '../config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$user = getCurrentUser();
$chat_id = isset($_POST['chat_id']) ? (int)$_POST['chat_id'] : 0;

if ($chat_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de chat inválido']);
    exit;
}

$conn = getDBConnection();

// Verificar que el chat pertenece al usuario y está cerrado (estado_id = 8)
$stmt = $conn->prepare("
    SELECT c.id, c.estado_id, c.comprador_id, p.vendedor_id
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

// Verificar que el usuario es parte del chat
$es_comprador = ($user['id'] == $chat['comprador_id']);
$es_vendedor = ($user['id'] == $chat['vendedor_id']);

if (!$es_comprador && !$es_vendedor) {
    echo json_encode(['success' => false, 'error' => 'No tienes permiso para eliminar este chat']);
    $conn->close();
    exit;
}

// Verificar que el chat está cerrado (estado_id = 8)
if ($chat['estado_id'] != 8) {
    echo json_encode(['success' => false, 'error' => 'Solo se pueden eliminar chats cerrados']);
    $conn->close();
    exit;
}

// Crear tabla de eliminaciones si no existe (solo la primera vez)
$conn->query("
    CREATE TABLE IF NOT EXISTS chats_eliminados (
        id INT AUTO_INCREMENT PRIMARY KEY,
        chat_id INT UNSIGNED NOT NULL,
        usuario_id INT UNSIGNED NOT NULL,
        fecha_eliminacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_chat_usuario (chat_id, usuario_id),
        FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

// Registrar que este usuario eliminó el chat
$stmt = $conn->prepare("
    INSERT INTO chats_eliminados (chat_id, usuario_id) 
    VALUES (?, ?)
    ON DUPLICATE KEY UPDATE fecha_eliminacion = CURRENT_TIMESTAMP
");
$stmt->bind_param("ii", $chat_id, $user['id']);

if ($stmt->execute()) {
    // Verificar si ambos usuarios ya eliminaron el chat
    $stmt_check = $conn->prepare("
        SELECT COUNT(*) as eliminaciones 
        FROM chats_eliminados 
        WHERE chat_id = ?
    ");
    $stmt_check->bind_param("i", $chat_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $row = $result_check->fetch_assoc();
    $stmt_check->close();
    
    // Si ambos eliminaron, cambiar estado del chat a eliminado
    if ($row['eliminaciones'] >= 2) {
        $stmt_update = $conn->prepare("UPDATE chats SET estado_id = 3 WHERE id = ?");
        $stmt_update->bind_param("i", $chat_id);
        $stmt_update->execute();
        $stmt_update->close();
    }
    
    echo json_encode(['success' => true, 'message' => 'Chat eliminado de tu vista']);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al eliminar el chat']);
}

$stmt->close();
$conn->close();
?>
