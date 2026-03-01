<?php
require_once '../config.php';

if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

$user = getCurrentUser();

// Verificar si es administrador (puedes ajustar esta lógica según tu sistema)
// Por ahora, cualquier usuario puede ver sus propias denuncias
$conn = getDBConnection();

// Obtener todas las denuncias (para admin) o solo las del usuario
$query = "SELECT 
    d.id,
    d.motivo,
    d.estado,
    d.fecha_denuncia,
    u_denunciante.nickname AS denunciante_nombre,
    u_denunciante.id AS denunciante_id,
    u_denunciado.nickname AS denunciado_nombre,
    u_denunciado.id AS denunciado_id,
    p.nombre AS producto_nombre,
    c.id AS chat_id
FROM denuncias d
INNER JOIN usuarios u_denunciante ON d.denunciante_id = u_denunciante.id
INNER JOIN usuarios u_denunciado ON d.denunciado_id = u_denunciado.id
INNER JOIN chats c ON d.chat_id = c.id
INNER JOIN productos p ON c.producto_id = p.id
ORDER BY d.fecha_denuncia DESC";

$result = $conn->query($query);
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Denuncias - Tu Mercado SENA</title>
    <link rel="stylesheet" href="<?= getBaseUrl() ?>styles.css?v=<?= time(); ?>">
    <style>
        .denuncias-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .denuncia-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #e74c3c;
        }
        
        .denuncia-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .denuncia-info {
            flex: 1;
        }
        
        .estado-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .estado-pendiente {
            background: #fff3cd;
            color: #856404;
        }
        
        .estado-revisando {
            background: #cce5ff;
            color: #004085;
        }
        
        .estado-resuelta {
            background: #d4edda;
            color: #155724;
        }
        
        .estado-rechazada {
            background: #f8d7da;
            color: #721c24;
        }
        
        .denuncia-motivo {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        
        .denuncia-detalles {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .detalle-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .detalle-label {
            font-size: 0.85rem;
            color: #666;
            font-weight: 600;
        }
        
        .detalle-valor {
            font-size: 0.95rem;
            color: #333;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/bottom_nav.php'; ?>

    <main class="main">
        <div class="denuncias-container">
            <h1 style="color: var(--color-primary); margin-bottom: 2rem;">
                <i class="ri-alarm-warning-line"></i> Denuncias Registradas
            </h1>

            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($denuncia = $result->fetch_assoc()): ?>
                    <div class="denuncia-card">
                        <div class="denuncia-header">
                            <div class="denuncia-info">
                                <h3 style="margin: 0 0 0.5rem 0; color: #e74c3c;">
                                    Denuncia #<?= $denuncia['id'] ?>
                                </h3>
                                <p style="margin: 0; color: #666; font-size: 0.9rem;">
                                    <i class="ri-time-line"></i> 
                                    <?= date('d/m/Y H:i', strtotime($denuncia['fecha_denuncia'])) ?>
                                </p>
                            </div>
                            <span class="estado-badge estado-<?= $denuncia['estado'] ?>">
                                <?= ucfirst($denuncia['estado']) ?>
                            </span>
                        </div>
                        
                        <div class="denuncia-detalles">
                            <div class="detalle-item">
                                <span class="detalle-label">Denunciante:</span>
                                <span class="detalle-valor"><?= htmlspecialchars($denuncia['denunciante_nombre']) ?></span>
                            </div>
                            <div class="detalle-item">
                                <span class="detalle-label">Denunciado:</span>
                                <span class="detalle-valor"><?= htmlspecialchars($denuncia['denunciado_nombre']) ?></span>
                            </div>
                            <div class="detalle-item">
                                <span class="detalle-label">Producto:</span>
                                <span class="detalle-valor"><?= htmlspecialchars($denuncia['producto_nombre']) ?></span>
                            </div>
                            <div class="detalle-item">
                                <span class="detalle-label">Chat ID:</span>
                                <span class="detalle-valor">
                                    <a href="../chat/chat.php?id=<?= $denuncia['chat_id'] ?>" 
                                       style="color: var(--color-primary); text-decoration: none;">
                                        #<?= $denuncia['chat_id'] ?>
                                    </a>
                                </span>
                            </div>
                        </div>
                        
                        <div class="denuncia-motivo">
                            <strong style="display: block; margin-bottom: 0.5rem; color: #333;">
                                <i class="ri-file-text-line"></i> Motivo:
                            </strong>
                            <p style="margin: 0; line-height: 1.6;">
                                <?= nl2br(htmlspecialchars($denuncia['motivo'])) ?>
                            </p>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem; background: white; border-radius: 12px;">
                    <i class="ri-checkbox-circle-line" style="font-size: 4rem; color: #28a745; margin-bottom: 1rem;"></i>
                    <h2 style="color: #333;">No hay denuncias registradas</h2>
                    <p style="color: #666;">Todas las transacciones se han realizado sin problemas.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Tu Mercado SENA. Todos los derechos reservados.</p>
        </div>
    </footer>
    
    <script>
        window.BASE_URL = '<?= getBaseUrl() ?>';
    </script>
    <script src="<?= getBaseUrl() ?>script.js"></script>
</body>
</html>
