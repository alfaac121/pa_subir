<?php 
// FORZAR NO CACHÉ
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once 'config.php';

if(!isLoggedIn()) {
    header('Location:login.php');
    exit;
}

if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
    header('Location:mis_productos.php?error=id_invalido');
    exit;
}

$producto_id = (int)$_GET['id'];
$user = getCurrentUser();
$conn = getDBConnection();

// 1. Verificar que el producto existe y pertenece al usuario
$stmt = $conn->prepare("SELECT id, vendedor_id, nombre FROM productos WHERE id = ?");
if (!$stmt) {
    die("Error en prepare: " . $conn->error);
}

$stmt->bind_param("i", $producto_id);
$stmt->execute();
$result = $stmt->get_result();
$producto = $result->fetch_assoc();
$stmt->close();

if(!$producto){
    $conn->close();
    header('Location:mis_productos.php?error=producto_no_encontrado');
    exit;
}

if($producto['vendedor_id'] != $user['id']){
    $conn->close();
    header('Location:mis_productos.php?error=sin_permiso');
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

// 3. Obtener IDs de chats para eliminar mensajes
$stmt = $conn->prepare("SELECT id FROM chats WHERE producto_id = ?");
$stmt->bind_param("i", $producto_id);
$stmt->execute();
$resChats = $stmt->get_result();

$chat_ids = [];
while($c = $resChats->fetch_assoc()){
    $chat_ids[] = $c['id'];
}
$stmt->close();

// 4. Iniciar la transacción
$conn->begin_transaction();

try {
    // A. Eliminar mensajes (uno por uno si hay chats)
    if (!empty($chat_ids)) {
        foreach ($chat_ids as $chat_id) {
            $stmt = $conn->prepare("DELETE FROM mensajes WHERE chat_id = ?");
            $stmt->bind_param("i", $chat_id);
            $stmt->execute();
            $stmt->close();
        }
    }

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
    $affected = $stmt->affected_rows;
    $stmt->close();

    $conn->commit();
    $success = ($affected > 0);

} catch(Exception $e){
    $conn->rollback();
    $success = false;
    error_log("Error eliminando producto: " . $e->getMessage());
}

$conn->close();

// 5. Eliminar archivos físicos de las fotos
if ($success) {
    foreach ($fotos as $ruta) {
        if (file_exists($ruta)) {
            @unlink($ruta);
        }
    }
    
    // FORZAR LIMPIEZA DE CACHÉ Y REDIRECCIÓN
    header('Location:mis_productos.php?mensaje=producto_eliminado&t=' . time());
} else {
    header('Location:mis_productos.php?error=eliminacion_fallida&t=' . time());
}

exit;
?>
