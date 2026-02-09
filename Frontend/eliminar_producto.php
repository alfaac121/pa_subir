

<?php 

require_once 'config.php';

if(!isLoggedIn()) {
    header('Location:login.php');
    exit;
}

// Usuario autenticado

if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
    header('Location:index.php');
    exit;
}

$producto_id = (int)$_GET['id'];
$user = getCurrentUser();
$conn = getDBConnection();

// 1. Verificar que el producto existe y pertenece al usuario
$stmt = $conn->prepare("SELECT vendedor_id FROM productos WHERE id = ?");
if (!$stmt) die("âŒ Error en prepare: " . $conn->error);

$stmt->bind_param("i", $producto_id);
$stmt->execute();
$result = $stmt->get_result();
$producto = $result->fetch_assoc();
$stmt->close();

if(!$producto){
    header('Location:index.php?error=producto_no_encontrado');
    exit;
}

if($producto['vendedor_id'] != $user['id']){
    header('Location:index.php?error=sin_permiso');
    exit;
}

// 2. Obtener todas las fotos del producto ANTES de eliminar registros
$stmt = $conn->prepare("SELECT imagen FROM fotos WHERE producto_id = ?");
$stmt->bind_param("i", $producto_id);
$stmt->execute();
$resFotos = $stmt->get_result();

$fotos = [];
while($f = $resFotos->fetch_assoc()){
    $fotos[] = "uploads/productos/" . $f['imagen'];
}
$stmt->close();

// 3. Iniciar la transacciÃ³n
$conn->begin_transaction();

try {
    // A. Eliminar mensajes
    $stmt = $conn->prepare("
        DELETE FROM mensajes 
        WHERE chat_id IN (SELECT id FROM chats WHERE producto_id = ?)
    ");
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $stmt->close();

    // B. Eliminar chats
    $stmt = $conn->prepare("DELETE FROM chats WHERE producto_id = ?");
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $stmt->close();

    // C. Eliminar vistos
    $stmt = $conn->prepare("DELETE FROM vistos WHERE producto_id = ?");
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $stmt->close();

    // D. Eliminar fotos (REGISTROS)
    $stmt = $conn->prepare("DELETE FROM fotos WHERE producto_id = ?");
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $stmt->close();

    // E. Eliminar producto
    $stmt = $conn->prepare("DELETE FROM productos WHERE id = ?");
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    $success = true;

} catch(Exception $e){
    $conn->rollback();
    $success = false;
}

// 4. Eliminar archivos fÃ­sicos de las fotos
if ($success) {
    foreach ($fotos as $ruta) {
        if (file_exists($ruta)) unlink($ruta);
    }

    $conn->close();
    header('Location:mis_productos.php?mensaje=producto_eliminado');
} else {
    $conn->close();
    header('Location:mis_productos.php?error=eliminacion_fallida');
}

exit;
?>


