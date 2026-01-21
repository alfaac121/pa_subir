<?php
require_once 'config.php';

$token = $_GET['token'] ?? null;
$msg = '';

if (!$token) {
    die("Token inválido.");
}

$conn = getDBConnection();

// Buscar token
$query = $conn->prepare("SELECT correo_id, fecha_expira FROM restablecer_contrasena WHERE token = ?");
$query->bind_param("s", $token);
$query->execute();
$res = $query->get_result();

if ($res->num_rows === 0) {
    die("Token no válido.");
}

$data = $res->fetch_assoc();

if (strtotime($data['fecha_expira']) < time()) {
    die("Este enlace ha expirado.");
}

$correo_id = $data['correo_id'];

// Obtener usuario con ese correo_id
$q2 = $conn->prepare("SELECT id FROM usuarios WHERE correo_id = ?");
$q2->bind_param("i", $correo_id);
$q2->execute();
$user = $q2->get_result()->fetch_assoc();
$user_id = $user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!empty($_POST['password'])) {
        $newPass = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Actualizar contraseña
        $up = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $up->bind_param("si", $newPass, $user_id);
        $up->execute();

        // Eliminar token
        $del = $conn->prepare("DELETE FROM restablecer_contrasena WHERE token = ?");
        $del->bind_param("s", $token);
        $del->execute();

        $msg = "Tu contraseña ha sido actualizada. Ya puedes iniciar sesión.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Crear nueva contraseña</title>
    <link rel="stylesheet" href="styles.css?v=<?= time(); ?>">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1 class="auth-title">Crear nueva contraseña</h1>

            <?php if (!empty($msg)): ?>
                <div class="success-message"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <form method="POST" action="reset_password.php?token=<?= urlencode($token) ?>" novalidate>
                <div class="form-group">
                    <label for="password">Nueva contraseña</label>
                    <input id="password" type="password" name="password" placeholder="Escribe tu nueva contraseña" required>
                </div>

                <button type="submit" class="btn-primary">Actualizar contraseña</button>

                <p class="auth-link"><a href="login.php">Volver al login</a></p>
            </form>
        </div>
    </div>
</body>
</html>

