<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Usuario autenticado

$user = getCurrentUser();
$conn = getDBConnection();

// Obtener todos los chats del usuario (como comprador o como vendedor)
$query = "SELECT 
    c.id AS chat_id,
    c.visto_comprador,
    c.visto_vendedor,
    p.id AS producto_id,
    p.nombre AS producto_nombre,
    p.precio AS producto_precio,
    f.imagen AS producto_imagen,
    u_comprador.id AS comprador_id,
    u_comprador.nickname AS comprador_nombre,
    u_comprador.imagen AS comprador_avatar,
    u_vendedor.id AS vendedor_id,
    u_vendedor.nickname AS vendedor_nombre,
    u_vendedor.imagen AS vendedor_avatar,
    (SELECT mensaje FROM mensajes WHERE chat_id = c.id ORDER BY fecha_registro DESC LIMIT 1) AS ultimo_mensaje,
    (SELECT fecha_registro FROM mensajes WHERE chat_id = c.id ORDER BY fecha_registro DESC LIMIT 1) AS ultima_fecha,
    (SELECT fecha_registro FROM mensajes WHERE chat_id = c.id ORDER BY fecha_registro ASC LIMIT 1) AS primera_fecha
FROM chats c
INNER JOIN productos p ON c.producto_id = p.id
INNER JOIN usuarios u_comprador ON c.comprador_id = u_comprador.id
INNER JOIN usuarios u_vendedor ON p.vendedor_id = u_vendedor.id
LEFT JOIN fotos f ON f.producto_id = p.id
WHERE c.comprador_id = ? OR p.vendedor_id = ?
GROUP BY c.id
ORDER BY ultima_fecha DESC, primera_fecha DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $user['id'], $user['id']);
$stmt->execute();
$chats_result = $stmt->get_result();
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Chats - Tu Mercado SENA</title>
    <link rel="stylesheet" href="styles.css?v=<?= time(); ?>">
    <style>
        /* Estilos específicos para la lista de chats */
        .chats-page-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .chats-page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .chats-page-header h1 {
            color: var(--color-primary);
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .chat-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .chat-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background-color: var(--color-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .chat-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        .chat-item.unread {
            border-left: 4px solid var(--color-primary);
            background-color: var(--color-bg-secondary);
        }

        .chat-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--color-accent);
            flex-shrink: 0;
        }

        .chat-content {
            flex: 1;
            min-width: 0;
        }

        .chat-top-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.25rem;
        }

        .chat-user-name {
            font-weight: 600;
            color: var(--color-primary);
            font-size: 1.1rem;
        }

        .chat-time {
            font-size: 0.8rem;
            color: var(--color-text-light);
            white-space: nowrap;
        }

        .chat-product-name {
            font-size: 0.9rem;
            color: var(--color-text-light);
            margin-bottom: 0.25rem;
        }

        .chat-last-message {
            font-size: 0.9rem;
            color: var(--color-text-light);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .chat-product-img {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
            flex-shrink: 0;
        }

        .no-chats {
            text-align: center;
            padding: 3rem;
            background-color: var(--color-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
        }

        .no-chats-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .no-chats h2 {
            color: var(--color-primary);
            margin-bottom: 0.5rem;
        }

        .no-chats p {
            color: var(--color-text-light);
            margin-bottom: 1.5rem;
        }

        .unread-badge {
            background-color: var(--color-primary);
            color: white;
            font-size: 0.75rem;
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
            margin-left: 0.5rem;
        }

        @media (max-width: 600px) {
            .chat-item {
                padding: 0.75rem;
            }

            .chat-avatar {
                width: 50px;
                height: 50px;
            }

            .chat-product-img {
                width: 40px;
                height: 40px;
            }

            .chats-page-header h1 {
                font-size: 1.4rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <?php include 'includes/bottom_nav.php'; ?>

    <main class="main">
        <div class="container chats-page-container">
            <div class="chats-page-header">
                <h1><i class="ri-chat-3-line"></i> Mis Conversaciones</h1>
            </div>

            <?php if ($chats_result->num_rows > 0): ?>
                <div class="chat-list">
                    <?php while ($chat = $chats_result->fetch_assoc()): 
                        // Determinar si el usuario actual es comprador o vendedor
                        $es_comprador = ($user['id'] == $chat['comprador_id']);
                        
                        // Determinar el otro usuario (con quien se chatea)
                        $otro_nombre = $es_comprador ? $chat['vendedor_nombre'] : $chat['comprador_nombre'];
                        $otro_avatar = $es_comprador ? $chat['vendedor_avatar'] : $chat['comprador_avatar'];
                        
                        // Verificar si hay mensajes sin leer
                        $sin_leer = false;
                        if ($es_comprador && !$chat['visto_comprador']) {
                            $sin_leer = true;
                        } elseif (!$es_comprador && !$chat['visto_vendedor']) {
                            $sin_leer = true;
                        }
                        
                        // Formatear tiempo del último mensaje
                        $tiempo = $chat['ultima_fecha'] ? formato_tiempo_relativo($chat['ultima_fecha']) : ($chat['primera_fecha'] ? formato_tiempo_relativo($chat['primera_fecha']) : 'Reciente');
                    ?>
                        <a href="chat.php?id=<?= $chat['chat_id'] ?>" class="chat-item <?= $sin_leer ? 'unread' : '' ?>">
                            <img src="<?= getAvatarUrl($otro_avatar) ?>" alt="<?= htmlspecialchars($otro_nombre) ?>" class="chat-avatar">
                            
                            <div class="chat-content">
                                <div class="chat-top-row">
                                    <span class="chat-user-name">
                                        <?= htmlspecialchars($otro_nombre) ?>
                                        <?php if ($sin_leer): ?>
                                            <span class="unread-badge">Nuevo</span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="chat-time"><?= $tiempo ?></span>
                                </div>
                                <div class="chat-product-name"><i class="ri-box-3-line"></i> <?= htmlspecialchars($chat['producto_nombre']) ?> — <?= formatPrice($chat['producto_precio']) ?></div>
                                <div class="chat-last-message">
                                    <?php if ($chat['ultimo_mensaje']): ?>
                                        <?= htmlspecialchars(mb_substr($chat['ultimo_mensaje'], 0, 50)) ?><?= strlen($chat['ultimo_mensaje']) > 50 ? '...' : '' ?>
                                    <?php else: ?>
                                        <em>Sin mensajes aún</em>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if (!empty($chat['producto_imagen'])): ?>
                                <img src="uploads/productos/<?= htmlspecialchars($chat['producto_imagen']) ?>" alt="Producto" class="chat-product-img" onerror="this.src='https://via.placeholder.com/50?text=Sin+Imagen'">
                            <?php else: ?>
                                <div class="chat-product-img" style="background: var(--color-primary); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                    <i class="ri-image-line" style="font-size: 1.5rem;"></i>
                                </div>
                            <?php endif; ?>
                        </a>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-chats">
                    <div class="no-chats-icon"><i class="ri-chat-3-line"></i></div>
                    <h2>No tienes conversaciones aún</h2>
                    <p>Cuando contactes a un vendedor o alguien te escriba, tus chats aparecerán aquí.</p>
                    <a href="index.php" class="btn-primary">Explorar productos</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Tu Mercado SENA. Todos los derechos reservados.</p>
        </div>
    </footer>
    <script src="script.js?v=<?= time(); ?>"></script>
</body>
</html>
