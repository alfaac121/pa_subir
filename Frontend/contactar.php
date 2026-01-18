<?php
require_once 'config.php';
date_default_timezone_set('America/Bogota');

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser();
$producto_id = isset($_GET['producto_id']) ? (int)$_GET['producto_id'] : 0;

if ($producto_id <= 0) {
    header('Location: index.php');
    exit;
}

$conn = getDBConnection();

// Verificar que el producto existe
$stmt = $conn->prepare("
    SELECT p.*, u.id as vendedor_id 
    FROM productos p
    INNER JOIN usuarios u ON p.vendedor_id = u.id
    WHERE p.id = ? AND p.estado_id = 1
");
$stmt->bind_param("i", $producto_id);
$stmt->execute();
$producto = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$producto) {
    header('Location: index.php');
    exit;
}

// No puedes hablar contigo mismo
if ($user['id'] == $producto['vendedor_id']) {
    header("Location: producto.php?id=$producto_id");
    exit;
}

// Verificar si ya existe chat
$stmt = $conn->prepare("
    SELECT id FROM chats 
    WHERE comprador_id = ? AND producto_id = ? AND estado_id = 1
");
$stmt->bind_param("ii", $user['id'], $producto_id);
$stmt->execute();
$chat_existente = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($chat_existente) {
    header("Location: chat.php?id=" . $chat_existente['id']);
    exit;
}

// 1️⃣ Crear chat
$stmt = $conn->prepare("
    INSERT INTO chats (comprador_id, producto_id, estado_id, visto_comprador, visto_vendedor)
    VALUES (?, ?, 1, 0, 0)
");
$stmt->bind_param("ii", $user['id'], $producto_id);
$stmt->execute();
$chat_id = $conn->insert_id;
$stmt->close();

// 2️⃣ Si hay mensaje inicial, guardarlo
if (isset($_POST['mensaje_inicial']) && !empty(trim($_POST['mensaje_inicial']))) {
    $mensaje = sanitize($_POST['mensaje_inicial']);
    $es_comprador = 1;
    $es_imagen = 0;

    $stmt = $conn->prepare("
        INSERT INTO mensajes (es_comprador, chat_id, mensaje, es_imagen)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("iisi", $es_comprador, $chat_id, $mensaje, $es_imagen);
    $stmt->execute();
    $stmt->close();
}

$conn->close();

// 3️⃣ Redirigir correctamente al chat
header("Location: chat.php?id=$chat_id");
exit;
?>