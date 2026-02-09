<?php
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
        p.id as producto_id,
        p.nombre as producto_nombre,
        p.precio as precio_original,
        u.nickname as comprador_nombre,
        u.imagen as comprador_imagen,
        f.imagen as producto_imagen
    FROM chats c
    INNER JOIN productos p ON c.producto_id = p.id
    INNER JOIN usuarios u ON c.comprador_id = u.id
    LEFT JOIN fotos f ON f.producto_id = p.id
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
        p.id as producto_id,
        p.nombre as producto_nombre,
        p.precio as precio_original,
        u.nickname as vendedor_nombre,
        u.imagen as vendedor_imagen,
        f.imagen as producto_imagen
    FROM chats c
    INNER JOIN productos p ON c.producto_id = p.id
    INNER JOIN usuarios u ON p.vendedor_id = u.id
    LEFT JOIN fotos f ON f.producto_id = p.id
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
            background: white; /* Cambiado de var(--color-bg) */
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
        }
        .tab-btn {
            padding: 0.75rem 1.5rem;
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
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
            background: white; /* Cambiado de var(--color-bg) */
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid var(--border-color);
            display: flex;
            gap: 1rem;
            align-items: flex-start;
            box-shadow: var(--shadow-sm);
        }
        .transaccion-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            flex-shrink: 0;
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
                <button class="tab-btn active" onclick="showTab('ventas')">Mis Ventas</button>
                <button class="tab-btn" onclick="showTab('compras')">Mis Compras</button>
            </div>
            
            <div id="ventas" class="tab-content active">
                <?php if ($total_ventas > 0): ?>
                    <?php $ventas->data_seek(0); while ($v = $ventas->fetch_assoc()): ?>
                        <div class="transaccion-card">
                            <img src="<?= $v['producto_imagen'] ? 'uploads/productos/'.$v['producto_imagen'] : 'https://picsum.photos/80' ?>" 
                                 class="transaccion-img" alt="Producto">
                            <div class="transaccion-info">
                                <h3><?= htmlspecialchars($v['producto_nombre']) ?></h3>
                                <p><strong>Comprador:</strong> <?= htmlspecialchars($v['comprador_nombre']) ?></p>
                                <p><strong>Precio acordado:</strong> <?= formatPrice($v['precio_acordado'] ?? $v['precio_original']) ?></p>
                                <p><strong>Cantidad:</strong> <?= $v['cantidad'] ?? 1 ?></p>
                                <p><strong>Fecha:</strong> <?= date('d/m/Y', strtotime($v['fecha_venta'])) ?></p>
                                <?php if ($v['calificacion']): ?>
                                    <div class="estrellas">
                                        <?= str_repeat('★', $v['calificacion']) . str_repeat('☆', 5 - $v['calificacion']) ?>

                                    </div>
                                <?php endif; ?>
                                <?php if ($v['comentario']): ?>
                                    <div class="comentario-box">"<?= htmlspecialchars($v['comentario']) ?>"</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-products"><p>Aún no has realizado ventas.</p></div>

                <?php endif; ?>
            </div>
            
            <div id="compras" class="tab-content">
                <?php if ($total_compras > 0): ?>
                    <?php while ($c = $compras->fetch_assoc()): ?>
                        <div class="transaccion-card">
                            <img src="<?= $c['producto_imagen'] ? 'uploads/productos/'.$c['producto_imagen'] : 'https://picsum.photos/80' ?>" 
                                 class="transaccion-img" alt="Producto">
                            <div class="transaccion-info">
                                <h3><?= htmlspecialchars($c['producto_nombre']) ?></h3>
                                <p><strong>Vendedor:</strong> <?= htmlspecialchars($c['vendedor_nombre']) ?></p>
                                <p><strong>Precio:</strong> <?= formatPrice($c['precio_acordado'] ?? $c['precio_original']) ?></p>
                                <p><strong>Fecha:</strong> <?= date('d/m/Y', strtotime($c['fecha_venta'])) ?></p>
                                <?php if ($c['calificacion']): ?>
                                    <div class="estrellas">Tu calificación: <?= str_repeat('★', $c['calificacion']) ?></div>

                                <?php else: ?>
                                    <a href="calificar.php?chat_id=<?= $c['chat_id'] ?>" class="btn-small">Calificar</a>
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
        <div class="container"><p>&copy; 2025 Tu Mercado SENA.</p></div>
    </footer>
    
    <script>
        function showTab(tab) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById(tab).classList.add('active');
            event.target.classList.add('active');
        }
    </script>
    <script src="script.js"></script>
</body>
</html>
