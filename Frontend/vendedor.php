<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser();

// Obtener ID del vendedor
$vendedor_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($vendedor_id <= 0) {
    header('Location: index.php');
    exit;
}

$conn = getDBConnection();

// Verificar si el usuario actual tiene bloqueado a este vendedor o viceversa
$stmt = $conn->prepare("SELECT id FROM bloqueados WHERE (bloqueador_id = ? AND bloqueado_id = ?) OR (bloqueador_id = ? AND bloqueado_id = ?)");
$stmt->bind_param("iiii", $user['id'], $vendedor_id, $vendedor_id, $user['id']);

$stmt->execute();
$bloqueado = $stmt->get_result()->num_rows > 0;
$stmt->close();

if ($bloqueado) {
    $conn->close();
    header('Location: index.php');
    exit;
}

// Obtener informacion del vendedor
$stmt = $conn->prepare("SELECT u.*, 
    (SELECT COUNT(*) FROM productos p WHERE p.vendedor_id = u.id AND p.estado_id = 1) as total_productos,
    (SELECT AVG(c.calificacion) FROM chats c INNER JOIN productos p ON c.producto_id = p.id WHERE p.vendedor_id = u.id AND c.calificacion IS NOT NULL) as calificacion_promedio,
    (SELECT COUNT(*) FROM chats c INNER JOIN productos p ON c.producto_id = p.id WHERE p.vendedor_id = u.id AND c.fecha_venta IS NOT NULL) as total_ventas
    FROM usuarios u 
    WHERE u.id = ? AND u.estado_id = 1 AND u.visible = 1");
$stmt->bind_param("i", $vendedor_id);
$stmt->execute();
$vendedor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$vendedor) {
    $conn->close();
    header('Location: index.php');
    exit;
}

// Verificar si es favorito
$stmt = $conn->prepare("SELECT id FROM favoritos WHERE votante_id = ? AND votado_id = ?");
$stmt->bind_param("ii", $user['id'], $vendedor_id);
$stmt->execute();
$esFavorito = $stmt->get_result()->num_rows > 0;
$stmt->close();

