<?php
// Barra de Navegación Inferior (solo móviles)
// Incluir este archivo después del header en las páginas principales

// Detectar la página actual para marcar como activa
$currentPage = basename($_SERVER['PHP_SELF']);

// Usar la función getBaseUrl() del config.php
$base_url = getBaseUrl();
?>
<!-- Barra de Navegación Inferior (solo móviles) -->
<nav class="bottom-nav" id="bottomNav">
    <a href="<?= $base_url ?>index.php" class="bottom-nav-item <?= ($currentPage == 'index.php') ? 'active' : '' ?>">
        <i class="bottom-nav-icon ri-home-5-fill"></i>
        <span class="bottom-nav-label">Inicio</span>
    </a>
    <a href="<?= $base_url ?>productos/mis_productos.php" class="bottom-nav-item <?= ($currentPage == 'mis_productos.php') ? 'active' : '' ?>">
        <i class="bottom-nav-icon ri-box-3-fill"></i>
        <span class="bottom-nav-label">Productos</span>
    </a>
    <a href="<?= $base_url ?>productos/publicar.php" class="bottom-nav-item bottom-nav-publish <?= ($currentPage == 'publicar.php') ? 'active' : '' ?>">
        <i class="bottom-nav-icon ri-add-line"></i>
        <span class="bottom-nav-label">Publicar</span>
    </a>
    <a href="<?= $base_url ?>chat/mis_chats.php" class="bottom-nav-item <?= ($currentPage == 'mis_chats.php' || $currentPage == 'chat.php') ? 'active' : '' ?>">
        <i class="bottom-nav-icon ri-chat-3-fill"></i>
        <span class="bottom-nav-label">Chats</span>
    </a>
    <a href="<?= $base_url ?>perfil/favoritos.php" class="bottom-nav-item <?= ($currentPage == 'favoritos.php') ? 'active' : '' ?>">
        <i class="bottom-nav-icon ri-heart-3-fill"></i>
        <span class="bottom-nav-label">Favoritos</span>
    </a>
    <a href="<?= $base_url ?>perfil/perfil.php" class="bottom-nav-item <?= ($currentPage == 'perfil.php') ? 'active' : '' ?>">
        <?php if (isset($user) && !empty($user['imagen'])): ?>
            <img src="<?= getAvatarUrl($user['imagen']); ?>" class="bottom-nav-avatar" alt="Perfil">
        <?php else: ?>
            <i class="bottom-nav-icon ri-user-3-fill"></i>
        <?php endif; ?>
        <span class="bottom-nav-label">Perfil</span>
    </a>
</nav>
