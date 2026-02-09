<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Usuario autenticado

$user = getCurrentUser();
$producto_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($producto_id <= 0) {
    header('Location: mis_productos.php');
    exit;
}

$conn = getDBConnection();

/* ===============================
   1. Verificar que el producto es del usuario
================================= */
$stmt_check = $conn->prepare("SELECT * FROM productos WHERE id = ? AND vendedor_id = ?");
$stmt_check->bind_param("ii", $producto_id, $user['id']);
$stmt_check->execute();
$result = $stmt_check->get_result();
$producto = $result->fetch_assoc();
$stmt_check->close();

if (!$producto) {
    header('Location: mis_productos.php');
    exit;
}

/* ===============================
   2. Obtener imagen actual del producto
================================= */
$stmt_img = $conn->prepare("SELECT imagen FROM fotos WHERE producto_id = ? ORDER BY id ASC LIMIT 1");
$stmt_img->bind_param("i", $producto_id);
$stmt_img->execute();
$result_img = $stmt_img->get_result();
$foto_actual = $result_img->fetch_assoc();
$stmt_img->close();

$error = '';
$success = '';

/* ===============================
   3. Obtener cat, subcat, integridad
================================= */
$categorias_result = $conn->query("SELECT * FROM categorias ORDER BY nombre");

$subcategorias_result = $conn->query("
    SELECT sc.*, c.nombre AS categoria_nombre 
    FROM subcategorias sc 
    INNER JOIN categorias c ON sc.categoria_id = c.id 
    ORDER BY c.nombre, sc.nombre
");

$subcategorias = [];
while ($row = $subcategorias_result->fetch_assoc()) {
    $subcategorias[] = $row;
}

$integridad_result = $conn->query("SELECT * FROM integridad ORDER BY id");

/* ===============================
   4. Procesar el formulario POST
================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = sanitize($_POST['nombre'] ?? '');
    $descripcion = sanitize($_POST['descripcion'] ?? '');
    $precio = floatval($_POST['precio'] ?? 0);
    $disponibles = intval($_POST['disponibles'] ?? 1);
    $subcategoria_id = intval($_POST['subcategoria_id'] ?? 0);
    $integridad_id = intval($_POST['integridad_id'] ?? 1);
    $estado_id = intval($_POST['estado_id'] ?? 1);

    if (empty($nombre) || empty($descripcion) || $precio <= 0 || $subcategoria_id <= 0) {
        $error = 'Por favor completa todos los campos correctamente';
    } else {

        /* ===============================
           4.1 Actualizar datos del producto
        ================================= */
        $stmt_update = $conn->prepare("
            UPDATE productos 
            SET nombre = ?, subcategoria_id = ?, integridad_id = ?, estado_id = ?, descripcion = ?, 
                precio = ?, disponibles = ? 
            WHERE id = ? AND vendedor_id = ?
        ");

        $stmt_update->bind_param(
            "siiisidii",
            $nombre,
            $subcategoria_id,
            $integridad_id,
            $estado_id,
            $descripcion,
            $precio,
            $disponibles,
            $producto_id,
            $user['id']
        );

        if ($stmt_update->execute()) {

            /* ===============================
               4.2 Manejar nueva imagen subida
            ================================= */
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {

                $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif'];
                $mime = mime_content_type($_FILES['imagen']['tmp_name']);

                if (in_array($mime, $allowed)) {

                    // obtener imagen anterior
                    $stmt_old = $conn->prepare("SELECT imagen FROM fotos WHERE producto_id = ? ORDER BY id ASC LIMIT 1");
                    $stmt_old->bind_param("i", $producto_id);
                    $stmt_old->execute();
                    $res_old = $stmt_old->get_result()->fetch_assoc();
                    $stmt_old->close();

                    if ($res_old && file_exists("uploads/productos/" . $res_old['imagen'])) {
                        unlink("uploads/productos/" . $res_old['imagen']);
                    }

                    // borrar registros antiguos
                    $stmt_delete = $conn->prepare("DELETE FROM fotos WHERE producto_id = ?");
                    $stmt_delete->bind_param("i", $producto_id);
                    $stmt_delete->execute();
                    $stmt_delete->close();

                    // procesar nueva imagen
                    $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
                    $filename = uniqid("img_", true) . "." . $ext;

                    if (move_uploaded_file($_FILES['imagen']['tmp_name'], "uploads/productos/" . $filename)) {
                        // guardar nueva imagen
                        $stmt_insert = $conn->prepare("INSERT INTO fotos (producto_id, imagen) VALUES (?, ?)");
                        $stmt_insert->bind_param("is", $producto_id, $filename);
                        $stmt_insert->execute();
                        $stmt_insert->close();

                        // ðŸ”„ RECARGAR FOTO ACTUAL DESPUÃ‰S DE ACTUALIZAR
                        $stmt_reload = $conn->prepare("SELECT imagen FROM fotos WHERE producto_id = ? ORDER BY id ASC LIMIT 1");
                        $stmt_reload->bind_param("i", $producto_id);
                        $stmt_reload->execute();
                        $foto_actual = $stmt_reload->get_result()->fetch_assoc();
                        $stmt_reload->close();
                    } else {
                        $error = 'Error al guardar la imagen en el servidor';
                    }
                }
            }

            $success = 'Producto actualizado exitosamente';

        } else {
            $error = 'Error al actualizar producto: ' . $conn->error;
        }

        $stmt_update->close();
    }
}

