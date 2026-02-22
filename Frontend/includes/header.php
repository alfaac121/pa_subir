<?php
// Header común para todas las páginas del marketplace
$current_page = basename($_SERVER['PHP_SELF']);
if (!isset($user)) {
    $user = getCurrentUser();
}
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
                <nav class="nav nav-desktop">
                    <a href="index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>">Inicio</a>
                    <a href="mis_productos.php" class="<?= $current_page == 'mis_productos.php' ? 'active' : '' ?>">Productos</a>
                    <a href="favoritos.php" class="<?= $current_page == 'favoritos.php' ? 'active' : '' ?>">Favoritos</a>
                    <a href="publicar.php" class="<?= $current_page == 'publicar.php' ? 'active' : '' ?>">Publicar</a>
                    
                    <a href="mis_chats.php" class="notification-badge <?= $current_page == 'mis_chats.php' ? 'active' : '' ?>" title="Mis conversaciones">
                        <i class="ri-chat-3-line notification-icon" id="notificationIcon"></i>
                        <span class="notification-count hidden" id="notificationCount">0</span>
                    </a>

                    <a href="perfil.php" class="perfil-link <?= $current_page == 'perfil.php' ? 'active' : '' ?>">
                        <div class="user-avatar-container">
                            <img src="<?= getAvatarUrl($user['imagen']); ?>" 
                                 class="avatar-header" id="headerAvatar" alt="Mi Avatar">
                            <span class="user-name-footer"><?php echo htmlspecialchars($user['nickname']); ?></span>
                        </div>
                    </a>
                </nav>
            </div>
        </div>
    </div>
</header>
