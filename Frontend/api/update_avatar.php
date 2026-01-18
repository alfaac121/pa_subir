<?php
require '../config.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'No auth']);
    exit;
}

$user = getCurrentUser();
$user_id = $user['id'];

if (!isset($_FILES['avatar'])) {
    echo json_encode(['success' => false, 'error' => 'No file']);
    exit;
}

$folder = "../assests/images/defa.jpg";
$ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
$filename = "avatar_" . $user_id . "_" . time() . "." . $ext;
$savePath = $folder . $filename;

if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $savePath)) {
    echo json_encode(['success' => false, 'error' => 'Upload failed']);
    exit;
}

// Guardar en la BD (solo el path relativo)
$conn = getDBConnection();
$stmt = $conn->prepare("UPDATE usuarios SET avatar = ? WHERE id = ?");
$relativePath = "assets/images/" . $filename;
$stmt->bind_param("si", $relativePath, $user_id);
$stmt->execute();

echo json_encode(['success' => true, 'avatar' => $relativePath]);
?>