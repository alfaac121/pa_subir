<?php
require_once 'config.php';
require_once 'api/api_client.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// Obtener información del usuario desde la API
$response = apiGetPerfilPublico($id);
if (!$response['success']) {
    header('Location: index.php');
    exit;
}

$usuario = $response['data']['data'];
$user = isLoggedIn() ? getCurrentUser() : null;

// Obtener productos del vendedor (Usando DB por ahora)
$conn = getDBConnection();
$stmt = $conn->prepare("
    SELECT p.*, sc.nombre as subcategoria_nombre, c.nombre as categoria_nombre
    FROM productos p
    INNER JOIN subcategorias sc ON p.subcategoria_id = sc.id
    INNER JOIN categorias c ON sc.categoria_id = c.id
    WHERE p.vendedor_id = ? AND p.estado_id = 1
    ORDER BY p.fecha_registro DESC
");
$stmt->bind_param("i", $id);
$stmt->execute();
$productos_vendedor = $stmt->get_result();
$stmt->close();

$isFavorite = false;
if ($user) {
    $isFavorite = isSellerFavorite($user['id'], $id);
}

$avatar = getAvatarUrl($usuario['imagen']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de <?php echo htmlspecialchars($usuario['nickname']); ?> - Tu Mercado SENA</title>
    <link rel="stylesheet" href="styles.css?v=<?= time(); ?>">
    <style>


        .profile-content {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
        }

        .profile-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            margin-bottom: 40px;
        }

        .profile-avatar-large {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            margin-bottom: 20px;
            background: #eee;
        }

        .profile-nickname {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: #333;
        }

        .profile-description {
            color: #666;
            font-size: 1.1rem;
            max-width: 600px;
            margin: 10px auto 20px;
            line-height: 1.6;
        }

        .profile-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .profile-link-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary-color, #007bff);
            text-decoration: none;
            font-weight: 600;
            margin-top: 10px;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 3px solid var(--primary-color, #007bff);
            display: inline-block;
        }

        .seller-products {
            margin-top: 40px;
        }

        .btn-fav-large {
            padding: 12px 25px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 2px solid var(--primary-color, #007bff);
            background: transparent;
            color: var(--primary-color, #007bff);
        }

        .btn-fav-large.active {
            background: var(--primary-color, #007bff);
            color: white;
        }

        .btn-fav-large:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        @media (max-width: 768px) {
            .profile-header {
                padding: 40px 0;
            }
            .profile-card {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <h1 class="logo">
                    <a href="index.php">
                        <img src="logo_new.png" class="logo-img" alt="Tu Mercado SENA">
                        Tu Mercado SENA
                    </a>
                </h1>
                <nav class="nav nav-desktop">
                    <a href="index.php">Inicio</a>
                    <a href="favoritos.php">Favoritos</a>
                    <?php if ($user): ?>
                    <a href="perfil.php" class="perfil-link">
                        <div class="user-avatar-container">
                             <img src="<?php echo getAvatarUrl($user['imagen']); ?>"
                            alt="Avatar de <?php echo htmlspecialchars($user['nickname']); ?>"
                            class="avatar-header">
                            <span class="user-name-footer"><?php echo htmlspecialchars($user['nickname']); ?></span>
                        </div>
                    </a>
                    <?php else: ?>
                    <a href="login.php" class="btn-primary">Iniciar Sesión</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>
    
    <?php if ($user): ?>
<?php endif; ?>

    <main class="main">
        <div class="profile-content">
            <div class="profile-card">
                <img src="<?php echo htmlspecialchars($avatar); ?>"
                     alt="Avatar de <?php echo htmlspecialchars($usuario['nickname']); ?>"
                     class="profile-avatar-large">

                <h2 class="profile-nickname"><?php echo htmlspecialchars($usuario['nickname']); ?></h2>
                
                <p class="profile-description">
                    <?php echo $usuario['descripcion'] ? nl2br(htmlspecialchars($usuario['descripcion'])) : 'Este vendedor no ha agregado una descripción todavía.'; ?>
                </p>

                <?php if (!empty($usuario['link'])): ?>
                    <a href="<?php echo htmlspecialchars($usuario['link']); ?>" target="_blank" class="profile-link-btn">
                        🔗 Enlace externo / Red social
                    </a>
                <?php endif; ?>

                <div class="profile-actions">
                    <?php if ($user && $user['id'] != $id): ?>
                        <a href="favoritos.php?vendedor_id=<?php echo $id; ?>" 
                           class="btn-fav-large <?php echo $isFavorite ? 'active' : ''; ?>">
                            <?php echo $isFavorite ? '❤️ En mis Favoritos' : '🤍 Añadir a Favoritos'; ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="seller-products">
                <h3 class="section-title">Productos en venta</h3>
                
                <div class="products-grid">
                    <?php if ($productos_vendedor->num_rows > 0): ?>
                        <?php while ($p = $productos_vendedor->fetch_assoc()): ?>
                            <?php 
                            $p_img = getProductMainImage($p['id']);
                            ?>
                            <div class="product-card">
                                <a href="producto.php?id=<?php echo $p['id']; ?>">
                                    <img src="<?php echo htmlspecialchars($p_img); ?>" alt="<?php echo htmlspecialchars($p['nombre']); ?>" class="product-image">
                                </a>
                                <div class="product-info">
                                    <h3 class="product-name">
                                        <a href="producto.php?id=<?php echo $p['id']; ?>">
                                            <?php echo htmlspecialchars($p['nombre']); ?>
                                        </a>
                                    </h3>
                                    <p class="product-price"><?php echo formatPrice($p['precio']); ?></p>
                                    <p class="product-category"><?php echo htmlspecialchars($p['categoria_nombre']); ?></p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-products">
                            <p>Este vendedor no tiene productos publicados actualmente.</p>
                        </div>
                    <?php endif; ?>
                </div>
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

