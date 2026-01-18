<?php
require_once '../config.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    exit("Falta ID");
}

$id = (int)$_GET['id'];

$conn = getDBConnection();

$stmt = $conn->prepare("SELECT avatar, avatar_tipo FROM usuarios WHERE id = ? AND avatar IS NOT NULL");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    http_response_code(404);
    exit("Sin avatar");
}

$stmt->bind_result($avatar, $avatar_tipo);
$stmt->fetch();

header("Content-Type: $avatar_tipo");
echo $avatar;

$stmt->close();
$conn->close();
