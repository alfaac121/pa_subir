<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser();
$conn = getDBConnection();

/* ---------------------------
   🔄 AGREGAR / QUITAR FAVORITO
--------------------------- */
if (isset($_GET['vendedor_id'])) {
    $vendedor_id = (int)$_GET['vendedor_id'];
    $usuario_id = $user['id'];

    // Revisar si ya existe
    $stmt = $conn->prepare("SELECT id FROM favoritos WHERE votante_id = ? AND votado_id = ?");
    $stmt->bind_param("ii", $usuario_id, $vendedor_id);
    $stmt->execute();
    $existe = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existe) {
        // Si ya existe → eliminar
        $stmt = $conn->prepare("DELETE FROM favoritos WHERE votante_id = ? AND votado_id = ?");
    } else {
        // Si no existe → agregar
        $stmt = $conn->prepare("INSERT INTO favoritos (votante_id, votado_id) VALUES (?, ?)");
    }

    $stmt->bind_param("ii", $usuario_id, $vendedor_id);
    $stmt->execute();
    $stmt->close();

    header("Location: favoritos.php");
    exit;
}

/* ---------------------------
   📌 OBTENER VENDEDORES FAVORITOS
--------------------------- */
$query = "
    SELECT u.id, u.nickname, u.descripcion, u.link, u.imagen
    FROM favoritos f
    INNER JOIN usuarios u ON f.votado_id = u.id
    WHERE f.votante_id = ?
";

$stmt = $conn->prepare($query);

if (!$stmt) {
    die("❌ Error en prepare(): " . $conn->error . "<br><pre>$query</pre>");
}

$stmt->bind_param("i", $user['id']);
$stmt->execute();
$vendedores_favoritos = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Favoritos - Tu Mercado SENA</title>
    <link rel="stylesheet" href="styles.css?v=<?= time(); ?>">
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
                    <a href="index.php">Menú Principal</a>
                    <a href="publicar.php">Publicar Producto</a>
                    <a href="perfil.php" class="perfil-link">
                        <div class="user-avatar-container">
                             <img src="<?php echo getAvatarUrl($user['imagen']); ?>"
                    alt="Avatar de <?php echo htmlspecialchars($user['nickname']); ?>"
                    class="avatar-header">
                                <span class="user-name-footer"><?php echo htmlspecialchars($user['nickname'] ?? 'Invitado'); ?>
</span>
                        </div>
                    </a>
                </nav>
            </div>
        </div>
    </header>
    
    <?php include 'includes/bottom_nav.php'; ?>

    <main class="main">
        <div class="container">
            <div class="page-header">
                <h1>Mis Vendedores Favoritos</h1>
            </div>
            
           <div class="products-grid">
<?php if ($vendedores_favoritos->num_rows > 0): ?>
    <?php while ($v = $vendedores_favoritos->fetch_assoc()): ?>
        
        <div class="product-card seller-card">

            <!-- Avatar del vendedor -->
            <img src="<?php echo getAvatarUrl($v['imagen']); ?>"
                alt="Avatar de <?php echo htmlspecialchars($v['nickname']); ?>"
                class="product-image">

            <div class="product-info">
                <h3 class="product-name">
                    <?php echo htmlspecialchars($v['nickname']); ?>
                </h3>

                <p class="product-category">
                    <?php echo nl2br(htmlspecialchars($v['descripcion'] ?? '')); ?>
                </p>

                <?php if (!empty($v['link'])): ?>
                <p>
                    <a href="<?php echo htmlspecialchars($v['link']); ?>" target="_blank">
                        🔗 Enlace del vendedor
                    </a>
                </p>
                <?php endif; ?>
            </div>

            <div class="product-actions">
                <a href="perfil_publico.php?id=<?php echo $v['id']; ?>" class="btn-primary">
                    Ver Perfil
                </a>

                <a href="favoritos.php?vendedor_id=<?php echo $v['id']; ?>"
                   class="btn-small"
                   onclick="return confirm('¿Quieres quitar a este vendedor de tus favoritos?');">
                    Quitar de Favoritos
                </a>
            </div>

        </div>

    <?php endwhile; ?>

<?php else: ?>
    <div class="no-products">
        <p>No has agregado vendedores a tus favoritos todavía.</p>
        <a href="index.php" class="btn-primary">Explorar</a>
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
    const savedTheme = localStorage.getItem("theme") || "light";
    document.documentElement.setAttribute("data-theme", savedTheme);
</script>
</body>
</html>