/* ===============================
   5. Obtener estados
================================= */
$estados_result = $conn->query("SELECT * FROM estados WHERE id IN (1, 2, 3) ORDER BY id");

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto - Tu Mercado SENA</title>
    <link rel="stylesheet" href="styles.css?v=<?= time(); ?>">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <?php include 'includes/bottom_nav.php'; ?>

    <main class="main">
        <div class="container">
            <div class="form-container">
                <h1>Editar Producto</h1>
                
                <?php if ($error): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="editar_producto.php?id=<?php echo $producto_id; ?>" enctype="multipart/form-data" class="product-form">
                    <div class="form-group">
                        <label for="nombre">Nombre del Producto *</label>
                        <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($producto['nombre']); ?>" required maxlength="64">
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion">DescripciÃ³n *</label>
                        <textarea id="descripcion" name="descripcion" rows="5" required maxlength="512"><?php echo htmlspecialchars($producto['descripcion']); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="precio">Precio (COP) *</label>
                            <input type="number" id="precio" name="precio" step="0.01" min="0" 
                                   value="<?php echo $producto['precio']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="disponibles">Cantidad Disponible *</label>
                            <input type="number" id="disponibles" name="disponibles" min="1" 
                                   value="<?php echo $producto['disponibles']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="subcategoria_id">CategorÃ­a *</label>
                        <select id="subcategoria_id" name="subcategoria_id" required>
                            <option value="">Selecciona una categorÃ­a</option>
                            <?php
                            $current_categoria = '';
                            foreach ($subcategorias as $subcat):
                                if ($current_categoria != $subcat['categoria_nombre']):
                                    if ($current_categoria != '') echo '</optgroup>';
                                    echo '<optgroup label="' . htmlspecialchars($subcat['categoria_nombre']) . '">';
                                    $current_categoria = $subcat['categoria_nombre'];
                                endif;
                            ?>
                                <option value="<?php echo $subcat['id']; ?>" 
                                        <?php echo $producto['subcategoria_id'] == $subcat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subcat['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if ($current_categoria != '') echo '</optgroup>'; ?>
                        </select>
                    </div>
                    
                   <div class="form-group">
    <label for="integridad_id">CondiciÃ³n *</label>
    <select id="integridad_id" name="integridad_id" required>
        <?php 
        $integridad_result->data_seek(0);
        while ($int = $integridad_result->fetch_assoc()): ?>
            <option value="<?php echo $int['id']; ?>" 
                    <?php echo $producto['integridad_id'] == $int['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($int['nombre']); ?> - 
                <?php echo htmlspecialchars($int['descripcion']); ?>
            </option>
        <?php endwhile; ?>
    </select>
</div>

<!-- Estado (Debajo de CondiciÃ³n) -->
<div class="form-group">
    <label for="estado_id">Estado *</label>
    <select id="estado_id" name="estado_id" required>
        <?php while ($estado = $estados_result->fetch_assoc()): ?>
            <option value="<?php echo $estado['id']; ?>" 
                    <?php echo $producto['estado_id'] == $estado['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($estado['nombre']); ?>
            </option>
        <?php endwhile; ?>
    </select>
</div>

                    
                    <div class="form-group">
                        <label for="imagen">Nueva Imagen del Producto (opcional)</label>
                        <?php if (!empty($foto_actual['imagen'])): ?>
<p>Imagen actual:</p>
<img src="uploads/productos/<?php echo htmlspecialchars($foto_actual['imagen']); ?>" style="max-width:200px;">
<?php endif; ?>
                        <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/jpg,image/png,image/gif">
                        <small>Formatos aceptados: JPG, PNG, GIF. Deja vacÃ­o para mantener la imagen actual.</small>
                    </div>
                    
                    <button type="submit" class="btn-primary">Guardar Cambios</button>
                    <a href="index.php" class="btn-secondary">Cancelar</a>
                </form>
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