// Obtener productos del vendedor
$stmt = $conn->prepare("SELECT p.*, 
    (SELECT f.imagen FROM fotos f WHERE f.producto_id = p.id ORDER BY f.id ASC LIMIT 1) as imagen,
    c.nombre as categoria_nombre, sc.nombre as subcategoria_nombre
    FROM productos p
    INNER JOIN subcategorias sc ON p.subcategoria_id = sc.id
    INNER JOIN categorias c ON sc.categoria_id = c.id
    WHERE p.vendedor_id = ? AND p.estado_id = 1
    ORDER BY p.fecha_registro DESC");
$stmt->bind_param("i", $vendedor_id);
$stmt->execute();
$productos = $stmt->get_result();
$stmt->close();

// Calcular si esta recientemente conectado (menos de 24 horas)
$ultimaConexion = strtotime($vendedor['fecha_reciente']);
$ahora = time();
$recienteConectado = ($ahora - $ultimaConexion) < 86400; // 24 horas

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($vendedor['nickname']) ?> - Tu Mercado SENA</title>
    <link rel="stylesheet" href="styles.css?v=<?= time(); ?>">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        .vendedor-header {
            background: var(--color-primary);
            padding: 2.5rem;
            border-radius: 20px;
            color: white;
            margin-bottom: 2rem;
            display: flex;
            gap: 2rem;
            align-items: center;
            flex-wrap: wrap;
            box-shadow: var(--shadow-md);
        }

        .vendedor-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid rgba(255,255,255,0.3);
        }
        .vendedor-info h1 {
            margin: 0 0 0.5rem 0;
            font-size: 1.8rem;
        }
        .vendedor-stats {
            display: flex;
            gap: 1.5rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }
        .stat-item {
            text-align: center;
        }
        .stat-item .number {
            font-size: 1.5rem;
            font-weight: 700;
        }
        .stat-item .label {
            font-size: 0.85rem;
            opacity: 0.9;
        }
        .vendedor-descripcion {
            margin-top: 1rem;
            opacity: 0.95;
            max-width: 500px;
        }
        .vendedor-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }
        .btn-social {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }
        .btn-social:hover {
            background: rgba(255,255,255,0.3);
        }
        .reciente-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            background: rgba(46, 204, 113, 0.3);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        .reciente-badge i {
            color: #2ecc71;
        }
        .productos-section h2 {
            margin-bottom: 1.5rem;
            color: var(--color-primary);
        }
        .calificacion-stars {
            color: #f39c12;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/bottom_nav.php'; ?>

    <main class="main">
        <div class="container">
            <div class="vendedor-header">
                <img src="<?= getAvatarUrl($vendedor['imagen']) ?>" alt="<?= htmlspecialchars($vendedor['nickname']) ?>" class="vendedor-avatar">
                
                <div class="vendedor-info">
                    <h1><?= htmlspecialchars($vendedor['nickname']) ?></h1>
                    
                    <?php if ($recienteConectado): ?>
                        <span class="reciente-badge">
                            <i class="ri-checkbox-blank-circle-fill"></i> Recientemente conectado
                        </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($vendedor['descripcion'])): ?>
                        <p class="vendedor-descripcion"><?= htmlspecialchars($vendedor['descripcion']) ?></p>
                    <?php endif; ?>
                    
                    <div class="vendedor-stats">
                        <div class="stat-item">
                            <div class="number"><?= $vendedor['total_productos'] ?></div>
                            <div class="label">Productos</div>
                        </div>
                        <div class="stat-item">
                            <div class="number"><?= $vendedor['total_ventas'] ?></div>
                            <div class="label">Ventas</div>
                        </div>
                        <div class="stat-item">
                            <div class="number calificacion-stars">
                                <?= $vendedor['calificacion_promedio'] ? number_format($vendedor['calificacion_promedio'], 1) . ' ★' : 'Sin calif.' ?>
                            </div>
                            <div class="label">Calificacion</div>
                        </div>
                    </div>
                    
                    <div class="vendedor-actions">
                        <?php if ($user['id'] != $vendedor_id): ?>
                            <button type="button" id="btnFavorito" 
                                data-vendedor-id="<?= $vendedor_id ?>"
                                class="btn-favorite <?= $esFavorito ? 'active' : '' ?>"
                                onclick="toggleFavorito(this)">
                                <i class="<?= $esFavorito ? 'ri-heart-3-fill' : 'ri-heart-3-line' ?>"></i>
                                <?= $esFavorito ? 'En Favoritos' : 'Agregar Favorito' ?>
                            </button>
                        <?php endif; ?>
                        
                        <?php if (!empty($vendedor['link'])): ?>
                            <a href="<?= htmlspecialchars($vendedor['link']) ?>" target="_blank" rel="noopener" class="btn-social">
                                <i class="ri-links-line"></i> Red Social
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="productos-section">
                <h2>Productos de <?= htmlspecialchars($vendedor['nickname']) ?></h2>
                
                <?php if ($productos->num_rows > 0): ?>
                    <div class="products-grid">
                        <?php while ($producto = $productos->fetch_assoc()): ?>
                            <div class="product-card">
                                <a href="producto.php?id=<?= $producto['id'] ?>">
                                    <?php 
                                    $imgSrc = $producto['imagen'] ? 'uploads/productos/' . $producto['imagen'] : 'https://picsum.photos/seed/' . $producto['id'] . '/400/300';
                                    if ($user['uso_datos'] == 1): ?>
                                        <div class="data-save-placeholder" onclick="this.innerHTML='<img src=\'<?= $imgSrc ?>\' class=\'product-image\'>'; event.preventDefault(); event.stopPropagation();">
                                            <i class="ri-image-line"></i>
                                            <span>Clic para cargar imagen</span>
                                        </div>
                                    <?php else: ?>
                                        <img src="<?= $imgSrc ?>" 
                                             alt="<?= htmlspecialchars($producto['nombre']) ?>"
                                             class="product-image"
                                             onerror="this.onerror=null; this.src='https://picsum.photos/seed/error/400/300?blur=5'">

                                    <?php endif; ?>

                                    <div class="product-info">
                                        <h3 class="product-name"><?= htmlspecialchars($producto['nombre']) ?></h3>
                                        <p class="product-price"><?= formatPrice($producto['precio']) ?></p>
                                        <p class="product-category"><?= htmlspecialchars($producto['categoria_nombre']) ?></p>
                                    </div>
                                </a>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="no-products">
                        <p>Este vendedor no tiene productos disponibles.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Tu Mercado SENA.</p>
        </div>
    </footer>
    
    <script src="script.js?v=<?= time(); ?>"></script>
</body>
</html>
