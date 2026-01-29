<?php
require_once 'config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// Obtener información del usuario desde la base de datos
$conn = getDBConnection();
$stmt = $conn->prepare("
    SELECT u.id, u.nickname, u.descripcion, u.link, u.imagen
    FROM usuarios u
    WHERE u.id = ? AND u.estado_id = 1
    LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();
$stmt->close();

if (!$usuario) {
    $conn->close();
    header('Location: index.php');
    exit;
}

$user = isLoggedIn() ? getCurrentUser() : null;

// Obtener productos del vendedor
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

$conn->close();
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

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--color-primary);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: var(--color-bg);
            border-radius: 30px;
            box-shadow: var(--shadow);
            transition: all 0.2s;
        }

        .back-button:hover {
            transform: translateX(-5px);
            box-shadow: var(--shadow-hover);
        }

        @media (max-width: 768px) {
            .profile-header {
                padding: 40px 0;
            }
            .profile-card {
                padding: 30px 20px;
                margin-bottom: 100px;
            }
            .profile-content {
                padding-bottom: 80px;
            }
        }
    </style>
</head>
<body>
    <?php
    if ($user) {
        include 'includes/header.php';
    } else {
        // Header simplificado para invitados
        ?>
        <header class="header">
            <div class="container">
                <div class="header-content">
                    <h1 class="logo">
                        <a href="index.php">
                            <img src="logo_new.png" class="logo-img" alt="Logo">
                            <span class="logo-text">Tu Mercado SENA</span>
                        </a>
                    </h1>
                    <div class="header-right">
                        <a href="login.php" class="btn-primary">Iniciar Sesión</a>
                    </div>
                </div>
            </div>
        </header>
        <?php
    }
    ?>

    <main class="main">
        <div class="profile-content">
            <a href="javascript:history.back()" class="back-button">
                ← Volver
            </a>
            <div class="profile-card">
                <img src="<?php echo htmlspecialchars($avatar); ?>"
                     alt="Avatar de <?php echo htmlspecialchars($usuario['nickname']); ?>"
                     class="profile-avatar-large">

                <h2 class="profile-nickname"><?php echo htmlspecialchars($usuario['nickname']); ?></h2>
                
                <p class="profile-description">
                    <?php echo $usuario['descripcion'] ? nl2br(htmlspecialchars($usuario['descripcion'])) : 'Este vendedor no ha agregado una descripción todavía.'; ?>
                </p>

                <?php if ($user && $user['id'] != $usuario['id']): ?>
                    <button type="button" 
                            class="btn-fav-large <?php echo $isFavorite ? 'active' : ''; ?>"
                            data-vendedor-id="<?php echo $usuario['id']; ?>"
                            onclick="toggleFavorito(this)">
                        <i class="fav-icon <?php echo $isFavorite ? 'ri-heart-3-fill' : 'ri-heart-3-line'; ?>"></i>
                        <span class="fav-text"><?php echo $isFavorite ? 'En mis Favoritos' : 'Añadir a Favoritos'; ?></span>
                    </button>
                <?php endif; ?>

                <?php if (!empty($usuario['link'])): ?>
                    <a href="<?php echo htmlspecialchars($usuario['link']); ?>" target="_blank" class="profile-link-btn">
                        <i class="ri-links-line"></i> Enlace externo / Red social
                    </a>
                <?php endif; ?>
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
