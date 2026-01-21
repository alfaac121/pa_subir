<?php
// Barra de Navegación Inferior (solo móviles)
// Incluir este archivo después del header en las páginas principales

// Detectar la página actual para marcar como activa
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!-- Barra de Navegación Inferior (solo móviles) -->
<nav class="bottom-nav" id="bottomNav">
    <a href="index.php" class="bottom-nav-item <?= ($currentPage == 'index.php') ? 'active' : '' ?>">
        <span class="bottom-nav-icon">🏠</span>
        <span class="bottom-nav-label">Inicio</span>
    </a>
    <a href="mis_productos.php" class="bottom-nav-item <?= ($currentPage == 'mis_productos.php') ? 'active' : '' ?>">
        <span class="bottom-nav-icon">📦</span>
        <span class="bottom-nav-label">Productos</span>
    </a>
    <a href="publicar.php" class="bottom-nav-item bottom-nav-publish <?= ($currentPage == 'publicar.php') ? 'active' : '' ?>">
        <span class="bottom-nav-icon">➕</span>
        <span class="bottom-nav-label">Publicar</span>
    </a>
    <a href="favoritos.php" class="bottom-nav-item <?= ($currentPage == 'favoritos.php') ? 'active' : '' ?>">
        <span class="bottom-nav-icon">❤️</span>
        <span class="bottom-nav-label">Favoritos</span>
    </a>
    <a href="perfil.php" class="bottom-nav-item <?= ($currentPage == 'perfil.php') ? 'active' : '' ?>">
        <?php if (isset($user) && !empty($user['imagen'])): ?>
            <img src="<?= getAvatarUrl($user['imagen']); ?>" class="bottom-nav-avatar" alt="Perfil">
        <?php else: ?>
            <span class="bottom-nav-icon">👤</span>
        <?php endif; ?>
        <span class="bottom-nav-label">Perfil</span>
    </a>
</nav>
