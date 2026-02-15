<?php
// FORZAR NO CACHÉ
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once 'config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Usuario autenticado

$user = getCurrentUser();
$conn = getDBConnection();

// Obtener productos del usuario
$stmt = $conn->prepare("
    SELECT p.*, 
           sc.nombre AS subcategoria_nombre, 
           c.nombre AS categoria_nombre,
           e.nombre AS estado_nombre,
           f.imagen AS producto_imagen
    FROM productos p
    INNER JOIN subcategorias sc ON p.subcategoria_id = sc.id
    INNER JOIN categorias c ON sc.categoria_id = c.id
    INNER JOIN estados e ON p.estado_id = e.id
    LEFT JOIN (
        SELECT producto_id, MIN(id) AS min_id
        FROM fotos
        GROUP BY producto_id
    ) fmin ON fmin.producto_id = p.id
    LEFT JOIN fotos f ON f.id = fmin.min_id
    WHERE p.vendedor_id = ?
    ORDER BY p.fecha_registro DESC
");

$stmt->bind_param("i", $user['id']);
$stmt->execute();
$productos_result = $stmt->get_result();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Mis Productos - Tu Mercado SENA</title>
    <link rel="stylesheet" href="styles.css?v=<?= time() . rand(1000, 9999); ?>">

    <style>
        .btn-delete {
            background: var(--color-danger);
            color: #fff;
            padding: 8px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            transition: .2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-delete:hover {
            filter: brightness(0.9);
            transform: translateY(-1px);
        }
        .product-actions {
            display: flex;
            gap: 8px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
    </style>

</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <?php include 'includes/bottom_nav.php'; ?>

    <main class="main">
        <div class="container">

            <!-- MENSAJE CUANDO SE PUBLICA UN PRODUCTO -->
            <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'producto_publicado'): ?>
                <div class="alert-success" style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    ✅ Producto publicado exitosamente
                </div>
            <?php endif; ?>

            <!-- MENSAJE CUANDO SE ELIMINA UN PRODUCTO -->
            <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'producto_eliminado'): ?>
                <div class="alert-success" style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    ✅ Producto eliminado correctamente.
                </div>
            <?php endif; ?>

            <div class="page-header">
                <h1>Mis Productos</h1>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <a href="publicar.php" class="btn-primary">Publicar Nuevo Producto</a>
                </div>
            </div>
            
            <div class="products-grid">
                <?php if ($productos_result->num_rows > 0): ?>
                    <?php while ($producto = $productos_result->fetch_assoc()): ?>
                        <div class="product-card">
                            <a href="producto.php?id=<?php echo $producto['id']; ?>">

<?php if (!empty($producto['producto_imagen'])): ?>
    <img src="uploads/productos/<?php echo htmlspecialchars($producto['producto_imagen']); ?>"
         alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
         class="product-image">
<?php else: ?>
    <img src="https://picsum.photos/seed/<?php echo $producto['id']; ?>/400/300"
         alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
         class="product-image">
<?php endif; ?>



                                <div class="product-info">
                                    <h3 class="product-name"><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                                    <p class="product-price"><?php echo formatPrice($producto['precio']); ?></p>
                                    <p class="product-category"><?php echo htmlspecialchars($producto['categoria_nombre']); ?> - 
                                       <?php echo htmlspecialchars($producto['subcategoria_nombre']); ?></p>
                                    <span class="product-status status-<?php echo $producto['estado_id']; ?>">
                                        <?php echo htmlspecialchars($producto['estado_nombre']); ?>
                                    </span>
                                    <span class="product-stock">Disponibles: <?php echo $producto['disponibles']; ?></span>
                                </div>
                            </a>

                            <div class="product-actions" style="display: flex; gap: 4px; flex-wrap: wrap;">
                                <a href="editar_producto.php?id=<?php echo $producto['id']; ?>" class="btn-small">Editar</a>
                                
                                <button type="button" 
                                        onclick="toggleVisibilidad(<?php echo $producto['id']; ?>, this)" 
                                        class="btn-small" 
                                        style="background: var(--color-secondary); color: white;">
                                    <?php echo $producto['estado_id'] == 1 ? 'Ocultar' : 'Mostrar'; ?>
                                </button>

                                <a href="eliminar_producto.php?id=<?php echo $producto['id']; ?>"
                                   class="btn-delete"
                                   onclick="return confirm('¿Estás seguro de que deseas eliminar este producto?\n\nSe eliminará:\n- El producto\n- Todos los chats\n- Todas las fotos\n- Todos los mensajes\n\nEsta acción NO se puede deshacer.');">
                                   Eliminar
                                </a>
                            </div>


                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-products">
                        <p>No has publicado ningún producto todavía.</p>

                        <a href="publicar.php" class="btn-primary">Publicar tu primer producto</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Tu Mercado SENA. Todos los derechos reservados.</p>
        </div>
    </footer>
    <script>
    function toggleVisibilidad(id, btn) {
        fetch(`api/toggle_visibilidad.php?id=${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const card = btn.closest('.product-card');
                    const statusSpan = card.querySelector('.product-status');
                    
                    if (data.nuevo_estado === 2) {
                        btn.textContent = 'Mostrar';
                        statusSpan.textContent = 'invisible';
                        statusSpan.className = 'product-status status-2';
                    } else {
                        btn.textContent = 'Ocultar';
                        statusSpan.textContent = 'activo';
                        statusSpan.className = 'product-status status-1';
                    }
                    alert('Visibilidad actualizada');
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(err => alert('Error de conexion'));
    }
    </script>
</body>
</html>



