<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Usuario ya está autenticado via sesión PHP

$user = getCurrentUser();
if ($user['estado_id'] != 1) {
    header("Location: bloqueado.php");
    exit;
}

$error = '';
$success = '';

// SOLO permitir rol 2 (vendedor)
//if ($user['rol_id'] != 2) {
  //  header("Location: index.php");
    //exit;
//}
$conn = getDBConnection();

// Obtener categorías y subcategorías
$categorias_query = "SELECT * FROM categorias ORDER BY nombre";
$categorias_result = $conn->query($categorias_query);

$subcategorias_query = "SELECT sc.*, c.nombre as categoria_nombre FROM subcategorias sc 
                       INNER JOIN categorias c ON sc.categoria_id = c.id ORDER BY c.nombre, sc.nombre";
$subcategorias_result = $conn->query($subcategorias_query);
$subcategorias = [];
while ($row = $subcategorias_result->fetch_assoc()) {
    $subcategorias[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = sanitize($_POST['nombre'] ?? '');
    $descripcion = sanitize($_POST['descripcion'] ?? '');
    $precio = floatval($_POST['precio'] ?? 0);
    $disponibles = intval($_POST['disponibles'] ?? 1);
    $subcategoria_id = intval($_POST['subcategoria_id'] ?? 0);
    $integridad_id = intval($_POST['integridad_id'] ?? 1);
    
    if (empty($nombre) || empty($descripcion) || $precio <= 0 || $subcategoria_id <= 0) {
        $error = 'Por favor completa todos los campos correctamente';
    } else {
        $estado_id = 1; // activo
        $vendedor_id = $user['id'];
        $con_imagen = 0;
        
        $stmt = $conn->prepare("INSERT INTO productos (nombre, subcategoria_id, integridad_id, 
                               vendedor_id, estado_id, descripcion, precio, disponibles) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
       $stmt->bind_param("siiiisdi",
    $nombre, 
    $subcategoria_id, 
    $integridad_id, 
    $vendedor_id, 
    $estado_id, 
    $descripcion, 
    $precio, 
    $disponibles
);

        if ($stmt->execute()) {
            $producto_id = $conn->insert_id;
            
if (isset($_FILES['imagenes']) && !empty($_FILES['imagenes']['name'][0])) {
    $total_imagenes = count($_FILES['imagenes']['name']);
    $max_imagenes = 5;
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/avif', 'image/webp'];

    // Procesar hasta un máximo de 5 imágenes
    for ($i = 0; $i < min($total_imagenes, $max_imagenes); $i++) {
        if ($_FILES['imagenes']['error'][$i] === UPLOAD_ERR_OK) {
            $imagenTmp = $_FILES['imagenes']['tmp_name'][$i];
            $imagenTipo = $_FILES['imagenes']['type'][$i];

            if (in_array($imagenTipo, $allowedTypes)) {
                $extension = pathinfo($_FILES['imagenes']['name'][$i], PATHINFO_EXTENSION);
                $nombreArchivo = uniqid("img_") . "_" . $i . "." . $extension;
                $rutaDestino = "uploads/" . $nombreArchivo;

                if (move_uploaded_file($imagenTmp, $rutaDestino)) {
                    $stmtImg = $conn->prepare("INSERT INTO fotos (producto_id, imagen) VALUES (?, ?)");
                    $stmtImg->bind_param("is", $producto_id, $nombreArchivo);
                    $stmtImg->execute();
                    $stmtImg->close();
                }
            }
        }
    }
}
            
            $success = 'Producto publicado exitosamente';
            header('Location: producto.php?id=' . $producto_id);
            exit;
        } else {
            $error = 'Error al publicar producto: ' . $conn->error;
        }
        
        $stmt->close();
    }
}

// Obtener integridad para el formulario (siempre después del POST)
$integridad_query = "SELECT * FROM integridad ORDER BY id";
$integridad_result = $conn->query($integridad_query);

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publicar Producto - Tu Mercado SENA</title>
    <link rel="stylesheet" href="styles.css?v=<?= time(); ?>">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <?php include 'includes/bottom_nav.php'; ?>

    <main class="main">
        <div class="container">
            <div class="form-container">
                <h1>Publicar Nuevo Producto</h1>
                
                <?php if ($error): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="publicar.php" enctype="multipart/form-data" class="product-form">
                    <div class="form-group">
                        <label for="nombre">Nombre del Producto *</label>
                        <input type="text" id="nombre" name="nombre" required maxlength="64">
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion">Descripción *</label>
                        <textarea id="descripcion" name="descripcion" rows="5" required maxlength="512"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="precio">Precio (COP) *</label>
                            <input type="number" id="precio" name="precio" step="0.01" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="disponibles">Cantidad Disponible *</label>
                            <input type="number" id="disponibles" name="disponibles" min="1" value="1" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="subcategoria_id">Categoría *</label>
                        <select id="subcategoria_id" name="subcategoria_id" required>
                            <option value="">Selecciona una categoría</option>
                            <?php
                            $current_categoria = '';
                            foreach ($subcategorias as $subcat):
                                if ($current_categoria != $subcat['categoria_nombre']):
                                    if ($current_categoria != '') echo '</optgroup>';
                                    echo '<optgroup label="' . htmlspecialchars($subcat['categoria_nombre']) . '">';
                                    $current_categoria = $subcat['categoria_nombre'];
                                endif;
                            ?>
                                <option value="<?php echo $subcat['id']; ?>">
                                    <?php echo htmlspecialchars($subcat['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if ($current_categoria != '') echo '</optgroup>'; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="integridad_id">Condición *</label>
                        <select id="integridad_id" name="integridad_id" required>
                            <?php while ($int = $integridad_result->fetch_assoc()): ?>
                                <option value="<?php echo $int['id']; ?>">
                                    <?php echo htmlspecialchars($int['nombre']); ?> - 
                                    <?php echo htmlspecialchars($int['descripcion']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="imagenes">Imágenes del Producto (mínimo 1, máximo 5) *</label>
                        <div class="multiple-images-upload">
                            <label for="imagenes" class="upload-area" id="dropArea">
                                <div class="upload-icon">📸</div>
                                <div class="upload-text">Haz clic o arrastra las imágenes aquí</div>
                                <input type="file" id="imagenes" name="imagenes[]" accept="image/jpeg,image/jpg,image/png,image/gif,image/avif,image/webp" multiple required>
                            </label>
                            <div id="previsualizaciones" class="previsualizaciones-grid"></div>
                        </div>
                        <small>Formatos aceptados: JPG, PNG, GIF, AVIF, WEBP. Se recomienda un tamaño cuadrado.</small>
                    </div>
                    
                    <button type="submit" class="btn-primary">Publicar Producto</button>
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



