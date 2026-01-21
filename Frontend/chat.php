<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser();
$chat_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($chat_id <= 0) {
    header('Location: index.php');
    exit;
}

$conn = getDBConnection();

// Obtener información del chat
$stmt = $conn->prepare("SELECT c.*, 
       p.nombre AS producto_nombre, 
       p.precio AS producto_precio, 
       p.id AS producto_id,
       u_comprador.nickname AS comprador_nombre, 
       u_comprador.id AS comprador_id,
       u_vendedor.nickname AS vendedor_nombre, 
       u_vendedor.id AS vendedor_id
FROM chats c
INNER JOIN productos p ON c.producto_id = p.id
INNER JOIN usuarios u_comprador ON c.comprador_id = u_comprador.id
INNER JOIN usuarios u_vendedor ON p.vendedor_id = u_vendedor.id
WHERE c.id = ?");

if (!$stmt) {
    die("Error en prepare: " . $conn->error);
}
    

$stmt->bind_param("i", $chat_id);
$stmt->execute();
$result = $stmt->get_result();
$chat = $result->fetch_assoc();
$stmt->close();

if (!$chat) {
    header('Location: index.php');
    exit;
}

// Verificar que el usuario es parte del chat
$es_comprador = $user['id'] == $chat['comprador_id'];
$es_vendedor = $user['id'] == $chat['vendedor_id'];

if (!$es_comprador && !$es_vendedor) {
    header('Location: index.php');
    exit;
}
if ($es_comprador) {
    $stmt = $conn->prepare("UPDATE chats SET visto_comprador = 1 WHERE id = ?");
} else {
    $stmt = $conn->prepare("UPDATE chats SET visto_vendedor = 1 WHERE id = ?");
}
$stmt->bind_param("i", $chat_id);
$stmt->execute();
$stmt->close();

// Obtener mensajes (los nuevos se cargan vía AJAX)
$stmt = $conn->prepare("SELECT * FROM mensajes WHERE chat_id = ? ORDER BY fecha_registro ASC");
$stmt->bind_param("i", $chat_id);
$stmt->execute();
$mensajes_result = $stmt->get_result();
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - Tu Mercado SENA</title>
    <link rel="stylesheet" href="styles.css?v=<?= time(); ?>">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
<h1 class="logo">
  <a href="index.php">
      <img src="logo_new.png"  class="logo-img">
      Tu Mercado SENA   
  </a>
</h1>                <nav class="nav nav-desktop">
                     <a href="index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>">Menu Principal</a>
                    <a href="mis_productos.php">Mis Productos</a>
                    <a href="favoritos.php">Favoritos</a>
                    <a href="publicar.php">Publicar Producto</a>
                 
                    <div class="notification-badge">
                        <span class="notification-icon" id="notificationIcon" title="Chats y notificaciones">💬</span>
                        <span class="notification-count hidden" id="notificationCount">0</span>
                        <div class="chats-list" id="chatsList"></div>
                    </div>
   <a href="perfil.php" class="perfil-link">
                         <div class="user-avatar-container">
                         <img src="<?php echo getAvatarUrl($user['imagen']); ?>"
                    alt="Avatar de <?php echo htmlspecialchars($user['nickname']); ?>"
                    class="avatar-header">
                 <span class="user-name-footer"><?php echo htmlspecialchars($user['nickname']); ?></span>
             </div>
 </a>

                </nav>
            </div>
        </div>
    </header>
    
    <?php include 'includes/bottom_nav.php'; ?>

    <main class="main">
        <div class="container">
            <div class="chat-container">
                <div class="chat-header">
                    <h2>
                    <?php echo htmlspecialchars($chat['producto_nombre']); ?> — 
                     <?php echo htmlspecialchars($es_comprador ? $chat['vendedor_nombre'] : $chat['comprador_nombre']); ?>
                    </h2>
                   
                    <p>Precio: <?php echo formatPrice($chat['producto_precio']); ?></p>
                    <p>
                        <?php if ($es_comprador): ?>
                            Vendedor: <?php echo htmlspecialchars($chat['vendedor_nombre']); ?>
                        <?php else: ?>
                            Comprador: <?php echo htmlspecialchars($chat['comprador_nombre']); ?>
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="chat-messages" id="chatMessages">
                    <?php 
                    $last_message_id = 0;
                    while ($mensaje = $mensajes_result->fetch_assoc()): 
                        $last_message_id = max($last_message_id, $mensaje['id']);
                        $message_class = ($mensaje['es_comprador'] == 1 && $es_comprador) || 
                                         ($mensaje['es_comprador'] == 0 && $es_vendedor) ? 'message-sent' : 'message-received';
                    ?>
                        <div id="message-<?php echo $mensaje['id']; ?>" class="message <?php echo $message_class; ?>">
                            <p><?php echo nl2br(htmlspecialchars($mensaje['mensaje'])); ?></p>
                            <?php

?>
<span class="message-time"><?php echo formato_tiempo_relativo($mensaje['fecha_registro']); ?></span>
                            
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <div class="chat-input">
                    <form class="message-form" id="messageForm">
                        <textarea name="mensaje" id="messageInput" placeholder="Escribe un mensaje..." required rows="2"></textarea>
                        <button type="submit" class="btn-primary">Enviar</button>
                    </form>
                </div>
                <script>
                    // Guardar último ID de mensaje para AJAX
                    window.lastMessageId = <?php echo $last_message_id; ?>;
                    window.chatId = <?php echo $chat_id; ?>;
                </script>
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



