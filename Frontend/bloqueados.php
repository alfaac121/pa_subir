<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser();
$conn = getDBConnection();

// Desbloquear usuario
if (isset($_GET['desbloquear'])) {
    $bloqueado_id = (int)$_GET['desbloquear'];
    $stmt = $conn->prepare("DELETE FROM bloqueados WHERE bloqueador_id = ? AND bloqueado_id = ?");
    $stmt->bind_param("ii", $user['id'], $bloqueado_id);
    $stmt->execute();
    $stmt->close();
    header("Location: bloqueados.php?msg=desbloqueado");
    exit;
}

// Obtener lista de bloqueados
$stmt = $conn->prepare("
    SELECT b.id, b.bloqueador_id, b.bloqueado_id, u.nickname, u.imagen, u.descripcion
    FROM bloqueados b
    INNER JOIN usuarios u ON b.bloqueado_id = u.id
    WHERE b.bloqueador_id = ?
    ORDER BY b.id DESC
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$bloqueados = $stmt->get_result();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios Bloqueados - Tu Mercado SENA</title>
    <link rel="stylesheet" href="styles.css?v=<?= time(); ?>">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/bottom_nav.php'; ?>

    <main class="main">
        <div class="container">
            <div class="page-header">
                <h1>🚫 Usuarios Bloqueados</h1>
            </div>
            
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'desbloqueado'): ?>
                <div class="success-message">Usuario desbloqueado correctamente</div>
            <?php endif; ?>
            
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'bloqueado'): ?>
                <div class="success-message">Usuario bloqueado correctamente</div>
            <?php endif; ?>
            
            <div class="products-grid">
                <?php if ($bloqueados->num_rows > 0): ?>
                    <?php while ($b = $bloqueados->fetch_assoc()): ?>
                        <div class="product-card seller-card">
                            <img src="<?= getAvatarUrl($b['imagen']); ?>" 
                                 alt="Avatar de <?= htmlspecialchars($b['nickname']); ?>"
                                 class="product-image">
                            <div class="product-info">
                                <h3 class="product-name"><?= htmlspecialchars($b['nickname']); ?></h3>
                                <?php if (!empty($b['descripcion'])): ?>
                                    <p class="product-category"><?= htmlspecialchars(substr($b['descripcion'], 0, 50)); ?>...</p>
                                <?php endif; ?>
                            </div>
                            <div class="product-actions">
                                <a href="bloqueados.php?desbloquear=<?= $b['bloqueado_id']; ?>" 
                                   class="btn-primary"
                                   onclick="return confirm('¿Desbloquear a este usuario?');">
                                   Desbloquear
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-products">
                        <p>No has bloqueado a ningún usuario.</p>
                        <a href="index.php" class="btn-primary">Explorar productos</a>
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
