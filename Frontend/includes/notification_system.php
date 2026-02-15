<?php
/**
 * Sistema de Notificaciones Automáticas
 * RF05-003, RF05-005, RF05-006 - Tu Mercado SENA
 * 
 * Este archivo contiene funciones para enviar notificaciones automáticas
 * ante eventos del sistema (mensajes, ventas, devoluciones, etc.)
 */

// Cargar funciones de email solo si existe (no crítico)
@require_once __DIR__ . '/email_functions.php';

/**
 * Envía notificaciones automáticas según las preferencias del usuario
 * 
 * @param int $usuario_id ID del usuario destinatario
 * @param string $tipo Tipo de notificación ('mensaje', 'venta', 'devolucion', etc.)
 * @param array $datos Datos adicionales para la notificación
 * @return array Resultado del envío
 */
function enviarNotificacionAutomatica($usuario_id, $tipo, $datos = []) {
    // Validar parámetros básicos
    if (empty($usuario_id) || empty($tipo)) {
        return ['success' => false, 'message' => 'Parámetros inválidos'];
    }
    
    try {
        $conn = getDBConnection();
        
        // Obtener preferencias del usuario
        $stmt = $conn->prepare("
            SELECT c.email, c.notifica_correo, c.notifica_push, u.nickname
            FROM usuarios u
            INNER JOIN cuentas c ON u.cuenta_id = c.id
            WHERE u.id = ?
        ");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuario = $result->fetch_assoc();
        $stmt->close();
        
        if (!$usuario) {
            $conn->close();
            return ['success' => false, 'message' => 'Usuario no encontrado'];
        }
        
        $resultado = [
            'email_enviado' => false,
            'push_enviado' => false,
            'notificacion_guardada' => false
        ];
        
        // Generar mensaje según el tipo
        $notificacion = generarMensajeNotificacion($tipo, $datos);
        
        // Guardar notificación en BD (siempre) - con manejo de errores
        $notificacion_id = 0;
        try {
            // Verificar si la tabla existe antes de insertar
            $checkTable = $conn->query("SHOW TABLES LIKE 'notificaciones'");
            if ($checkTable && $checkTable->num_rows > 0) {
                $stmt = $conn->prepare("
                    INSERT INTO notificaciones (usuario_id, motivo_id, mensaje, visto, fecha_registro)
                    VALUES (?, ?, ?, 0, NOW())
                ");
                $motivo_id = obtenerMotivoId($tipo);
                $mensaje_notif = $notificacion['mensaje'] ?? 'Notificación';
                $stmt->bind_param("iis", $usuario_id, $motivo_id, $mensaje_notif);
                if ($stmt->execute()) {
                    $notificacion_id = $conn->insert_id;
                    $resultado['notificacion_guardada'] = true;
                }
                $stmt->close();
            }
        } catch (Exception $e) {
            // Si falla guardar notificación, continuar de todas formas
            error_log("Error al guardar notificación en BD: " . $e->getMessage());
        }
    
        // Enviar correo si está habilitado (RF05-003, RF05-006)
        if ($usuario['notifica_correo'] == 1 && !empty($usuario['email']) && function_exists('enviarEmailNotificacion')) {
            try {
                $emailResult = enviarEmailNotificacion($usuario['email'], $usuario['nickname'], $notificacion);
                $resultado['email_enviado'] = $emailResult['success'] ?? false;
                $resultado['email_message'] = $emailResult['message'] ?? '';
            } catch (Exception $e) {
                error_log("Error al enviar email de notificación: " . $e->getMessage());
            }
        }
        
        // Preparar notificación push si está habilitado (RF05-004, RF05-005)
        if ($usuario['notifica_push'] == 1) {
            // La notificación push se manejará desde JavaScript
            // Guardamos en sesión o en una tabla para que el SW la procese
            $resultado['push_enviado'] = true;
            $resultado['push_data'] = [
                'title' => $notificacion['titulo'],
                'body' => $notificacion['mensaje'],
                'icon' => '/logo_new.png',
                'tag' => $tipo,
                'data' => [
                    'url' => $notificacion['url'] ?? '/',
                    'notificacion_id' => $notificacion_id ?? 0
                ]
            ];
        }
        
        $conn->close();
        
        return [
            'success' => true,
            'resultado' => $resultado,
            'notificacion_id' => $notificacion_id ?? 0
        ];
    } catch (Exception $e) {
        // Si hay cualquier error, retornar sin fallar
        error_log("Error en enviarNotificacionAutomatica: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error al enviar notificación: ' . $e->getMessage(),
            'resultado' => [
                'email_enviado' => false,
                'push_enviado' => false,
                'notificacion_guardada' => false
            ]
        ];
    }
}

/**
 * Genera el mensaje de notificación según el tipo
 */
function generarMensajeNotificacion($tipo, $datos) {
    $mensajes = [
        'mensaje' => [
            'titulo' => '💬 Nuevo mensaje',
            'mensaje' => $datos['remitente'] . ' te ha enviado un mensaje sobre: ' . $datos['producto'] ?? 'un producto',
            'url' => '/chat.php?id=' . ($datos['chat_id'] ?? '')
        ],
        'venta_finalizada' => [
            'titulo' => '✅ Venta finalizada',
            'mensaje' => 'Tu venta de "' . ($datos['producto'] ?? 'producto') . '" ha sido finalizada',
            'url' => '/historial.php'
        ],
        'compra_finalizada' => [
            'titulo' => '🛒 Compra finalizada',
            'mensaje' => 'Tu compra de "' . ($datos['producto'] ?? 'producto') . '" ha sido finalizada',
            'url' => '/historial.php'
        ],
        'devolucion_solicitada' => [
            'titulo' => '🔄 Solicitud de devolución',
            'mensaje' => $datos['comprador'] . ' ha solicitado devolución de "' . ($datos['producto'] ?? 'producto') . '"',
            'url' => '/historial.php'
        ],
        'devolucion_aceptada' => [
            'titulo' => '✅ Devolución aceptada',
            'mensaje' => 'Tu solicitud de devolución de "' . ($datos['producto'] ?? 'producto') . '" fue aceptada',
            'url' => '/historial.php'
        ],
        'devolucion_rechazada' => [
            'titulo' => '❌ Devolución rechazada',
            'mensaje' => 'Tu solicitud de devolución de "' . ($datos['producto'] ?? 'producto') . '" fue rechazada',
            'url' => '/historial.php'
        ],
        'producto_favorito' => [
            'titulo' => '⭐ Nuevo favorito',
            'mensaje' => 'Tu producto "' . ($datos['producto'] ?? 'producto') . '" fue añadido a favoritos',
            'url' => '/producto.php?id=' . ($datos['producto_id'] ?? '')
        ],
        'reporte_recibido' => [
            'titulo' => '⚠️ Reporte recibido',
            'mensaje' => 'Se ha recibido un reporte sobre tu producto "' . ($datos['producto'] ?? 'producto') . '"',
            'url' => '/mis_productos.php'
        ]
    ];
    
    return $mensajes[$tipo] ?? [
        'titulo' => '🔔 Notificación',
        'mensaje' => 'Tienes una nueva notificación',
        'url' => '/'
    ];
}

/**
 * Obtiene el ID del motivo según el tipo de notificación
 */
function obtenerMotivoId($tipo) {
    $motivos = [
        'mensaje' => 1,
        'venta_finalizada' => 2,
        'compra_finalizada' => 3,
        'devolucion_solicitada' => 14,
        'devolucion_aceptada' => 15,
        'devolucion_rechazada' => 16,
        'producto_favorito' => 4,
        'reporte_recibido' => 5
    ];
    
    return $motivos[$tipo] ?? 1;
}

/**
 * Envía correo de notificación (RF05-006)
 */
function enviarEmailNotificacion($email, $nombre, $notificacion) {
    // Verificar que la función sendEmail existe
    if (!function_exists('sendEmail')) {
        return ['success' => false, 'message' => 'Función sendEmail no disponible'];
    }
    
    $subject = ($notificacion['titulo'] ?? 'Notificación') . ' - Tu Mercado SENA';
    
    // Obtener URL base (definida en email_config.php o usar relativa)
    $appUrl = defined('APP_URL') ? APP_URL : '';
    $urlCompleta = $appUrl . ($notificacion['url'] ?? '/');
    
    $htmlBody = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
            .container { max-width: 500px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #2DC75C, #538392); color: white; padding: 30px; text-align: center; }
            .content { padding: 30px; }
            .btn { display: inline-block; background: #2DC75C; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin-top: 15px; }
            .footer { background: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>' . htmlspecialchars($notificacion['titulo'] ?? 'Notificación') . '</h1>
            </div>
            <div class="content">
                <p>Hola <strong>' . htmlspecialchars($nombre) . '</strong>,</p>
                <p>' . htmlspecialchars($notificacion['mensaje'] ?? 'Tienes una nueva notificación') . '</p>
                <a href="' . htmlspecialchars($urlCompleta) . '" class="btn">Ver detalles</a>
            </div>
            <div class="footer">
                <p>© ' . date('Y') . ' Tu Mercado SENA - Marketplace de la comunidad SENA</p>
                <p>Puedes desactivar estas notificaciones en tu perfil.</p>
            </div>
        </div>
    </body>
    </html>';
    
    try {
        return sendEmail($email, $subject, $htmlBody);
    } catch (Exception $e) {
        error_log("Error al llamar sendEmail: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Trigger para nuevo mensaje (se llama desde send_message.php)
 */
function notificarNuevoMensaje($chat_id, $remitente_id, $destinatario_id, $producto_nombre) {
    try {
        // Validar parámetros
        if (empty($chat_id) || empty($remitente_id) || empty($destinatario_id)) {
            return ['success' => false, 'message' => 'Parámetros inválidos'];
        }
        
        $conn = getDBConnection();
        
        // Obtener nombre del remitente
        $remitente_nombre = 'Un usuario';
        try {
            $stmt = $conn->prepare("SELECT nickname FROM usuarios WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $remitente_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $remitente = $result->fetch_assoc();
                if ($remitente && isset($remitente['nickname'])) {
                    $remitente_nombre = $remitente['nickname'];
                }
                $stmt->close();
            }
        } catch (Exception $e) {
            error_log("Error al obtener nombre del remitente: " . $e->getMessage());
        }
        $conn->close();
        
        $datos = [
            'remitente' => $remitente_nombre,
            'producto' => $producto_nombre ?? 'un producto',
            'chat_id' => $chat_id
        ];
        
        return enviarNotificacionAutomatica($destinatario_id, 'mensaje', $datos);
    } catch (Throwable $e) {
        error_log("Error en notificarNuevoMensaje: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Trigger para venta finalizada (se llama desde finalizar_venta.php)
 */
function notificarVentaFinalizada($chat_id, $vendedor_id, $comprador_id, $producto_nombre) {
    // Notificar al vendedor
    enviarNotificacionAutomatica($vendedor_id, 'venta_finalizada', [
        'producto' => $producto_nombre,
        'chat_id' => $chat_id
    ]);
    
    // Notificar al comprador
    enviarNotificacionAutomatica($comprador_id, 'compra_finalizada', [
        'producto' => $producto_nombre,
        'chat_id' => $chat_id
    ]);
}

/**
 * Trigger para devolución solicitada (se llama desde solicitar_devolucion.php)
 */
function notificarDevolucionSolicitada($vendedor_id, $comprador_nombre, $producto_nombre) {
    return enviarNotificacionAutomatica($vendedor_id, 'devolucion_solicitada', [
        'comprador' => $comprador_nombre,
        'producto' => $producto_nombre
    ]);
}

/**
 * Trigger para devolución respondida (se llama desde responder_devolucion.php)
 */
function notificarDevolucionRespondida($comprador_id, $producto_nombre, $aceptada) {
    $tipo = $aceptada ? 'devolucion_aceptada' : 'devolucion_rechazada';
    return enviarNotificacionAutomatica($comprador_id, $tipo, [
        'producto' => $producto_nombre
    ]);
}
?>
