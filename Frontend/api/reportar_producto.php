<?php
/**
 * API para reportar productos
 * Guarda la denuncia en la tabla de denuncias
 */

header('Content-Type: application/json');
require_once '../config.php';

// Verificar autenticación
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Obtener datos
$data = json_decode(file_get_contents('php://input'), true);
$producto_id = isset($data['producto_id']) ? (int)$data['producto_id'] : 0;
$motivo = isset($data['motivo']) ? (int)$data['motivo'] : 0;
$comentario = isset($data['comentario']) ? sanitize($data['comentario']) : '';

// Validar
if ($producto_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Producto inválido']);
    exit;
}

if ($motivo < 1 || $motivo > 5) {
    echo json_encode(['success' => false, 'error' => 'Selecciona un motivo válido']);
    exit;
}

$user = getCurrentUser();
$conn = getDBConnection();

// Verificar que el producto existe
$stmt = $conn->prepare("SELECT id, vendedor_id FROM productos WHERE id = ? AND estado_id = 1");
$stmt->bind_param("i", $producto_id);
$stmt->execute();
$producto = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$producto) {
    echo json_encode(['success' => false, 'error' => 'Producto no encontrado']);
    $conn->close();
    exit;
}

// No permitir reportar tus propios productos
if ($producto['vendedor_id'] == $user['id']) {
    echo json_encode(['success' => false, 'error' => 'No puedes reportar tus propios productos']);
    $conn->close();
    exit;
}

// Verificar si ya reportó este producto
$stmt = $conn->prepare("SELECT id FROM denuncias WHERE denunciante_id = ? AND producto_id = ?");
$stmt->bind_param("ii", $user['id'], $producto_id);
$stmt->execute();
$existe = $stmt->get_result()->num_rows > 0;
$stmt->close();

if ($existe) {
    echo json_encode(['success' => false, 'error' => 'Ya has reportado este producto anteriormente']);
    $conn->close();
    exit;
}

// Mapear motivos de denuncia a IDs
// Necesitamos verificar los motivos en la BD, por ahora usamos 1 como genérico
$motivo_db = 1; // motivo genérico para denuncia

// Insertar denuncia (estructura real de la tabla)
$estado_id = 1; // activo/pendiente

$stmt = $conn->prepare("INSERT INTO denuncias (denunciante_id, producto_id, motivo_id, estado_id) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiii", $user['id'], $producto_id, $motivo_db, $estado_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true, 
        'message' => '¡Gracias por tu reporte! Lo revisaremos pronto.'
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al guardar el reporte']);
}

$stmt->close();
$conn->close();
