<?php
/**
 * Historial de Transacciones con Sistema de Devoluciones
 * RF03-017 - Tu Mercado SENA
 */

require_once 'config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser();
$conn = getDBConnection();

// Obtener ventas finalizadas (como vendedor)
$stmt = $conn->prepare("
    SELECT 
        c.id as chat_id,
        c.precio as precio_acordado,
        c.cantidad,
        c.calificacion,
        c.comentario,
        c.fecha_venta,
        c.estado_id,
        p.id as producto_id,
        p.nombre as producto_nombre,
        p.precio as precio_original,
        u.id as comprador_id,
        u.nickname as comprador_nombre,
        u.imagen as comprador_imagen,
        f.imagen as producto_imagen,
        e.nombre as estado_nombre
    FROM chats c
    INNER JOIN productos p ON c.producto_id = p.id
    INNER JOIN usuarios u ON c.comprador_id = u.id
    LEFT JOIN fotos f ON f.producto_id = p.id
    LEFT JOIN estados e ON c.estado_id = e.id
    WHERE p.vendedor_id = ? AND c.fecha_venta IS NOT NULL
    GROUP BY c.id
    ORDER BY c.fecha_venta DESC
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$ventas = $stmt->get_result();
$stmt->close();

// Obtener compras finalizadas (como comprador)
$stmt = $conn->prepare("
    SELECT 
        c.id as chat_id,
        c.precio as precio_acordado,
        c.cantidad,
        c.calificacion,
        c.comentario,
        c.fecha_venta,
        c.estado_id,
        p.id as producto_id,
        p.nombre as producto_nombre,
        p.precio as precio_original,
        u.id as vendedor_id,
        u.nickname as vendedor_nombre,
        u.imagen as vendedor_imagen,
        f.imagen as producto_imagen,
        e.nombre as estado_nombre
    FROM chats c
    INNER JOIN productos p ON c.producto_id = p.id
    INNER JOIN usuarios u ON p.vendedor_id = u.id
    LEFT JOIN fotos f ON f.producto_id = p.id
    LEFT JOIN estados e ON c.estado_id = e.id
    WHERE c.comprador_id = ? AND c.fecha_venta IS NOT NULL
    GROUP BY c.id
    ORDER BY c.fecha_venta DESC
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$compras = $stmt->get_result();
$stmt->close();

// Estadísticas
$total_ventas = $ventas->num_rows;
$total_compras = $compras->num_rows;

// calificación promedio como vendedor
$stmt = $conn->prepare("
    SELECT AVG(c.calificacion) as promedio, COUNT(c.calificacion) as total
    FROM chats c
    INNER JOIN productos p ON c.producto_id = p.id
    WHERE p.vendedor_id = ? AND c.calificacion IS NOT NULL
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

$promedio = $stats['promedio'] ? round($stats['promedio'], 1) : 0;
$total_calificaciones = $stats['total'];

// Función para obtener el badge del estado
function getEstadoBadge($estado_id, $estado_nombre) {
    $classes = [
        5 => 'badge-success',    // vendido
        6 => 'badge-warning',    // esperando
        7 => 'badge-warning',    // devolviendo
        8 => 'badge-danger',     // devuelto
        9 => 'badge-dark'        // censurado
    ];
    $icons = [
        5 => '✓',
        6 => '⏳',
        7 => '🔄',
        8 => '↩️',
        9 => '🚫'
    ];
    $class = $classes[$estado_id] ?? 'badge-secondary';
    $icon = $icons[$estado_id] ?? '';
    return "<span class='status-badge $class'>$icon " . ucfirst($estado_nombre) . "</span>";
}

// Verificar si puede solicitar devolución (menos de 7 días)
function puedeDevolver($fecha_venta, $estado_id) {
    if ($estado_id != 5) return false; // Solo si está en estado "vendido"
    $dias = (time() - strtotime($fecha_venta)) / (60 * 60 * 24);
    return $dias <= 7;
}

function diasParaDevolucion($fecha_venta) {
    $dias = 7 - floor((time() - strtotime($fecha_venta)) / (60 * 60 * 24));
    return max(0, $dias);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Transacciones - Tu Mercado SENA</title>
    <link rel="stylesheet" href="styles.css?v=<?= time(); ?>">
    <style>
        .historial-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }
        .stat-card .number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--color-primary);
        }
        .stat-card .label {
            color: var(--color-text-light);
            font-size: 0.9rem;
        }
        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        .tab-btn {
            padding: 0.75rem 1.5rem;
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .tab-btn:hover {
            background: #f8f9fa;
        }
        .tab-btn.active {
            background: var(--color-primary);
            color: white;
            border-color: var(--color-primary);
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .transaccion-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid var(--border-color);
            display: flex;
            gap: 1rem;
            align-items: flex-start;
            box-shadow: var(--shadow-sm);
            position: relative;
        }
        .transaccion-card.devolviendo {
            border-left: 4px solid #ffc107;
        }
        .transaccion-card.devuelto {
            border-left: 4px solid #dc3545;
            opacity: 0.8;
        }
        .transaccion-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            flex-shrink: 0;
        }
        .transaccion-info {
            flex: 1;
        }
        .transaccion-info h3 {
            margin-bottom: 0.5rem;
            color: var(--color-primary);
        }
        .transaccion-info p {
            font-size: 0.9rem;
            color: var(--color-text-light);
            margin-bottom: 0.25rem;
        }
        .estrellas {
            color: #ffc107;
            font-size: 1.2rem;
        }
        .comentario-box {
            background: var(--color-bg-secondary);
            padding: 0.75rem;
            border-radius: 8px;
            margin-top: 0.5rem;
            font-style: italic;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 8px;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-dark { background: #d6d8db; color: #1b1e21; }
        .badge-secondary { background: #e9ecef; color: #495057; }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        .btn-devolucion {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }
        .btn-devolucion:hover {
            background: #c82333;
            transform: translateY(-1px);
        }
        .btn-aceptar {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
        }
        .btn-rechazar {
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
        }
        .dias-restantes {
            font-size: 11px;
            color: #6c757d;
            margin-top: 5px;
        }
        
        /* Modal de devolución */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 25px;
            max-width: 450px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .modal-header h3 {
            margin: 0;
            color: var(--color-primary);
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }
        .modal-body textarea {
            width: 100%;
            min-height: 100px;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            resize: vertical;
            font-family: inherit;
        }
        .modal-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        
        @media (max-width: 600px) {
            .transaccion-card {
                flex-direction: column;
            }
            .transaccion-img {
                width: 100%;
                height: 150px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/bottom_nav.php'; ?>

    <main class="main">
        <div class="container">
            <div class="page-header">
                <h1>📊 Historial de Transacciones</h1>
            </div>
            
            <div class="historial-stats">
                <div class="stat-card">
                    <div class="number"><?= $total_ventas ?></div>
                    <div class="label">Ventas realizadas</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?= $total_compras ?></div>
                    <div class="label">Compras realizadas</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?= $promedio ?> ⭐</div>
                    <div class="label"><?= $total_calificaciones ?> calificaciones</div>
                </div>
            </div>
            
            <div class="tabs">
                <button class="tab-btn active" onclick="showTab('ventas', this)">🏷️ Mis Ventas</button>
                <button class="tab-btn" onclick="showTab('compras', this)">🛒 Mis Compras</button>
            </div>
            
            <!-- TAB VENTAS -->
            <div id="ventas" class="tab-content active">
                <?php if ($total_ventas > 0): ?>
                    <?php $ventas->data_seek(0); while ($v = $ventas->fetch_assoc()): ?>
                        <div class="transaccion-card <?= $v['estado_id'] == 7 ? 'devolviendo' : ($v['estado_id'] == 8 ? 'devuelto' : '') ?>">
                            <img src="<?= $v['producto_imagen'] ? 'uploads/productos/'.$v['producto_imagen'] : 'assets/images/default-product.jpg' ?>" 
                                 class="transaccion-img" alt="Producto">
                            <div class="transaccion-info">
                                <h3><?= htmlspecialchars($v['producto_nombre']) ?></h3>
                                <p><strong>Comprador:</strong> <?= htmlspecialchars($v['comprador_nombre']) ?></p>
                                <p><strong>Precio acordado:</strong> <?= formatPrice($v['precio_acordado'] ?? $v['precio_original']) ?></p>
                                <p><strong>Cantidad:</strong> <?= $v['cantidad'] ?? 1 ?></p>
                                <p><strong>Fecha:</strong> <?= date('d/m/Y', strtotime($v['fecha_venta'])) ?></p>
                                
                                <?= getEstadoBadge($v['estado_id'], $v['estado_nombre']) ?>
                                
                                <?php if ($v['calificacion']): ?>
                                    <div class="estrellas">
                                        <?= str_repeat('★', $v['calificacion']) . str_repeat('☆', 5 - $v['calificacion']) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($v['comentario']): ?>
                                    <div class="comentario-box">"<?= htmlspecialchars($v['comentario']) ?>"</div>
                                <?php endif; ?>
                                
                                <?php if ($v['estado_id'] == 7): ?>
                                    <!-- Solicitud de devolución pendiente - mostrar botones al vendedor -->
                                    <div class="action-buttons">
                                        <button class="btn-aceptar" onclick="responderDevolucion(<?= $v['chat_id'] ?>, 'aceptar')">
                                            ✓ Aceptar devolución
                                        </button>
                                        <button class="btn-rechazar" onclick="abrirModalRechazar(<?= $v['chat_id'] ?>)">
                                            ✗ Rechazar
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-products"><p>Aún no has realizado ventas.</p></div>
                <?php endif; ?>
            </div>
            
            <!-- TAB COMPRAS -->
            <div id="compras" class="tab-content">
                <?php if ($total_compras > 0): ?>
                    <?php while ($c = $compras->fetch_assoc()): ?>
                        <div class="transaccion-card <?= $c['estado_id'] == 7 ? 'devolviendo' : ($c['estado_id'] == 8 ? 'devuelto' : '') ?>">
                            <img src="<?= $c['producto_imagen'] ? 'uploads/productos/'.$c['producto_imagen'] : 'assets/images/default-product.jpg' ?>" 
                                 class="transaccion-img" alt="Producto">
                            <div class="transaccion-info">
                                <h3><?= htmlspecialchars($c['producto_nombre']) ?></h3>
                                <p><strong>Vendedor:</strong> <?= htmlspecialchars($c['vendedor_nombre']) ?></p>
                                <p><strong>Precio:</strong> <?= formatPrice($c['precio_acordado'] ?? $c['precio_original']) ?></p>
                                <p><strong>Fecha:</strong> <?= date('d/m/Y', strtotime($c['fecha_venta'])) ?></p>
                                
                                <?= getEstadoBadge($c['estado_id'], $c['estado_nombre']) ?>
                                
                                <?php if ($c['calificacion']): ?>
                                    <div class="estrellas">Tu calificación: <?= str_repeat('★', $c['calificacion']) ?></div>
                                <?php elseif ($c['estado_id'] == 5): ?>
                                    <a href="calificar.php?chat_id=<?= $c['chat_id'] ?>" class="btn-small">Calificar</a>
                                <?php endif; ?>
                                
                                <?php if (puedeDevolver($c['fecha_venta'], $c['estado_id'])): ?>
                                    <div class="action-buttons">
                                        <button class="btn-devolucion" onclick="abrirModalDevolucion(<?= $c['chat_id'] ?>, '<?= htmlspecialchars($c['producto_nombre'], ENT_QUOTES) ?>')">
                                            🔄 Solicitar devolución
                                        </button>
                                    </div>
                                    <div class="dias-restantes">
                                        ⏰ <?= diasParaDevolucion($c['fecha_venta']) ?> días restantes para solicitar
                                    </div>
                                <?php elseif ($c['estado_id'] == 7): ?>
                                    <div class="dias-restantes">
                                        ⏳ Esperando respuesta del vendedor...
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-products"><p>Aún no has realizado compras.</p></div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container"><p>&copy; <?= date('Y') ?> Tu Mercado SENA.</p></div>
    </footer>
    
    <!-- Modal Solicitar Devolución -->
    <div class="modal-overlay" id="modalDevolucion">
        <div class="modal-content">
            <div class="modal-header">
                <h3>🔄 Solicitar Devolución</h3>
                <button class="modal-close" onclick="cerrarModal('modalDevolucion')">&times;</button>
            </div>
            <div class="modal-body">
                <p>Producto: <strong id="productoDevolucion"></strong></p>
                <p style="margin: 15px 0; color: #666;">Explica el motivo de tu solicitud:</p>
                <textarea id="motivoDevolucion" placeholder="Ej: El producto llegó dañado, no corresponde a la descripción..."></textarea>
            </div>
            <div class="modal-footer">
                <button class="btn-rechazar" onclick="cerrarModal('modalDevolucion')">Cancelar</button>
                <button class="btn-devolucion" onclick="enviarSolicitudDevolucion()">Enviar solicitud</button>
            </div>
        </div>
    </div>
    
    <!-- Modal Rechazar Devolución -->
    <div class="modal-overlay" id="modalRechazar">
        <div class="modal-content">
            <div class="modal-header">
                <h3>❌ Rechazar Devolución</h3>
                <button class="modal-close" onclick="cerrarModal('modalRechazar')">&times;</button>
            </div>
            <div class="modal-body">
                <p style="margin-bottom: 15px; color: #666;">Explica el motivo del rechazo (opcional):</p>
                <textarea id="motivoRechazo" placeholder="Ej: El producto fue entregado en perfectas condiciones..."></textarea>
            </div>
            <div class="modal-footer">
                <button class="btn-rechazar" onclick="cerrarModal('modalRechazar')">Cancelar</button>
                <button class="btn-devolucion" onclick="enviarRechazo()">Confirmar rechazo</button>
            </div>
        </div>
    </div>
    
    <script>
        let chatIdActual = null;
        
        function showTab(tab, btn) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById(tab).classList.add('active');
            btn.classList.add('active');
        }
        
        function abrirModalDevolucion(chatId, productoNombre) {
            chatIdActual = chatId;
            document.getElementById('productoDevolucion').textContent = productoNombre;
            document.getElementById('motivoDevolucion').value = '';
            document.getElementById('modalDevolucion').classList.add('active');
        }
        
        function abrirModalRechazar(chatId) {
            chatIdActual = chatId;
            document.getElementById('motivoRechazo').value = '';
            document.getElementById('modalRechazar').classList.add('active');
        }
        
        function cerrarModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            chatIdActual = null;
        }
        
        async function enviarSolicitudDevolucion() {
            const motivo = document.getElementById('motivoDevolucion').value.trim();
            if (!motivo) {
                alert('Por favor, indica el motivo de la devolución');
                return;
            }
            
            try {
                const response = await fetch('api/solicitar_devolucion.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `chat_id=${chatIdActual}&motivo=${encodeURIComponent(motivo)}`
                });
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message || 'Error al procesar la solicitud');
                }
            } catch (error) {
                alert('Error de conexión');
            }
            
            cerrarModal('modalDevolucion');
        }
        
        async function responderDevolucion(chatId, accion) {
            const confirmMsg = accion === 'aceptar' 
                ? '¿Confirmas que aceptas la devolución? El stock será restaurado.' 
                : '¿Confirmas que rechazas la devolución?';
            
            if (!confirm(confirmMsg)) return;
            
            try {
                const response = await fetch('api/responder_devolucion.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `chat_id=${chatId}&accion=${accion}`
                });
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message || 'Error al procesar');
                }
            } catch (error) {
                alert('Error de conexión');
            }
        }
        
        async function enviarRechazo() {
            const respuesta = document.getElementById('motivoRechazo').value.trim();
            
            try {
                const response = await fetch('api/responder_devolucion.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `chat_id=${chatIdActual}&accion=rechazar&respuesta=${encodeURIComponent(respuesta)}`
                });
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message || 'Error al procesar');
                }
            } catch (error) {
                alert('Error de conexión');
            }
            
            cerrarModal('modalRechazar');
        }
        
        // Cerrar modal al hacer clic fuera
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            });
        });
    </script>
    <script src="script.js"></script>
</body>
</html>
