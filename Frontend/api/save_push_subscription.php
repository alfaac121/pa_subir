<?php
/**
 * API para guardar suscripción de Push Notifications
 * RF05-004 - Tu Mercado SENA
 */

require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$user = getCurrentUser();
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['subscription'])) {
    echo json_encode(['success' => false, 'message' => 'Suscripción no proporcionada']);
    exit;
}

$subscription = json_encode($input['subscription']);

$conn = getDBConnection();

// Guardar o actualizar suscripción
$stmt = $conn->prepare("
    INSERT INTO push_subscriptions (usuario_id, subscription_data, fecha_registro)
    VALUES (?, ?, NOW())
    ON DUPLICATE KEY UPDATE 
        subscription_data = VALUES(subscription_data),
        fecha_registro = NOW()
");

$stmt->bind_param("is", $user['id'], $subscription);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Suscripción guardada']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al guardar suscripción']);
}

$stmt->close();
$conn->close();
?>
