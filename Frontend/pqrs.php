<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser();
$conn = getDBConnection();

$error = '';
$success = '';

// Motivos de PQRS (deben coincidir con los IDs de la tabla motivos en DB)
$motivos = [
    1 => ['nombre' => 'Pregunta', 'icon' => '❓', 'descripcion' => 'Tengo una duda sobre el funcionamiento del sistema'],
    2 => ['nombre' => 'Queja', 'icon' => '😠', 'descripcion' => 'Quiero expresar mi inconformidad con algo'],
    3 => ['nombre' => 'Reclamo', 'icon' => '📢', 'descripcion' => 'Tengo un problema que necesita solución'],
    4 => ['nombre' => 'Sugerencia', 'icon' => '💡', 'descripcion' => 'Tengo una idea para mejorar el sistema'],
    5 => ['nombre' => 'Agradecimiento', 'icon' => '🙏', 'descripcion' => 'Quiero agradecer al equipo']
];

// Procesar envío de PQRS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $motivo_id = intval($_POST['motivo_id'] ?? 0);
    $mensaje = sanitize($_POST['mensaje'] ?? '');
    
    // Validaciones
    if ($motivo_id < 1 || $motivo_id > 5) {
        $error = 'Selecciona un tipo de solicitud válido';
    } elseif (strlen($mensaje) < 20) {
        $error = 'El mensaje debe tener al menos 20 caracteres';
    } elseif (strlen($mensaje) > 600) {
        $error = 'El mensaje no puede exceder 600 caracteres';
    } else {
        // Insertar PQRS
        $estado_id = 1; // activo/pendiente
        $stmt = $conn->prepare("INSERT INTO pqrs (usuario_id, mensaje, motivo_id, estado_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isii", $user['id'], $mensaje, $motivo_id, $estado_id);
        
        if ($stmt->execute()) {
            $success = '¡Tu solicitud ha sido enviada correctamente! Te responderemos pronto.';
        } else {
            $error = 'Error al enviar la solicitud. Intenta de nuevo.';
        }
        $stmt->close();
    }
}

// Obtener PQRS anteriores del usuario
$stmt = $conn->prepare("
    SELECT p.id, p.usuario_id, p.mensaje, p.motivo_id, p.estado_id, p.fecha_registro, e.nombre as estado_nombre
    FROM pqrs p
    INNER JOIN estados e ON p.estado_id = e.id
    WHERE p.usuario_id = ?
    ORDER BY p.fecha_registro DESC
    LIMIT 10
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$mis_pqrs = $stmt->get_result();
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PQRS - Tu Mercado SENA</title>
    <link rel="stylesheet" href="styles.css?v=<?= time(); ?>">
    <style>
        .pqrs-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .pqrs-types {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .pqrs-type {
            padding: 1.2rem;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--color-bg);
        }
        
        .pqrs-type:hover {
            border-color: var(--color-primary);
            transform: translateY(-2px);
        }
        
        .pqrs-type.selected {
            border-color: var(--color-primary);
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            color: white;
        }
        
        .pqrs-type .icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .pqrs-type .name {
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .char-counter {
            text-align: right;
            font-size: 0.85rem;
            color: var(--color-text-light);
            margin-top: 0.5rem;
        }
        
        .char-counter.warning {
            color: #e74c3c;
        }
        
        .pqrs-history {
            margin-top: 3rem;
        }
        
        .pqrs-item {
            background: var(--color-bg);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid var(--border-color);
        }
        
        .pqrs-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        
        .pqrs-item-type {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            color: var(--color-primary);
        }
        
        .pqrs-item-status {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-activo {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-resuelto {
            background: #d4edda;
            color: #155724;
        }
        
        .pqrs-item-date {
            font-size: 0.85rem;
            color: var(--color-text-light);
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <?php include 'includes/bottom_nav.php'; ?>

    <main class="main">
        <div class="container pqrs-container">
            <div class="page-header">
                <h1>📝 PQRS</h1>
                <p>Preguntas, Quejas, Reclamos y Sugerencias</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="pqrs.php" class="form-container">
                <h2>Enviar Nueva Solicitud</h2>
                
                <div class="form-group">
                    <label>Tipo de Solicitud *</label>
                    <input type="hidden" name="motivo_id" id="motivo_id" required>
                    <div class="pqrs-types">
                        <?php foreach ($motivos as $id => $motivo): ?>
                            <div class="pqrs-type" data-id="<?= $id ?>" onclick="selectType(this, <?= $id ?>)">
                                <span class="icon"><?= $motivo['icon'] ?></span>
                                <span class="name"><?= $motivo['nombre'] ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="mensaje">Mensaje *</label>
                    <textarea id="mensaje" name="mensaje" rows="6" 
                              minlength="20" maxlength="600" required
                              placeholder="Describe tu solicitud con detalle..."
                              oninput="updateCharCounter(this)"></textarea>
                    <div class="char-counter">
                        <span id="charCount">0</span>/600 caracteres
                    </div>
                </div>
                
                <button type="submit" class="btn-primary">Enviar Solicitud</button>
            </form>
            
            <?php if ($mis_pqrs->num_rows > 0): ?>
                <div class="pqrs-history">
                    <h2>Mis Solicitudes Anteriores</h2>
                    
                    <?php while ($pqrs = $mis_pqrs->fetch_assoc()): ?>
                        <div class="pqrs-item">
                            <div class="pqrs-item-header">
                                <span class="pqrs-item-type">
                                    <?= $motivos[$pqrs['motivo_id']]['icon'] ?? '📝' ?>
                                    <?= $motivos[$pqrs['motivo_id']]['nombre'] ?? 'Solicitud' ?>
                                </span>
                                <span class="pqrs-item-status status-<?= strtolower($pqrs['estado_nombre']) ?>">
                                    <?= htmlspecialchars($pqrs['estado_nombre']) ?>
                                </span>
                            </div>
                            <p><?= nl2br(htmlspecialchars($pqrs['mensaje'])) ?></p>
                            <div class="pqrs-item-date">
                                Enviado: <?= date('d/m/Y H:i', strtotime($pqrs['fecha_registro'])) ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
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
        function selectType(element, id) {
            document.querySelectorAll('.pqrs-type').forEach(el => el.classList.remove('selected'));
            element.classList.add('selected');
            document.getElementById('motivo_id').value = id;
        }
        
        function updateCharCounter(textarea) {
            const counter = document.getElementById('charCount');
            const length = textarea.value.length;
            counter.textContent = length;
            
            if (length > 550) {
                counter.parentElement.classList.add('warning');
            } else {
                counter.parentElement.classList.remove('warning');
            }
        }
    </script>
    <script src="script.js"></script>
</body>
</html>
