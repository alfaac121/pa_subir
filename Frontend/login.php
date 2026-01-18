<?php

require_once 'config.php';
require_once 'api/api_client.php';
forceLightTheme();

$error = '';

// Si ya tiene sesión con token válido, redirigir
if (hasValidToken()) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!empty($email) && !empty($password)) {
        
        // Llamar a la API de login
        $response = apiLogin($email, $password);
        
        if ($response['success'] && isset($response['data']['data'])) {
            $data = $response['data']['data'];
            $user = $data['user'];
            $token = $data['token'];
            $expiresIn = $data['expires_in'] ?? 86400;
            
            // Guardar token JWT
            saveToken($token, $expiresIn);
            
            // Guardar datos del usuario en sesión
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['usuario_nombre'] = $user['nickname'];
            $_SESSION['usuario_rol'] = $user['rol_id'];
            $_SESSION['usuario_imagen'] = $user['imagen'];
            $_SESSION['cuenta_id'] = $user['cuenta_id'];
            
            header("Location: index.php");
            exit();
        } else {
            // Manejar errores de la API
            if (isset($response['data']['message'])) {
                $error = $response['data']['message'];
            } elseif (isset($response['data']['error'])) {
                $error = $response['data']['error'];
            } else {
                $error = 'Error al iniciar sesión. Verifica tus credenciales.';
            }
        }
        
    } else {
        $error = 'Por favor completa todos los campos';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Tu Mercado SENA</title>
    <link rel="stylesheet" href="styles.css">
</head>
<script>
    const savedTheme = localStorage.getItem("theme") || "light";
    document.documentElement.setAttribute("data-theme", savedTheme);
</script>

<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1 class="auth-title">
        Iniciar Sesión
            </h1>
            <?php if (isset($_GET['registered'])): ?>
                <div class="success-message">¡Registro completado! Ahora puedes iniciar sesión.</div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn-primary">Iniciar Sesión</button>
                <p class="auth-link"><a href="forgot_password.php">¿Olvidaste tu contraseña?</a></p>
            </form>
            <p class="auth-link">¿No tienes cuenta? <a href="register.php">Regístrate aquí</a></p>
            <p class="auth-link"><small>Debes tener un correo @soy.sena.edu.co para registrarte</small></p>
        </div>
    </div>
</body>
</html>