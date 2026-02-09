<?php

require_once 'config.php';

// Redirigir a login si no está autenticado

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Usuario autenticado

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$producto_id = (int)$_GET['id'];
$conn = getDBConnection(); // 👈 Conexión abierta al inicio
$user = getCurrentUser();

// Obtener Información del producto
$stmt = $conn->prepare("SELECT p.*, u.nickname as vendedor_nombre, u.id as vendedor_id, u.descripcion as vendedor_desc,
                        sc.nombre as subcategoria_nombre, c.nombre as categoria_nombre, 
                        i.nombre as integridad_nombre, i.descripcion as integridad_desc
                        FROM productos p
                        INNER JOIN usuarios u ON p.vendedor_id = u.id
                        INNER JOIN subcategorias sc ON p.subcategoria_id = sc.id
                        INNER JOIN categorias c ON sc.categoria_id = c.id
                        INNER JOIN integridad i ON p.integridad_id = i.id
                        WHERE p.id = ? AND p.estado_id = 1");
                        

$stmt->bind_param("i", $producto_id);
$stmt->execute();
$result = $stmt->get_result();
$producto = $result->fetch_assoc();
$stmt->close();

if (!$producto) {
    // Si no hay producto, CERRAR CONEXIÓN Y SALIR
    $conn->close();
    header('Location: index.php');
    exit;
}

if (isset($_POST['agregar_favorito'])) {
    $usuario_id = $_SESSION['usuario_id'];      // usuario logueado
    $producto_id = $_POST['producto_id']; // id del producto

    $query = "INSERT INTO favoritos (votante_id, votado_id) VALUES (?, ?)";
    $stmt = $conn->prepare($query);

    if ($stmt === false) {
        die("Error en prepare: " . $conn->error);
    }

    $stmt->bind_param("ii", $usuario_id, $producto_id);

    if ($stmt->execute()) {
        header("Location: favoritos.php");
        exit;
    } else {
        echo "Error al agregar favorito: " . $stmt->error;
    }
}
// Verificar si hay chat existente
$chat_existente = null;
if ($user && $user['id'] != $producto['vendedor_id']) {
    $stmt = $conn->prepare("SELECT id FROM chats WHERE comprador_id = ? AND producto_id = ? AND estado_id = 1");
    $stmt->bind_param("ii", $user['id'], $producto_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $chat_existente = $result->fetch_assoc();
    $stmt->close();
}
// Obtener la imagen principal del producto desde la tabla fotos
$stmt = $conn->prepare("SELECT imagen FROM fotos WHERE producto_id = ? ORDER BY id ASC LIMIT 1");
$stmt->bind_param("i", $producto_id);
$stmt->execute();
$resImg = $stmt->get_result();
$foto = $resImg->fetch_assoc();
$stmt->close();

// URL final
$imagen_url = $foto ? "uploads/productos/" . $foto['imagen'] : "images/placeholder.jpg";


// 👥 LÓGICA DE FAVORITOS (se ejecuta aquí, usando la conexión abierta) 👥
$isFavorite = false;
if ($user) {
    // isProductFavorite() debe manejar internamente la conexión (volver a abrir si $conn no es global o usarla si lo es)
$isFavorite = isSellerFavorite($user['id'], $producto['vendedor_id']);}

// Cerramos la conexión al final de toda la lógica de BD
$conn->close(); 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($producto['nombre']); ?> - Tu Mercado SENA</title>
    <link rel="stylesheet" href="styles.css?v=<?= time(); ?>">
    </head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <?php include 'includes/bottom_nav.php'; ?>

    <main class="main">
        <div class="container">
            <div class="product-detail">
                <div class="product-image-section">
                    <?php
                    // Obtener todas las fotos del producto
                    $conn = getDBConnection();
                    $stmt_fotos = $conn->prepare("SELECT imagen FROM fotos WHERE producto_id = ? ORDER BY id ASC");
                    $stmt_fotos->bind_param("i", $producto_id);
                    $stmt_fotos->execute();
                    $res_fotos = $stmt_fotos->get_result();
                    $fotos = [];
                    while ($f = $res_fotos->fetch_assoc()) {
                        $fotos[] = $f['imagen'];
                    }
                    $stmt_fotos->close();
                    $conn->close();

                    // Si no hay fotos, usar placeholder de picsum
                    $principal = !empty($fotos) 
                        ? "uploads/productos/" . $fotos[0] 
                        : "https://picsum.photos/seed/{$producto_id}/600/450";
                    ?>
                    <div class="product-gallery">
                        <div class="main-image-container">
                            <img src="<?= htmlspecialchars($principal) ?>" 
                                 alt="<?= htmlspecialchars($producto['nombre']) ?>" 
                                 id="mainProductImage"
                                 class="product-detail-image"
                                 onerror="this.onerror=null; this.src='https://picsum.photos/seed/error/600/450?blur=5'">

                        </div>
                        
                        <?php if (count($fotos) > 1): ?>
                            <div class="thumbnails-grid">
                                <?php foreach ($fotos as $index => $foto_nombre): ?>
                                    <div class="thumbnail <?= $index === 0 ? 'active' : '' ?>" 
                                         onclick="changeMainImage('uploads/productos/<?= htmlspecialchars($foto_nombre) ?>', this)">
                                        <img src="uploads/productos/<?= htmlspecialchars($foto_nombre) ?>" 
                                             alt="Miniatura <?= $index + 1 ?>"
                                             onerror="this.onerror=null; this.src='https://picsum.photos/seed/error/100/100?blur=5'">

                                    </div>
                                <?php endforeach; ?>

                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="product-detail-info">
                    <h1 class="product-detail-title"><?php echo htmlspecialchars($producto['nombre']); ?></h1>
                    <p class="product-detail-price"><?php echo formatPrice($producto['precio']); ?></p>
                    
                    <div class="product-meta">
                        <p><strong>Categoría:</strong> <?php echo htmlspecialchars($producto['categoria_nombre']); ?> - 
                            <?php echo htmlspecialchars($producto['subcategoria_nombre']); ?></p>
                        <p><strong>condición:</strong> <?php echo htmlspecialchars($producto['integridad_nombre']); ?></p>
                        <p><strong>Disponibles:</strong> <?php echo $producto['disponibles']; ?></p>
                        <p><strong>Publicado:</strong> <?php echo date('d/m/Y', strtotime($producto['fecha_registro'])); ?></p>
                    </div>
                    
                    <div class="product-description">
                        <h3>Descripción</h3>
                        <p><?php echo nl2br(htmlspecialchars($producto['descripcion'])); ?></p>
                    </div>
                    
                    <div class="seller-info">
                        <h3>Vendedor</h3>
                        <p><strong><a href="vendedor.php?id=<?php echo $producto['vendedor_id']; ?>"><?php echo htmlspecialchars($producto['vendedor_nombre']); ?></a></strong></p>
                        <?php if ($producto['vendedor_desc']): ?>
                            <p><?php echo htmlspecialchars($producto['vendedor_desc']); ?></p>
                        <?php endif; ?>
                        <a href="vendedor.php?id=<?php echo $producto['vendedor_id']; ?>" class="btn-small">Ver perfil del vendedor</a>
                    </div>

                    
                    <div class="product-actions">
                        
                        <?php if ($user['id'] == $producto['vendedor_id']): ?>
                            <a href="editar_producto.php?id=<?php echo $producto['id']; ?>" class="btn-secondary">Editar Producto</a>
                            
                            <a href="eliminar_producto.php?id=<?php echo $producto['id']; ?>" 
                               class="btn-secondary"
                               onclick="return confirm('¿Estás seguro de que quieres eliminar este producto? Esta acción no se puede deshacer.');">
                               Eliminar Producto
                            </a>
                            
                        <?php else: ?>
                        <?php if ($user['id'] != $producto['vendedor_id']): ?>
                            <button type="button" 
                                id="btnFavorito"
                                data-vendedor-id="<?php echo $producto['vendedor_id']; ?>"
                                class="btn-favorite <?php echo $isFavorite ? 'active' : ''; ?>"
                                title="<?php echo $isFavorite ? 'Quitar de Favoritos' : 'Añadir a Favoritos'; ?>"
                                onclick="toggleFavorito(this)">
                                <i class="fav-icon <?php echo $isFavorite ? 'ri-heart-3-fill' : 'ri-heart-3-line'; ?>"></i>
                                <span class="fav-text"><?php echo $isFavorite ? 'En Favoritos' : 'Añadir a Favoritos'; ?></span>
                            </button>
                            
                            <!-- Botón Bloquear Usuario (RF09-001) -->
                            <button type="button" 
                                id="btnBloquear"
                                data-usuario-id="<?php echo $producto['vendedor_id']; ?>"
                                class="btn-small btn-danger"
                                title="Bloquear a este usuario"
                                onclick="toggleBloqueo(<?php echo $producto['vendedor_id']; ?>)">
                                <i class="ri-forbid-line"></i> Bloquear
                            </button>
                            
                            <!-- Botón Reportar Producto -->
                            <button type="button" 
                                id="btnReportar"
                                class="btn-small btn-warning"
                                title="Reportar este producto"
                                onclick="abrirModalReporte(<?php echo $producto['id']; ?>)">
                                <i class="ri-flag-line"></i> Reportar
                            </button>
                        <?php endif; ?>
                            <?php if ($chat_existente): ?>
                                <a href="chat.php?id=<?php echo $chat_existente['id']; ?>" class="btn-primary">Ver Conversación</a>
                            <?php else: ?>
                                <a href="contactar.php?producto_id=<?php echo $producto['id']; ?>" class="btn-primary">Contactar Vendedor</a>
                            <?php endif; ?>
                            
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Tu Mercado SENA. Todos los derechos reservados.</p>
        </div>
    </footer>
    
    <!-- Modal Reportar Producto -->
    <div id="modalReporte" class="modal-overlay" style="display:none;">
        <div class="modal-content modal-reporte">
            <div class="modal-header">
                <h3>🚩 Reportar Producto</h3>
                <button type="button" class="modal-close" onclick="cerrarModalReporte()">&times;</button>
            </div>
            <div class="modal-body">
                <p>¿Por qué quieres reportar este producto?</p>
                <input type="hidden" id="reporteProductoId" value="">
                
                <div class="reporte-opciones">
                    <label class="reporte-opcion">
                        <input type="radio" name="motivo_reporte" value="1">
                        <span class="opcion-content">
                            <i class="ri-spam-line"></i>
                            <strong>Producto prohibido</strong>
                            <small>Armas, drogas, artículos ilegales</small>
                        </span>
                    </label>
                    
                    <label class="reporte-opcion">
                        <input type="radio" name="motivo_reporte" value="2">
                        <span class="opcion-content">
                            <i class="ri-money-dollar-circle-line"></i>
                            <strong>Precio falso o engañoso</strong>
                            <small>El precio no corresponde a la realidad</small>
                        </span>
                    </label>
                    
                    <label class="reporte-opcion">
                        <input type="radio" name="motivo_reporte" value="3">
                        <span class="opcion-content">
                            <i class="ri-file-warning-line"></i>
                            <strong>Descripción engañosa</strong>
                            <small>Información falsa sobre el producto</small>
                        </span>
                    </label>
                    
                    <label class="reporte-opcion">
                        <input type="radio" name="motivo_reporte" value="4">
                        <span class="opcion-content">
                            <i class="ri-image-line"></i>
                            <strong>Imágenes inapropiadas</strong>

                            <small>Contenido ofensivo o engañoso</small>

                        </span>
                    </label>
                    
                    <label class="reporte-opcion">
                        <input type="radio" name="motivo_reporte" value="5">
                        <span class="opcion-content">
                            <i class="ri-error-warning-line"></i>
                            <strong>Posible estafa</strong>
                            <small>Sospecho que es fraudulento</small>
                        </span>
                    </label>
                </div>
                
                <div class="form-group" style="margin-top: 1rem;">
                    <label for="comentarioReporte">Comentario adicional (opcional)</label>
                    <textarea id="comentarioReporte" rows="3" maxlength="300" 
                              placeholder="Describe el problema con Más detalle..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="cerrarModalReporte()">Cancelar</button>
                <button type="button" class="btn-danger" onclick="enviarReporte()">
                    <i class="ri-send-plane-line"></i> Enviar Reporte
                </button>
            </div>
        </div>
    </div>
    
    <style>
        .btn-warning {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
            border: none;
        }
        .btn-warning:hover {
            background: linear-gradient(135deg, #e67e22, #d35400);
        }
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .modal-content {
            background: var(--color-bg);
            border-radius: 16px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 {
            margin: 0;
            color: var(--color-primary);
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--color-text-light);
        }
        .modal-body {
            padding: 1.5rem;
        }
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
        .reporte-opciones {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-top: 1rem;
        }
        .reporte-opcion {
            display: block;
            cursor: pointer;
        }
        .reporte-opcion input {
            display: none;
        }
        .reporte-opcion .opcion-content {
            display: flex;
            flex-direction: column;
            padding: 1rem;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            transition: all 0.2s ease;
        }
        .reporte-opcion .opcion-content i {
            font-size: 1.5rem;
            color: var(--color-primary);
            margin-bottom: 0.25rem;
        }
        .reporte-opcion .opcion-content strong {
            color: var(--color-text);
        }
        .reporte-opcion .opcion-content small {
            color: var(--color-text-light);
            font-size: 0.85rem;
        }
        .reporte-opcion input:checked + .opcion-content {
            border-color: #e74c3c;
            background: rgba(231, 76, 60, 0.1);
        }
        .reporte-opcion:hover .opcion-content {
            border-color: var(--color-primary);
        }
    </style>
    
    <script src="script.js?v=<?= time(); ?>"></script>

</body>
</html>


