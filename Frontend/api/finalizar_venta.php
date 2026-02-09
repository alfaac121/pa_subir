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
$precio = isset($_POST['precio']) ? floatval($_POST['precio']) : 0;
$cantidad = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 1;

if ($chat_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Chat inválido']);
    exit;
}

// Verificar que el usuario es el vendedor de este chat
$stmt = $conn->prepare("
    SELECT c.*, p.vendedor_id, p.precio as precio_original
    FROM chats c
    INNER JOIN productos p ON c.producto_id = p.id
    WHERE c.id = ?
");
$stmt->bind_param("i", $chat_id);
$stmt->execute();
$chat = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$chat || $chat['vendedor_id'] != $user['id']) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    $conn->close();
    exit;
}

if ($chat['fecha_venta'] !== null) {
    echo json_encode(['success' => false, 'error' => 'Esta transacción ya fue finalizada']);
    $conn->close();
    exit;
}

// Usar precio original si no se especifica
if ($precio <= 0) {
    $precio = $chat['precio_original'];
}

// Finalizar venta
$stmt = $conn->prepare("
    UPDATE chats 
    SET precio = ?, cantidad = ?, fecha_venta = NOW(), estado_id = 4 
    WHERE id = ?
");
$stmt->bind_param("dii", $precio, $cantidad, $chat_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Venta finalizada correctamente']);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al finalizar']);
}

$stmt->close();
$conn->close();
?>
