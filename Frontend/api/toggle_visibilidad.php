<?php
require_once '../config.php';

if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$user = getCurrentUser();
$producto_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($producto_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'ID inválido']);
    exit;
}

$conn = getDBConnection();

// Verificar que el producto pertenece al usuario
$stmt = $conn->prepare("SELECT estado_id FROM productos WHERE id = ? AND vendedor_id = ?");
$stmt->bind_param("ii", $producto_id, $user['id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Producto no encontrado o sin permisos']);
    $stmt->close();
    $conn->close();
    exit;
}

$producto = $result->fetch_assoc();
$nuevo_estado = ($producto['estado_id'] == 1) ? 2 : 1; // Alternar entre activo(1) e invisible(2)

$stmt->close();

$stmt = $conn->prepare("UPDATE productos SET estado_id = ? WHERE id = ? AND vendedor_id = ?");
$stmt->bind_param("iii", $nuevo_estado, $producto_id, $user['id']);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'nuevo_estado' => $nuevo_estado]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al actualizar']);
}

$stmt->close();
$conn->close();
?>
