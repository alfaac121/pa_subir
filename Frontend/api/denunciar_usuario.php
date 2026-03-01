<?php
require_once '../config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$user = getCurrentUser();
$chat_id = isset($_POST['chat_id']) ? (int)$_POST['chat_id'] : 0;
$motivo = isset($_POST['motivo']) ? trim($_POST['motivo']) : '';

if ($chat_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de chat inválido']);
    exit;
}

if (empty($motivo)) {
    echo json_encode(['success' => false, 'message' => 'Debes especificar un motivo']);
    exit;
}

$conn = getDBConnection();

// Obtener información del chat para saber a quién se denuncia
$stmt = $conn->prepare("
    SELECT c.comprador_id, p.vendedor_id, p.nombre as producto_nombre
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
    echo json_encode(['success' => false, 'message' => 'Chat no encontrado']);
    $conn->close();
    exit;
}

// Determinar quién es el denunciante y quién el denunciado
$es_comprador = ($user['id'] == $chat['comprador_id']);
$denunciante_id = $user['id'];
$denunciado_id = $es_comprador ? $chat['vendedor_id'] : $chat['comprador_id'];

// Verificar que el usuario es parte del chat
if (!$es_comprador && $user['id'] != $chat['vendedor_id']) {
    echo json_encode(['success' => false, 'message' => 'No tienes permiso para denunciar en este chat']);
    $conn->close();
    exit;
}

// Crear tabla de denuncias si no existe
$conn->query("
    CREATE TABLE IF NOT EXISTS denuncias (
        id INT AUTO_INCREMENT PRIMARY KEY,
        denunciante_id INT UNSIGNED NOT NULL,
        denunciado_id INT UNSIGNED NOT NULL,
        chat_id INT UNSIGNED NOT NULL,
        motivo TEXT NOT NULL,
        estado ENUM('pendiente', 'revisando', 'resuelta', 'rechazada') DEFAULT 'pendiente',
        fecha_denuncia TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_resolucion TIMESTAMP NULL,
        notas_admin TEXT NULL,
        FOREIGN KEY (denunciante_id) REFERENCES usuarios(id) ON DELETE CASCADE,
        FOREIGN KEY (denunciado_id) REFERENCES usuarios(id) ON DELETE CASCADE,
        FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE,
        INDEX idx_denunciado (denunciado_id),
        INDEX idx_estado (estado),
        INDEX idx_fecha (fecha_denuncia)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

// Verificar si ya existe una denuncia de este usuario en este chat
$stmt_check = $conn->prepare("
    SELECT id FROM denuncias 
    WHERE denunciante_id = ? AND chat_id = ?
");
$stmt_check->bind_param("ii", $denunciante_id, $chat_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
$ya_denuncio = $result_check->num_rows > 0;
$stmt_check->close();

if ($ya_denuncio) {
    echo json_encode(['success' => false, 'message' => 'Ya has denunciado a este usuario en este chat']);
    $conn->close();
    exit;
}

// Insertar la denuncia
$stmt_insert = $conn->prepare("
    INSERT INTO denuncias (denunciante_id, denunciado_id, chat_id, motivo)
    VALUES (?, ?, ?, ?)
");
$stmt_insert->bind_param("iiis", $denunciante_id, $denunciado_id, $chat_id, $motivo);

if ($stmt_insert->execute()) {
    echo json_encode([
        'success' => true, 
        'message' => 'Denuncia enviada correctamente. Nuestro equipo la revisará pronto.'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al registrar la denuncia']);
}

$stmt_insert->close();
$conn->close();
?>
