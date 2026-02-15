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

// VERIFICAR SI HAY SOLICITUD DE DEVOLUCIÓN PENDIENTE (para vendedor)
// Buscar si existe un mensaje de solicitud sin respuesta
$tiene_devolucion_pendiente = false;
if ($es_vendedor) {
    $stmt_dev = $conn->prepare("
        SELECT COUNT(*) as tiene_solicitud
        FROM mensajes 
        WHERE chat_id = ? 
        AND mensaje LIKE '%🔄 SOLICITUD DE DEVOLUCIÓN%'
        AND id > (
            SELECT COALESCE(MAX(id), 0) 
            FROM mensajes 
            WHERE chat_id = ? 
            AND (mensaje LIKE '%✅ DEVOLUCIÓN ACEPTADA%' OR mensaje LIKE '%❌ DEVOLUCIÓN RECHAZADA%')
        )
    ");
    $stmt_dev->bind_param("ii", $chat_id, $chat_id);
    $stmt_dev->execute();
    $result_dev = $stmt_dev->get_result();
    $row_dev = $result_dev->fetch_assoc();
    $tiene_devolucion_pendiente = ($row_dev['tiene_solicitud'] > 0);
    $stmt_dev->close();
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

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - Tu Mercado SENA</title>
    <link rel="stylesheet" href="styles.css?v=<?= time(); ?>">
    <style>
        /* Ocultar SOLO los botones de devolución que NO estén en el header */
        .chat-input .btn-devolucion,
        .chat-input button[onclick*="mostrarFormularioDevolucion"],
        .chat-input button[onclick*="Solicitar"],
        .chat-input button[onclick*="solicitar"] {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            height: 0 !important;
            width: 0 !important;
            overflow: hidden !important;
            position: absolute !important;
            left: -9999px !important;
        }
        
        /* Asegurar que los botones del header SIEMPRE sean visibles */
        .chat-header .btn-confirmar-compra,
        .chat-header .btn-devolucion-header {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            position: relative !important;
            left: auto !important;
            height: auto !important;
            width: auto !important;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <?php include 'includes/bottom_nav.php'; ?>

    <main class="main">
        <div class="container">
            <div class="chat-container">
                <div class="chat-header" style="position: relative;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;">
                        <div style="flex: 1;">
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
                        
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: flex-start;">
                            <!-- DEBUG: Mostrar valores de las variables -->
                            <!-- Usuario ID: <?php echo $user['id']; ?> | Vendedor ID: <?php echo $chat['vendedor_id']; ?> | Es Vendedor: <?php echo $es_vendedor ? 'SI' : 'NO'; ?> -->
                            
                            <!-- Botón de confirmar compra (SIEMPRE VISIBLE) -->
                            <button type="button" 
                                    onclick="mostrarFormularioConfirmacion()" 
                                    class="btn-confirmar-compra"
                                    style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important; color: white !important; padding: 0.6rem 1rem !important; border: none !important; border-radius: 8px !important; font-size: 0.9rem !important; font-weight: 600 !important; cursor: pointer !important; display: flex !important; align-items: center !important; gap: 0.5rem !important; box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3) !important; visibility: visible !important; opacity: 1 !important;"
                                    title="Solicitar confirmación de compra">
                                <i class="ri-check-double-line"></i>
                                <span>Confirmar Compra</span>
                            </button>
                            
                            <!-- Botón de solicitar devolución en el header (SIEMPRE VISIBLE) -->
                            <button type="button" 
                                    onclick="mostrarFormularioDevolucion()" 
                                    class="btn-devolucion-header"
                                    title="Solicitar devolución">
                                <i class="ri-arrow-go-back-line"></i>
                                <span class="btn-text">Devolver</span>
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
                        
                        // Verificar si es un mensaje de solicitud de devolución
                        $es_solicitud_devolucion = strpos($mensaje['mensaje'], '🔄 SOLICITUD DE DEVOLUCIÓN') !== false;
                        $tiene_respuesta_devolucion = false;
                        
                        // Si es solicitud de devolución, verificar si ya tiene respuesta
                        if ($es_solicitud_devolucion) {
                            $stmt_check = $conn->prepare("
                                SELECT COUNT(*) as tiene_respuesta
                                FROM mensajes 
                                WHERE chat_id = ? 
                                AND id > ?
                                AND (mensaje LIKE '%✅ DEVOLUCIÓN ACEPTADA%' OR mensaje LIKE '%❌ DEVOLUCIÓN RECHAZADA%')
                            ");
                            $stmt_check->bind_param("ii", $chat_id, $mensaje['id']);
                            $stmt_check->execute();
                            $result_check = $stmt_check->get_result();
                            $row_check = $result_check->fetch_assoc();
                            $tiene_respuesta_devolucion = ($row_check['tiene_respuesta'] > 0);
                            $stmt_check->close();
                        }
                        
                        // Verificar si es un mensaje de solicitud de confirmación de compra
                        $es_solicitud_confirmacion = strpos($mensaje['mensaje'], '💰 SOLICITUD DE CONFIRMACIÓN DE COMPRA') !== false;
                        $tiene_respuesta_confirmacion = false;
                        
                        // Si es solicitud de confirmación, verificar si ya tiene respuesta
                        if ($es_solicitud_confirmacion) {
                            $stmt_check2 = $conn->prepare("
                                SELECT COUNT(*) as tiene_respuesta
                                FROM mensajes 
                                WHERE chat_id = ? 
                                AND id > ?
                                AND (mensaje LIKE '%✅ COMPRA CONFIRMADA%' OR mensaje LIKE '%❌ COMPRA RECHAZADA%')
                            ");
                            $stmt_check2->bind_param("ii", $chat_id, $mensaje['id']);
                            $stmt_check2->execute();
                            $result_check2 = $stmt_check2->get_result();
                            $row_check2 = $result_check2->fetch_assoc();
                            $tiene_respuesta_confirmacion = ($row_check2['tiene_respuesta'] > 0);
                            $stmt_check2->close();
                        }
                        
                        // Determinar si debo mostrar botones según quién RECIBE el mensaje
                        // Devolución: la envía el comprador (es_comprador=1), la recibe el vendedor
                        // Confirmación: la envía el vendedor (es_comprador=0), la recibe el comprador
                        
                        // Si el mensaje lo envió el comprador (es_comprador=1) y yo soy vendedor, veo botones de devolución
                        $mostrar_botones_devolucion = $es_solicitud_devolucion && !$tiene_respuesta_devolucion && ($mensaje['es_comprador'] == 1) && !$es_comprador;
                        
                        // Si el mensaje lo envió el vendedor (es_comprador=0) y yo soy comprador, veo botones de confirmación
                        $mostrar_botones_confirmacion = $es_solicitud_confirmacion && !$tiene_respuesta_confirmacion && ($mensaje['es_comprador'] == 0) && $es_comprador;
                    ?>
                        <div id="message-<?php echo $mensaje['id']; ?>" class="message <?php echo $message_class; ?>">
                            <p><?php echo nl2br(htmlspecialchars($mensaje['mensaje'])); ?></p>
                            <span class="message-time"><?php echo formato_tiempo_relativo($mensaje['fecha_registro']); ?></span>
                            
                            <?php if ($mostrar_botones_devolucion): ?>
                                <!-- Botones de respuesta para el vendedor (devolución) -->
                                <div style="display: flex; gap: 0.5rem; margin-top: 1rem; flex-wrap: wrap;">
                                    <button onclick="responderDevolucion('aceptar')" class="btn-success" style="flex: 1; min-width: 120px; padding: 0.75rem; font-size: 0.95rem; font-weight: 600;">
                                        <i class="ri-check-line"></i> Aceptar
                                    </button>
                                    <button onclick="responderDevolucion('rechazar')" class="btn-danger" style="flex: 1; min-width: 120px; padding: 0.75rem; font-size: 0.95rem; font-weight: 600;">
                                        <i class="ri-close-line"></i> Rechazar
                                    </button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($mostrar_botones_confirmacion): ?>
                                <!-- Botones de respuesta para el comprador (confirmación) -->
                                <div style="display: flex; gap: 0.5rem; margin-top: 1rem; flex-wrap: wrap;">
                                    <button onclick="responderConfirmacion('confirmar')" class="btn-success" style="flex: 1; min-width: 120px; padding: 0.75rem; font-size: 0.95rem; font-weight: 600;">
                                        <i class="ri-check-line"></i> Confirmar
                                    </button>
                                    <button onclick="responderConfirmacion('rechazar')" class="btn-danger" style="flex: 1; min-width: 120px; padding: 0.75rem; font-size: 0.95rem; font-weight: 600;">
                                        <i class="ri-close-line"></i> Rechazar
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
                
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
                    window.esComprador = <?php echo $es_comprador ? 'true' : 'false'; ?>;
                    window.esVendedor = <?php echo $es_vendedor ? 'true' : 'false'; ?>;
                    window.estadoChat = <?php echo $chat['estado_id']; ?>;
                    window.productoNombre = <?php echo json_encode($chat['producto_nombre']); ?>;
                    
                    // DEBUG: Información del usuario y chat
                    console.log('=== DEBUG CHAT ===');
                    console.log('Usuario actual ID:', <?php echo $user['id']; ?>);
                    console.log('Comprador ID:', <?php echo $chat['comprador_id']; ?>);
                    console.log('Vendedor ID:', <?php echo $chat['vendedor_id']; ?>);
                    console.log('Es comprador:', window.esComprador);
                    console.log('Es vendedor:', window.esVendedor);
                    console.log('Estado del chat:', window.estadoChat);
                    console.log('==================');
                    
                    // DEBUG: Verificar si el botón de confirmar compra existe
                    <?php if ($es_vendedor): ?>
                    console.log('✅ SOY VENDEDOR - El botón de Confirmar Compra DEBERÍA estar visible');
                    console.log('Valores PHP:', {
                        usuario_id: <?php echo $user['id']; ?>,
                        vendedor_id: <?php echo $chat['vendedor_id']; ?>,
                        es_vendedor: <?php echo $es_vendedor ? 'true' : 'false'; ?>
                    });
                    setTimeout(() => {
                        const btnConfirmar = document.querySelector('button[onclick="mostrarFormularioConfirmacion()"]');
                        const btnConfirmarClass = document.querySelector('.btn-confirmar-compra');
                        console.log('Búsqueda por onclick:', btnConfirmar);
                        console.log('Búsqueda por clase:', btnConfirmarClass);
                        
                        if (btnConfirmar || btnConfirmarClass) {
                            const btn = btnConfirmar || btnConfirmarClass;
                            console.log('✅ Botón de Confirmar Compra encontrado en el DOM');
                            console.log('Estilos computados:', {
                                display: window.getComputedStyle(btn).display,
                                visibility: window.getComputedStyle(btn).visibility,
                                opacity: window.getComputedStyle(btn).opacity,
                                position: window.getComputedStyle(btn).position
                            });
                            console.log('Elemento completo:', btn);
                        } else {
                            console.error('❌ Botón de Confirmar Compra NO encontrado en el DOM');
                            console.log('Todos los botones en el header:', document.querySelectorAll('.chat-header button'));
                        }
                    }, 500);
                    <?php else: ?>
                    console.log('❌ NO SOY VENDEDOR - El botón de Confirmar Compra NO debería aparecer');
                    <?php endif; ?>
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

    // Sistema de devoluciones en el chat
    function mostrarFormularioDevolucion() {
        const motivo = prompt('¿Por qué deseas devolver este producto?\n\nEscribe el motivo de la devolución:');
        
        if (!motivo || motivo.trim() === '') {
            return;
        }
        
        if (confirm('¿Estás seguro de solicitar la devolución de este producto?')) {
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
                // Recargar para ver el mensaje en el chat
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => {
            console.error('Error completo:', err);
            alert('Error al enviar la solicitud');
        });
    }

    function responderDevolucion(accion) {
        const textoAccion = accion === 'aceptar' ? 'aceptar' : 'rechazar';
        const confirmMsg = accion === 'aceptar' 
            ? '¿Estás seguro de ACEPTAR esta devolución?\n\nEl producto será devuelto al comprador.'
            : '¿Estás seguro de RECHAZAR esta devolución?\n\nLa venta permanecerá activa.';
        
        if (!confirm(confirmMsg)) {
            return;
        }
        
        const respuesta = prompt(
            accion === 'aceptar' 
                ? '¿Deseas agregar un mensaje al aceptar la devolución? (opcional)' 
                : '¿Por qué rechazas la devolución? (opcional)'
        );
        
        const formData = new FormData();
        formData.append('chat_id', window.chatId);
        formData.append('accion', accion);
        if (respuesta) formData.append('respuesta', respuesta.trim());
        
        // Deshabilitar botones mientras se procesa
        const notification = document.getElementById('devolucionNotification');
        const buttons = notification.querySelectorAll('button');
        buttons.forEach(btn => {
            btn.disabled = true;
            btn.style.opacity = '0.6';
        });
        
        fetch('api/responder_devolucion.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Ocultar notificación flotante
                notification.style.display = 'none';
                
                // Agregar mensaje de sistema
                const mensaje = accion === 'aceptar' 
                    ? '✅ Devolución aceptada' 
                    : '❌ Devolución rechazada';
                agregarMensajeSistema(mensaje, respuesta || '');
                
                // Actualizar estado
                window.estadoChat = accion === 'aceptar' ? 8 : 5;
                
                // Mostrar mensaje de éxito
                alert(data.message + '\n\nLa página se recargará para actualizar el estado.');
                
                // Recargar la página para actualizar todo el estado
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                alert('Error: ' + data.message);
                // Rehabilitar botones
                buttons.forEach(btn => {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                });
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error al procesar la respuesta');
            // Rehabilitar botones
            buttons.forEach(btn => {
                btn.disabled = false;
                btn.style.opacity = '1';
            });
        });
    }

    function agregarMensajeSistema(titulo, contenido) {
        const chatMessages = document.getElementById('chatMessages');
        const messageDiv = document.createElement('div');
        messageDiv.className = 'message message-system';
        messageDiv.innerHTML = `
            <div class="system-message-content">
                <strong>${titulo}</strong>
                ${contenido ? `<p>${contenido}</p>` : ''}
            </div>
            <span class="message-time">Ahora</span>
        `;
        chatMessages.appendChild(messageDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Verificar estado de devolución al cargar
    function verificarEstadoDevolucion() {
        console.log('Verificando estado de devolución...');
        console.log('Estado del chat:', window.estadoChat);
        console.log('Es vendedor:', window.esVendedor);
        
        if (window.estadoChat === 7 && window.esVendedor) {
            // Hay una solicitud de devolución pendiente y soy el vendedor
            console.log('✅ Mostrando notificación de devolución');
            mostrarNotificacionDevolucion();
        } else {
            console.log('❌ No se muestra notificación:', {
                estadoEs7: window.estadoChat === 7,
                esVendedor: window.esVendedor
            });
        }
    }

    function mostrarNotificacionDevolucion() {
        const notification = document.getElementById('devolucionNotification');
        const titulo = document.getElementById('devolucionTitulo');
        const motivo = document.getElementById('devolucionMotivo');
        const actions = document.getElementById('devolucionActions');
        
        titulo.textContent = '⚠️ Solicitud de devolución pendiente';
        motivo.textContent = `El comprador ha solicitado devolver "${window.productoNombre}". Debes aceptar o rechazar esta solicitud.`;
        
        actions.innerHTML = `
            <button onclick="responderDevolucion('aceptar')" class="btn-success" style="padding: 0.75rem 1.5rem; font-size: 1rem; font-weight: 600;">
                <i class="ri-check-line"></i> Aceptar Devolución
            </button>
            <button onclick="responderDevolucion('rechazar')" class="btn-danger" style="padding: 0.75rem 1.5rem; font-size: 1rem; font-weight: 600;">
                <i class="ri-close-line"></i> Rechazar Devolución
            </button>
        `;
        
        notification.style.display = 'block';
        
        // Reproducir sonido de notificación
        if (typeof playNotificationSound === 'function') {
            playNotificationSound();
        }
        
        // Scroll hacia la notificación para asegurar que se vea
        setTimeout(() => {
            notification.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 100);
    }

    // Ejecutar al cargar la página
    document.addEventListener('DOMContentLoaded', verificarEstadoDevolucion);
    
    // ALERTA INMEDIATA AL CARGAR (si hay devolución pendiente)
    <?php if ($tiene_devolucion_pendiente): ?>
    document.addEventListener('DOMContentLoaded', function() {
        // Esperar 500ms para que todo cargue
        setTimeout(function() {
            // Mostrar modal de alerta
            const modalHTML = `
                <div id="alertaDevolucionModal" style="
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.8);
                    z-index: 99999;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    animation: fadeIn 0.3s ease;
                ">
                    <div style="
                        background: linear-gradient(135deg, #FFF3CD 0%, #FFE69C 100%);
                        border: 4px solid #FFC107;
                        border-radius: 20px;
                        padding: 2.5rem;
                        max-width: 500px;
                        width: 90%;
                        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
                        animation: slideIn 0.4s ease;
                        text-align: center;
                    ">
                        <div style="font-size: 4rem; margin-bottom: 1rem;">⚠️</div>
                        <h2 style="color: #856404; margin: 0 0 1rem 0; font-size: 1.8rem;">
                            ¡Solicitud de Devolución!
                        </h2>
                        <p style="color: #856404; font-size: 1.1rem; margin-bottom: 2rem; line-height: 1.5;">
                            El comprador ha solicitado devolver<br>
                            <strong>"<?php echo htmlspecialchars($chat['producto_nombre']); ?>"</strong>
                        </p>
                        <div style="display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center;">
                            <button onclick="cerrarAlertaYResponder('aceptar')" style="
                                padding: 1rem 2rem;
                                background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
                                color: white;
                                border: none;
                                border-radius: 10px;
                                font-size: 1.1rem;
                                font-weight: 700;
                                cursor: pointer;
                                box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
                                transition: all 0.3s ease;
                                min-width: 150px;
                            " onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                ✅ Aceptar
                            </button>
                            <button onclick="cerrarAlertaYResponder('rechazar')" style="
                                padding: 1rem 2rem;
                                background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
                                color: white;
                                border: none;
                                border-radius: 10px;
                                font-size: 1.1rem;
                                font-weight: 700;
                                cursor: pointer;
                                box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
                                transition: all 0.3s ease;
                                min-width: 150px;
                            " onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                ❌ Rechazar
                            </button>
                        </div>
                        <button onclick="cerrarAlerta()" style="
                            margin-top: 1.5rem;
                            padding: 0.5rem 1rem;
                            background: transparent;
                            color: #856404;
                            border: 2px solid #856404;
                            border-radius: 8px;
                            font-size: 0.9rem;
                            cursor: pointer;
                            transition: all 0.3s ease;
                        " onmouseover="this.style.background='#856404'; this.style.color='white'" onmouseout="this.style.background='transparent'; this.style.color='#856404'">
                            Decidir después
                        </button>
                    </div>
                </div>
                <style>
                    @keyframes fadeIn {
                        from { opacity: 0; }
                        to { opacity: 1; }
                    }
                    @keyframes slideIn {
                        from { transform: scale(0.8) translateY(-50px); opacity: 0; }
                        to { transform: scale(1) translateY(0); opacity: 1; }
                    }
                </style>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            
            // Reproducir sonido
            if (typeof playNotificationSound === 'function') {
                playNotificationSound();
            }
        }, 500);
    });
    
    function cerrarAlerta() {
        const modal = document.getElementById('alertaDevolucionModal');
        if (modal) {
            modal.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => modal.remove(), 300);
        }
    }
    
    function cerrarAlertaYResponder(accion) {
        cerrarAlerta();
        setTimeout(() => {
            responderDevolucion(accion);
        }, 400);
    }
    <?php endif; ?>

    // ============================================
    // SISTEMA DE CONFIRMACIÓN DE COMPRA
    // ============================================
    
    function mostrarFormularioConfirmacion() {
        const motivo = prompt('Ingresa los detalles de la venta:\n\nEjemplo: "Precio: $50,000 - Cantidad: 2 unidades"');
        
        if (!motivo || motivo.trim() === '') {
            return;
        }
        
        if (confirm('¿Enviar solicitud de confirmación al comprador?')) {
            solicitarConfirmacion(motivo.trim());
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
                // Recargar para ver el mensaje en el chat
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => {
            console.error('Error completo:', err);
            alert('Error al enviar la solicitud');
        });
    }

    function responderConfirmacion(accion) {
        const textoAccion = accion === 'confirmar' ? 'confirmar' : 'rechazar';
        const confirmMsg = accion === 'confirmar' 
            ? '¿Estás seguro de CONFIRMAR esta compra?'
            : '¿Estás seguro de RECHAZAR esta compra?';
        
        if (!confirm(confirmMsg)) {
            return;
        }
        
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
                // Recargar para actualizar
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
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




