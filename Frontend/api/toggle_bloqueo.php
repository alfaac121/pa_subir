<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$user = getCurrentUser();
$conn = getDBConnection();

$usuario_id = isset($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : 0;

if ($usuario_id <= 0 || $usuario_id == $user['id']) {
    echo json_encode(['success' => false, 'error' => 'Usuario inválido']);
    exit;
}

// Verificar si ya está bloqueado
$stmt = $conn->prepare("SELECT id FROM bloqueados WHERE bloqueador_id = ? AND bloqueado_id = ?");
$stmt->bind_param("ii", $user['id'], $usuario_id);
$stmt->execute();
$existe = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existe) {
    // Desbloquear
    $stmt = $conn->prepare("DELETE FROM bloqueados WHERE bloqueador_id = ? AND bloqueado_id = ?");
    $stmt->bind_param("ii", $user['id'], $usuario_id);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    echo json_encode(['success' => true, 'action' => 'desbloqueado']);
} else {
    // Bloquear
    $stmt = $conn->prepare("INSERT INTO bloqueados (bloqueador_id, bloqueado_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user['id'], $usuario_id);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    echo json_encode(['success' => true, 'action' => 'bloqueado']);
}
?>
