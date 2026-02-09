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
$mensaje = isset($_POST['mensaje']) ? sanitize($_POST['mensaje']) : '';

if ($chat_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Chat invÃ¡lido']);
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

// Verificar imagen
if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Imagen no vÃ¡lida']);
    $conn->close();
    exit;
}

$file = $_FILES['imagen'];
$allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$mime = mime_content_type($file['tmp_name']);
$maxSize = 5 * 1024 * 1024; // 5MB

if (!in_array($mime, $allowed)) {
    echo json_encode(['success' => false, 'error' => 'Formato no permitido']);
    $conn->close();
    exit;
}

if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'error' => 'Imagen muy grande (mÃ¡x 5MB)']);
    $conn->close();
    exit;
}

// Guardar imagen
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'chat_' . uniqid() . '.' . $ext;
$uploadPath = __DIR__ . '/../uploads/chat/' . $filename;

// Crear directorio si no existe
if (!is_dir(__DIR__ . '/../uploads/chat')) {
    mkdir(__DIR__ . '/../uploads/chat', 0755, true);
}

if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
    echo json_encode(['success' => false, 'error' => 'Error al guardar imagen']);
    $conn->close();
    exit;
}

// Insertar mensaje con imagen
$es_comprador_int = $es_comprador ? 1 : 0;
$imagen_path = 'chat/' . $filename;

$stmt = $conn->prepare("INSERT INTO mensajes (chat_id, es_comprador, mensaje, imagen) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiss", $chat_id, $es_comprador_int, $mensaje, $imagen_path);

if ($stmt->execute()) {
    $mensaje_id = $conn->insert_id;
    
    // Actualizar visto
    if ($es_comprador) {
        $conn->query("UPDATE chats SET visto_vendedor = 0 WHERE id = $chat_id");
    } else {
        $conn->query("UPDATE chats SET visto_comprador = 0 WHERE id = $chat_id");
    }
    
    echo json_encode([
        'success' => true,
        'mensaje_id' => $mensaje_id,
        'imagen_url' => 'uploads/chat/' . $imagen_path
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al enviar']);
}

$stmt->close();
$conn->close();
?>
