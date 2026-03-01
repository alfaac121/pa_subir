<?php
/**
 * Script de prueba para el sistema de cierre automático
 * Muestra información sobre chats confirmados y cuándo se cerrarán
 */

require_once '../config.php';
require_once 'config_cierre_automatico.php';

$dias_espera = defined('DIAS_ESPERA_CIERRE') ? DIAS_ESPERA_CIERRE : 7;

$conn = getDBConnection();

// Obtener chats confirmados que aún no están cerrados
// Usamos fecha_venta como referencia
$query = "
    SELECT 
        c.id,
        c.fecha_venta,
        TIMESTAMPDIFF(DAY, c.fecha_venta, NOW()) as dias_transcurridos,
        ? - TIMESTAMPDIFF(DAY, c.fecha_venta, NOW()) as dias_restantes,
        p.nombre as producto_nombre,
        u_comprador.nickname as comprador,
        u_vendedor.nickname as vendedor
    FROM chats c
    INNER JOIN productos p ON c.producto_id = p.id
    INNER JOIN usuarios u_comprador ON c.comprador_id = u_comprador.id
    INNER JOIN usuarios u_vendedor ON p.vendedor_id = u_vendedor.id
    WHERE c.fecha_venta IS NOT NULL 
    AND c.estado_id != 8 
    AND c.estado_id != 3
    ORDER BY c.fecha_venta DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $dias_espera);
$stmt->execute();
$result = $stmt->get_result();

$chats = [];
while ($chat = $result->fetch_assoc()) {
    $chats[] = $chat;
}

$stmt->close();

// Obtener chats ya cerrados automáticamente
$query_cerrados = "
    SELECT 
        c.id,
        c.fecha_venta,
        p.nombre as producto_nombre,
        u_comprador.nickname as comprador,
        u_vendedor.nickname as vendedor
    FROM chats c
    INNER JOIN productos p ON c.producto_id = p.id
    INNER JOIN usuarios u_comprador ON c.comprador_id = u_comprador.id
    INNER JOIN usuarios u_vendedor ON p.vendedor_id = u_vendedor.id
    WHERE c.fecha_venta IS NOT NULL 
    AND c.estado_id = 8
    ORDER BY c.fecha_venta DESC
    LIMIT 10
";

$result_cerrados = $conn->query($query_cerrados);
$chats_cerrados = [];
while ($chat = $result_cerrados->fetch_assoc()) {
    $chats_cerrados[] = $chat;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test - Cierre Automático de Chats</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        .config-box {
            background: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .config-box h3 {
            margin-top: 0;
            color: #667eea;
        }
        table {
            width: 100%;
            background: white;
            border-collapse: collapse;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        th {
            background: #667eea;
            color: white;
            padding: 1rem;
            text-align: left;
        }
        td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        tr:hover {
            background: #f9f9f9;
        }
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        .btn {
            background: #667eea;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #5568d3;
        }
        .empty {
            text-align: center;
            padding: 3rem;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔄 Test - Sistema de Cierre Automático</h1>
        <p>Monitoreo de chats confirmados y su estado de cierre</p>
    </div>

    <div class="config-box">
        <h3>⚙️ Configuración Actual</h3>
        <p><strong>Días de espera configurados:</strong> <?= $dias_espera ?> días</p>
        <p><strong>Chats pendientes de cierre:</strong> <?= count($chats) ?></p>
        <p><strong>Chats cerrados automáticamente:</strong> <?= count($chats_cerrados) ?></p>
        <br>
        <a href="cerrar_chats_automatico.php" class="btn">▶️ Ejecutar Cierre Automático Ahora</a>
        <button onclick="location.reload()" class="btn" style="background: #28a745;">🔄 Actualizar</button>
    </div>

    <h2>📋 Chats Confirmados (Pendientes de Cierre)</h2>
    <?php if (count($chats) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID Chat</th>
                    <th>Producto</th>
                    <th>Comprador → Vendedor</th>
                    <th>Fecha Confirmación</th>
                    <th>Días Transcurridos</th>
                    <th>Días Restantes</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($chats as $chat): ?>
                    <tr>
                        <td>#<?= $chat['id'] ?></td>
                        <td><?= htmlspecialchars($chat['producto_nombre']) ?></td>
                        <td><?= htmlspecialchars($chat['comprador']) ?> → <?= htmlspecialchars($chat['vendedor']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($chat['fecha_venta'])) ?></td>
                        <td><?= $chat['dias_transcurridos'] ?> días</td>
                        <td>
                            <?php if ($chat['dias_restantes'] > 0): ?>
                                <span class="badge badge-success"><?= $chat['dias_restantes'] ?> días</span>
                            <?php else: ?>
                                <span class="badge badge-danger">¡Listo para cerrar!</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($chat['dias_restantes'] > 2): ?>
                                <span class="badge badge-success">Activo</span>
                            <?php elseif ($chat['dias_restantes'] > 0): ?>
                                <span class="badge badge-warning">Próximo a cerrar</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Debe cerrarse</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty">
            <p>✅ No hay chats pendientes de cierre</p>
        </div>
    <?php endif; ?>

    <h2>🔒 Últimos Chats Cerrados Automáticamente</h2>
    <?php if (count($chats_cerrados) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID Chat</th>
                    <th>Producto</th>
                    <th>Comprador → Vendedor</th>
                    <th>Fecha Confirmación</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($chats_cerrados as $chat): ?>
                    <tr>
                        <td>#<?= $chat['id'] ?></td>
                        <td><?= htmlspecialchars($chat['producto_nombre']) ?></td>
                        <td><?= htmlspecialchars($chat['comprador']) ?> → <?= htmlspecialchars($chat['vendedor']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($chat['fecha_venta'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty">
            <p>📭 Aún no se han cerrado chats automáticamente</p>
        </div>
    <?php endif; ?>
</body>
</html>
