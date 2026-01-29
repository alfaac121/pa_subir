<?php
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
    <title>Mis Productos - Tu Mercado SENA</title>
    <link rel="stylesheet" href="styles.css?v=<?= time(); ?>">

    <style>
        .alert-success {
            background: #d4ffd8;
            color: #155724;
            border: 1px solid #6dd17c;
            padding: 12px;
            margin: 15px 0;
            border-radius: 6px;
            text-align: center;
            font-size: 16px;
        }
        .btn-delete {
            background: #d9534f;
            color: #fff;
            padding: 6px 10px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            transition: .2s;
        }
        .btn-delete:hover {
            background: #c9302c;
        }
        .product-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
    </style>

</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <?php include 'includes/bottom_nav.php'; ?>

    <main class="main">
        <div class="container">

            <!-- MENSAJE CUANDO SE ELIMINA UN PRODUCTO -->
            <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'producto_eliminado'): ?>
                <div class="alert-success">
                    ✅ Producto eliminado correctamente.
                </div>
            <?php endif; ?>

            <div class="page-header">
                <h1>Mis Productos</h1>
                <a href="publicar.php" class="btn-primary">Publicar Nuevo Producto</a>
            </div>
            
            <div class="products-grid">
                <?php if ($productos_result->num_rows > 0): ?>
                    <?php while ($producto = $productos_result->fetch_assoc()): ?>
                        <div class="product-card">
                            <a href="producto.php?id=<?php echo $producto['id']; ?>">

<?php if (!empty($producto['producto_imagen'])): ?>
    <img src="uploads/<?php echo htmlspecialchars($producto['producto_imagen']); ?>"
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

                            <div class="product-actions">
                                <a href="editar_producto.php?id=<?php echo $producto['id']; ?>" class="btn-small">Editar</a>

                                <!-- BOTÓN ELIMINAR CON CONFIRMACIÓN -->
                                <a href="eliminar_producto.php?id=<?php echo $producto['id']; ?>"
                                   class="btn-delete"
                                   onclick="return confirm('¿Estás seguro de que deseas eliminar este producto? Esta acción no se puede deshacer.');">
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
    <script src="script.js"></script>
</body>
</html>


