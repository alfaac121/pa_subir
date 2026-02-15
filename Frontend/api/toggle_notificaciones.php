<?php
/**
 * API para activar/desactivar notificaciones
 * RF05-003, RF05-004
 */

require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$user = getCurrentUser();
$tipo = $_POST['tipo'] ?? ''; // 'correo' o 'push'
$estado = intval($_POST['estado'] ?? 0); // 1 = activar, 0 = desactivar

if (!in_array($tipo, ['correo', 'push'])) {
    echo json_encode(['success' => false, 'message' => 'Tipo de notificación inválido']);
    exit;
}

$conn = getDBConnection();

// Obtener cuenta_id del usuario
$stmt = $conn->prepare("SELECT cuenta_id FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$stmt->close();

if (!$userData) {
    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    $conn->close();
    exit;
}

$cuentaId = $userData['cuenta_id'];
$campo = $tipo === 'correo' ? 'notifica_correo' : 'notifica_push';

// Actualizar preferencia
$stmt = $conn->prepare("UPDATE cuentas SET $campo = ? WHERE id = ?");
$stmt->bind_param("ii", $estado, $cuentaId);

if ($stmt->execute()) {
    $accion = $estado ? 'activadas' : 'desactivadas';
    $tipoTexto = $tipo === 'correo' ? 'por correo' : 'push';
    echo json_encode([
        'success' => true, 
        'message' => "Notificaciones $tipoTexto $accion",
        'estado' => $estado
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar preferencia']);
}

$stmt->close();
$conn->close();
?>
