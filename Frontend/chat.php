<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Usuario autenticado

$user = getCurrentUser();
$chat_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($chat_id <= 0) {
    header('Location: index.php');
    exit;
}

$conn = getDBConnection();

// Obtener información del chat
$stmt = $conn->prepare("SELECT c.*, 
       p.nombre AS producto_nombre, 
       p.precio AS producto_precio, 
       p.id AS producto_id,
       u_comprador.nickname AS comprador_nombre, 
       u_comprador.id AS comprador_id,
       u_vendedor.nickname AS vendedor_nombre, 
       u_vendedor.id AS vendedor_id
FROM chats c
INNER JOIN productos p ON c.producto_id = p.id
INNER JOIN usuarios u_comprador ON c.comprador_id = u_comprador.id
INNER JOIN usuarios u_vendedor ON p.vendedor_id = u_vendedor.id
WHERE c.id = ?");

if (!$stmt) {
    die("Error en prepare: " . $conn->error);
}
    

$stmt->bind_param("i", $chat_id);
$stmt->execute();
$result = $stmt->get_result();
$chat = $result->fetch_assoc();
$stmt->close();

if (!$chat) {
    header('Location: index.php');
    exit;
}

// Verificar que el usuario es parte del chat
$es_comprador = $user['id'] == $chat['comprador_id'];
$es_vendedor = $user['id'] == $chat['vendedor_id'];

if (!$es_comprador && !$es_vendedor) {
    header('Location: index.php');
    exit;
}
if ($es_comprador) {
    $stmt = $conn->prepare("UPDATE chats SET visto_comprador = 1 WHERE id = ?");
} else {
    $stmt = $conn->prepare("UPDATE chats SET visto_vendedor = 1 WHERE id = ?");
}
$stmt->bind_param("i", $chat_id);
$stmt->execute();
$stmt->close();

// Obtener mensajes (los nuevos se cargan vía AJAX)
$stmt = $conn->prepare("SELECT * FROM mensajes WHERE chat_id = ? ORDER BY fecha_registro ASC");
$stmt->bind_param("i", $chat_id);
$stmt->execute();
$mensajes_result = $stmt->get_result();
$stmt->close();

// NO cerrar la conexión aquí, la necesitamos para verificar respuestas
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - Tu Mercado SENA</title>
    <link rel="stylesheet" href="styles.css?v=<?= time(); ?>">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <?php include 'includes/bottom_nav.php'; ?>

    <main class="main">
        <div class="container">
            <div class="chat-container">
                <div class="chat-header" style="position: relative;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <h2>
                            <?php echo htmlspecialchars($chat['producto_nombre']); ?> — 
                             <?php echo htmlspecialchars($es_comprador ? $chat['vendedor_nombre'] : $chat['comprador_nombre']); ?>
                            </h2>
                            <p>Precio: <?php echo formatPrice($chat['producto_precio']); ?></p>
                            <p>
                                <?php if ($es_comprador): ?>
                                    Vendedor: <?php echo htmlspecialchars($chat['vendedor_nombre']); ?>
                                <?php else: ?>
                                    Comprador: <?php echo htmlspecialchars($chat['comprador_nombre']); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <!-- Botón Confirmar Compra -->
                            <button type="button" 
                                    onclick="mostrarFormularioConfirmacion()" 
                                    class="btn-confirmar-compra"
                                    style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 0.6rem 1rem; border: none; border-radius: 8px; font-size: 0.9rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="ri-check-double-line"></i>
                                <span>Confirmar Compra</span>
                            </button>
                            
                            <!-- Botón Devolver -->
                            <button type="button" 
                                    onclick="mostrarFormularioDevolucion()" 
                                    class="btn-devolucion-header"
                                    style="background: linear-gradient(135deg, #FFC107 0%, #FFB300 100%); color: white; padding: 0.6rem 1rem; border: none; border-radius: 8px; font-size: 0.9rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="ri-arrow-go-back-line"></i>
                                <span>Devolver</span>
                            </button>
                            
                            <button type="button" 
                                    onclick="toggleSilencio(<?php echo $chat_id; ?>, this)" 
                                    class="btn-small" 
                                    style="background: var(--color-background); border: 1px solid var(--color-border); color: var(--color-text);">
                                <i class="ri-notification-3-line"></i>
                                <span id="txtSilencio"><?php echo ($es_comprador ? $chat['silenciado_comprador'] : $chat['silenciado_vendedor']) ? 'Silenciado' : 'Silenciar'; ?></span>
                            </button>
                        </div>
                    </div>
                </div>

                
                <div class="chat-messages" id="chatMessages">
                    <?php 
                    $last_message_id = 0;
                    while ($mensaje = $mensajes_result->fetch_assoc()): 
                        $last_message_id = max($last_message_id, $mensaje['id']);
                        $message_class = ($mensaje['es_comprador'] == 1 && $es_comprador) || 
                                         ($mensaje['es_comprador'] == 0 && $es_vendedor) ? 'message-sent' : 'message-received';
                        // Verificar si es solicitud de devolución o confirmación
                        $es_solicitud_devolucion = strpos($mensaje['mensaje'], '🔄 SOLICITUD DE DEVOLUCIÓN') !== false;
                        $es_solicitud_confirmacion = strpos($mensaje['mensaje'], '💰 SOLICITUD DE CONFIRMACIÓN') !== false;
                        
                        // Verificar si ya tiene respuesta
                        $tiene_respuesta = false;
                        if ($es_solicitud_devolucion || $es_solicitud_confirmacion) {
                            $patron_respuesta = $es_solicitud_devolucion ? 
                                '%✅ DEVOLUCIÓN ACEPTADA%' : '%✅ COMPRA CONFIRMADA%';
                            $patron_rechazo = $es_solicitud_devolucion ? 
                                '%❌ DEVOLUCIÓN RECHAZADA%' : '%❌ COMPRA RECHAZADA%';
                            
                            $stmt_check = $conn->prepare("
                                SELECT COUNT(*) as tiene_respuesta
                                FROM mensajes 
                                WHERE chat_id = ? 
                                AND id > ?
                                AND (mensaje LIKE ? OR mensaje LIKE ?)
                            ");
                            $stmt_check->bind_param("iiss", $chat_id, $mensaje['id'], $patron_respuesta, $patron_rechazo);
                            $stmt_check->execute();
                            $result_check = $stmt_check->get_result();
                            $row_check = $result_check->fetch_assoc();
                            $tiene_respuesta = ($row_check['tiene_respuesta'] > 0);
                            $stmt_check->close();
                        }
                        
                        // Mostrar botones según quién recibe el mensaje
                        // CONFIRMACIÓN: El vendedor solicita, el comprador responde
                        // DEVOLUCIÓN: El comprador solicita, el vendedor responde
                        
                        // Si es solicitud de confirmación Y el mensaje lo envió el vendedor (es_comprador=0)
                        // Y yo soy el comprador, entonces veo los botones
                        $mostrar_botones_confirmacion = $es_solicitud_confirmacion && !$tiene_respuesta && ($mensaje['es_comprador'] == 0) && $es_comprador;
                        
                        // Si es solicitud de devolución Y el mensaje lo envió el comprador (es_comprador=1)
                        // Y yo soy el vendedor, entonces veo los botones
                        $mostrar_botones_devolucion = $es_solicitud_devolucion && !$tiene_respuesta && ($mensaje['es_comprador'] == 1) && $es_vendedor;
                        
                        // DEBUG
                        if ($es_solicitud_confirmacion || $es_solicitud_devolucion) {
                            echo "<!-- DEBUG: ";
                            echo "tipo=" . ($es_solicitud_confirmacion ? 'CONFIRMACION' : 'DEVOLUCION') . ", ";
                            echo "tiene_respuesta=" . ($tiene_respuesta ? 'SI' : 'NO') . ", ";
                            echo "mensaje_es_comprador=" . $mensaje['es_comprador'] . ", ";
                            echo "yo_soy_comprador=" . ($es_comprador ? 'SI' : 'NO') . ", ";
                            echo "yo_soy_vendedor=" . ($es_vendedor ? 'SI' : 'NO') . ", ";
                            echo "mostrar_botones_conf=" . ($mostrar_botones_confirmacion ? 'SI' : 'NO') . ", ";
                            echo "mostrar_botones_dev=" . ($mostrar_botones_devolucion ? 'SI' : 'NO');
                            echo " -->";
                        }
                    ?>
                        <div id="message-<?php echo $mensaje['id']; ?>" class="message <?php echo $message_class; ?>">
                            <p><?php echo nl2br(htmlspecialchars($mensaje['mensaje'])); ?></p>
                            <?php

?>
<span class="message-time"><?php echo formato_tiempo_relativo($mensaje['fecha_registro']); ?></span>
                            
                            <?php if ($mostrar_botones_devolucion): ?>
                                <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                                    <button onclick="responderDevolucion('aceptar')" style="flex: 1; padding: 0.75rem; background: #28a745; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                                        <i class="ri-check-line"></i> Aceptar
                                    </button>
                                    <button onclick="responderDevolucion('rechazar')" style="flex: 1; padding: 0.75rem; background: #dc3545; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                                        <i class="ri-close-line"></i> Rechazar
                                    </button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($mostrar_botones_confirmacion): ?>
                                <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                                    <button onclick="responderConfirmacion('confirmar')" style="flex: 1; padding: 0.75rem; background: #28a745; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                                        <i class="ri-check-line"></i> Confirmar
                                    </button>
                                    <button onclick="responderConfirmacion('rechazar')" style="flex: 1; padding: 0.75rem; background: #dc3545; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                                        <i class="ri-close-line"></i> Rechazar
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <?php $conn->close(); // Cerrar conexión después de procesar mensajes ?>
                
                <div class="chat-input">
                    <form class="message-form" id="messageForm">
                        <textarea name="mensaje" id="messageInput" placeholder="Escribe un mensaje..." required rows="2"></textarea>
                        <button type="submit" class="btn-primary">Enviar</button>
                    </form>
                </div>
                <script>
                    // Guardar último ID de mensaje para AJAX
                    window.lastMessageId = <?php echo $last_message_id; ?>;
                    window.chatId = <?php echo $chat_id; ?>;
                </script>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Tu Mercado SENA. Todos los derechos reservados.</p>
        </div>
    </footer>
    <script>
    function toggleSilencio(id, btn) {
        fetch(`api/toggle_silencio.php?id=${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const txtSpan = document.getElementById('txtSilencio');
                    const icon = btn.querySelector('i');
                    if (data.silenciado) {
                        txtSpan.textContent = 'Silenciado';
                        icon.className = 'ri-notification-3-off-line';
                    } else {
                        txtSpan.textContent = 'Silenciar';
                        icon.className = 'ri-notification-3-line';
                    }
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(err => alert('Error de conexion'));
    }
    
    // SISTEMA DE CONFIRMACIÓN
    function mostrarFormularioConfirmacion() {
        const detalles = prompt('Ingresa los detalles de la venta:\n\nEjemplo: "Precio: $50,000 - Cantidad: 2"');
        if (!detalles || detalles.trim() === '') return;
        
        if (confirm('¿Enviar solicitud de confirmación?')) {
            solicitarConfirmacion(detalles.trim());
        }
    }
    
    function solicitarConfirmacion(detalles) {
        const formData = new FormData();
        formData.append('chat_id', window.chatId);
        formData.append('detalles', detalles);
        
        fetch('api/solicitar_confirmacion.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error al enviar la solicitud');
        });
    }
    
    function responderConfirmacion(accion) {
        const msg = accion === 'confirmar' ? '¿Confirmar esta compra?' : '¿Rechazar esta compra?';
        if (!confirm(msg)) return;
        
        const formData = new FormData();
        formData.append('chat_id', window.chatId);
        formData.append('accion', accion);
        
        fetch('api/responder_confirmacion.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error al procesar la respuesta');
        });
    }
    
    // SISTEMA DE DEVOLUCIÓN
    function mostrarFormularioDevolucion() {
        const motivo = prompt('¿Por qué deseas devolver este producto?\n\nEscribe el motivo:');
        if (!motivo || motivo.trim() === '') return;
        
        if (confirm('¿Solicitar la devolución?')) {
            solicitarDevolucion(motivo.trim());
        }
    }
    
    function solicitarDevolucion(motivo) {
        const formData = new FormData();
        formData.append('chat_id', window.chatId);
        formData.append('motivo', motivo);
        
        fetch('api/solicitar_devolucion.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error al enviar la solicitud');
        });
    }
    
    function responderDevolucion(accion) {
        const msg = accion === 'aceptar' ? '¿Aceptar la devolución?' : '¿Rechazar la devolución?';
        if (!confirm(msg)) return;
        
        const formData = new FormData();
        formData.append('chat_id', window.chatId);
        formData.append('accion', accion);
        
        fetch('api/responder_devolucion.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error al procesar la respuesta');
        });
    }
    </script>
    <script src="script.js"></script>
</body>
</html>




