<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$user = getCurrentUser();
$conn = getDBConnection();

$user_id = $user['id'];

// Obtener chats donde el usuario es comprador
$query_comprador = "SELECT c.id as chat_id, c.comprador_id, c.producto_id, c.visto_comprador, c.visto_vendedor,
                    p.nombre as producto_nombre, p.precio as producto_precio,
                    u_vendedor.nickname as vendedor_nombre, u_vendedor.id as vendedor_id,
                    u_comprador.nickname as comprador_nombre
                    FROM chats c
                    INNER JOIN productos p ON c.producto_id = p.id
                    INNER JOIN usuarios u_comprador ON c.comprador_id = u_comprador.id
                    INNER JOIN usuarios u_vendedor ON p.vendedor_id = u_vendedor.id
                    WHERE c.comprador_id = ? AND c.estado_id = 1";

$stmt = $conn->prepare($query_comprador);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => $conn->error]);
    exit;
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_comprador = $stmt->get_result();
$chats = [];

while ($row = $result_comprador->fetch_assoc()) {
    $row['es_comprador'] = true;
    $row['otro_usuario'] = $row['vendedor_nombre'];
    
    // Si ya lo vio, los no leídos son 0, independientemente del conteo por ID
    if ($row['visto_comprador'] == 1) {
        $row['mensajes_no_leidos'] = 0;
    } else {
        // Obtener último mensaje del comprador (este usuario)
        $stmt2 = $conn->prepare("SELECT COALESCE(MAX(id), 0) as last_my_id FROM mensajes WHERE chat_id = ? AND es_comprador = 1");
        $stmt2->bind_param("i", $row['chat_id']);
        $stmt2->execute();
        $last_my_result = $stmt2->get_result();
        $last_my_msg = $last_my_result->fetch_assoc();
        $last_my_id = $last_my_msg['last_my_id'];
        $stmt2->close();
        
        // Contar mensajes del vendedor (otro usuario) después del último mensaje del comprador
        $stmt2 = $conn->prepare("SELECT COUNT(*) as count FROM mensajes WHERE chat_id = ? AND es_comprador = 0 AND id > ?");
        $stmt2->bind_param("ii", $row['chat_id'], $last_my_id);
        $stmt2->execute();
        $unread_result = $stmt2->get_result();
        $unread = $unread_result->fetch_assoc();
        $row['mensajes_no_leidos'] = (int)$unread['count'];
        $stmt2->close();
    }
    
    // Obtener último mensaje
    $stmt3 = $conn->prepare("SELECT mensaje, fecha_registro, es_comprador FROM mensajes 
                            WHERE chat_id = ? ORDER BY fecha_registro DESC LIMIT 1");
    $stmt3->bind_param("i", $row['chat_id']);
    $stmt3->execute();
    $last_msg_result = $stmt3->get_result();
    $row['ultimo_mensaje'] = $last_msg_result->fetch_assoc();
    $stmt3->close();
    
    $chats[] = $row;
}
$stmt->close();

// Obtener chats donde el usuario es vendedor
$query_vendedor = "SELECT c.id as chat_id, c.comprador_id, c.producto_id, c.visto_comprador, c.visto_vendedor,
                   p.nombre as producto_nombre, p.precio as producto_precio,
                   u_vendedor.nickname as vendedor_nombre, u_vendedor.id as vendedor_id,
                   u_comprador.nickname as comprador_nombre
                   FROM chats c
                   INNER JOIN productos p ON c.producto_id = p.id
                   INNER JOIN usuarios u_comprador ON c.comprador_id = u_comprador.id
                   INNER JOIN usuarios u_vendedor ON p.vendedor_id = u_vendedor.id
                   WHERE p.vendedor_id = ? AND c.estado_id = 1";

$stmt = $conn->prepare($query_vendedor);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_vendedor = $stmt->get_result();

while ($row = $result_vendedor->fetch_assoc()) {
    $row['es_comprador'] = false;
    $row['otro_usuario'] = $row['comprador_nombre'];
    
    // Si ya lo vio, los no leídos son 0
    if ($row['visto_vendedor'] == 1) {
        $row['mensajes_no_leidos'] = 0;
    } else {
        // Obtener último mensaje del vendedor (este usuario)
        $stmt2 = $conn->prepare("SELECT COALESCE(MAX(id), 0) as last_my_id FROM mensajes WHERE chat_id = ? AND es_comprador = 0");
        $stmt2->bind_param("i", $row['chat_id']);
        $stmt2->execute();
        $last_my_result = $stmt2->get_result();
        $last_my_msg = $last_my_result->fetch_assoc();
        $last_my_id = $last_my_msg['last_my_id'];
        $stmt2->close();
        
        // Contar mensajes del comprador (otro usuario) después del último mensaje del vendedor
        $stmt2 = $conn->prepare("SELECT COUNT(*) as count FROM mensajes WHERE chat_id = ? AND es_comprador = 1 AND id > ?");
        $stmt2->bind_param("ii", $row['chat_id'], $last_my_id);
        $stmt2->execute();
        $unread_result = $stmt2->get_result();
        $unread = $unread_result->fetch_assoc();
        $row['mensajes_no_leidos'] = (int)$unread['count'];
        $stmt2->close();
    }
    
    // Obtener último mensaje
    $stmt3 = $conn->prepare("SELECT mensaje, fecha_registro, es_comprador FROM mensajes 
                            WHERE chat_id = ? ORDER BY fecha_registro DESC LIMIT 1");
    $stmt3->bind_param("i", $row['chat_id']);
    $stmt3->execute();
    $last_msg_result = $stmt3->get_result();
    $row['ultimo_mensaje'] = $last_msg_result->fetch_assoc();
    $stmt3->close();
    
    $chats[] = $row;
}
$stmt->close();

// Ordenar por fecha del último mensaje
usort($chats, function($a, $b) {
    $timeA = $a['ultimo_mensaje'] ? strtotime($a['ultimo_mensaje']['fecha_registro']) : 0;
    $timeB = $b['ultimo_mensaje'] ? strtotime($b['ultimo_mensaje']['fecha_registro']) : 0;
    return $timeB - $timeA; // Más reciente primero
});

// Contar total de mensajes no leídos
$total_no_leidos = 0;
foreach ($chats as $chat) {
    $total_no_leidos += $chat['mensajes_no_leidos'];
}

$conn->close();

echo json_encode([
    'success' => true, 
    'chats' => $chats,
    'total_no_leidos' => $total_no_leidos
]);
?>
