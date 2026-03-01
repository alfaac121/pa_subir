<?php
/**
 * Script para cerrar automáticamente chats después de X días desde la confirmación
 * Este script debe ejecutarse periódicamente (por ejemplo, mediante cron job)
 * 
 * Configuración:
 * - DIAS_ESPERA: Número de días después de la confirmación para cerrar el chat
 * 
 * Uso manual: php cerrar_chats_automatico.php
 * Uso cron (diario a las 2 AM): 0 2 * * * php /ruta/a/cerrar_chats_automatico.php
 */

require_once '../config.php';
require_once 'config_cierre_automatico.php';

// Usar la configuración definida
$dias_espera = defined('DIAS_ESPERA_CIERRE') ? DIAS_ESPERA_CIERRE : 7;

$conn = getDBConnection();

// Buscar chats confirmados que hayan superado el tiempo de espera
// y que no estén ya cerrados (estado_id != 8)
// Usamos fecha_venta como referencia para el cierre automático
$query = "
    SELECT id, fecha_venta 
    FROM chats 
    WHERE fecha_venta IS NOT NULL 
    AND estado_id != 8 
    AND estado_id != 3
    AND TIMESTAMPDIFF(DAY, fecha_venta, NOW()) >= ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $dias_espera);
$stmt->execute();
$result = $stmt->get_result();

$chats_cerrados = 0;
$chats_ids = [];

while ($chat = $result->fetch_assoc()) {
    $chats_ids[] = $chat['id'];
}

$stmt->close();

// Cerrar los chats encontrados
if (count($chats_ids) > 0) {
    $placeholders = implode(',', array_fill(0, count($chats_ids), '?'));
    $update_query = "UPDATE chats SET estado_id = 8 WHERE id IN ($placeholders)";
    
    $stmt_update = $conn->prepare($update_query);
    
    // Bind dinámico de parámetros
    $types = str_repeat('i', count($chats_ids));
    $stmt_update->bind_param($types, ...$chats_ids);
    
    if ($stmt_update->execute()) {
        $chats_cerrados = $stmt_update->affected_rows;
    }
    
    $stmt_update->close();
}

$conn->close();

// Log del resultado
$fecha = date('Y-m-d H:i:s');
$mensaje = "[$fecha] Chats cerrados automáticamente: $chats_cerrados\n";

// Si se ejecuta desde línea de comandos, mostrar resultado
if (php_sapi_name() === 'cli') {
    echo $mensaje;
    if ($chats_cerrados > 0) {
        echo "IDs cerrados: " . implode(', ', $chats_ids) . "\n";
    }
}

// Guardar log en archivo
$log_file = __DIR__ . '/../logs/chats_automaticos.log';
$log_dir = dirname($log_file);

if (!file_exists($log_dir)) {
    mkdir($log_dir, 0755, true);
}

file_put_contents($log_file, $mensaje, FILE_APPEND);

// Retornar JSON si se llama desde web
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'chats_cerrados' => $chats_cerrados,
        'ids' => $chats_ids,
        'dias_espera' => $dias_espera
    ]);
}
?>
