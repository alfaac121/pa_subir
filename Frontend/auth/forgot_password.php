<?php
require_once '../config.php';
forceLightTheme();

$msg = '';
$error = '';
$step = 1; // 1: ingresar correo, 2: nueva contraseña

// Verificar si hay un proceso de recuperación en curso
if (isset($_SESSION['recuperar_cuenta_id'])) {
    $step = 2;
}

// Cancelar proceso
if (isset($_GET['cancelar'])) {
    unset($_SESSION['recuperar_cuenta_id']);
    unset($_SESSION['recuperar_email']);
    header("Location: forgot_password.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $conn = getDBConnection();
    
    // PASO 1: Validar correo
    if (isset($_POST['email']) && !isset($_POST['password'])) {
        $email = trim($_POST['email']);
        
        if (empty($email)) {
            $error = "El correo es obligatorio";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Correo inválido";
        } else {
            // Buscar cuenta por email
            $stmt = $conn->prepare("SELECT id FROM cuentas WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $cuenta = $result->fetch_assoc();
                $_SESSION['recuperar_cuenta_id'] = $cuenta['id'];
                $_SESSION['recuperar_email'] = $email;
                $step = 2;
                $msg = "Correo verificado. Ahora ingresa tu nueva contraseña.";
            } else {
                $error = "No existe una cuenta con ese correo.";
            }
            $stmt->close();
        }
    }
    
    // PASO 2: Cambiar contraseña
    elseif (isset($_POST['password'])) {
        $password = $_POST['password'];
        $passwordConfirm = $_POST['password_confirm'];
        $cuentaId = $_SESSION['recuperar_cuenta_id'] ?? null;
        
        if (!$cuentaId) {
            $error = "Sesión expirada. Inicia el proceso de nuevo.";
            $step = 1;
        } elseif (empty($password) || strlen($password) < 8) {
            $error = "La contraseña debe tener al menos 8 caracteres";
            $step = 2;
        } elseif ($password !== $passwordConfirm) {
            $error = "Las contraseñas no coinciden";
            $step = 2;
        } else {
            // Actualizar contraseña
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE cuentas SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $passwordHash, $cuentaId);
            
            if ($stmt->execute()) {
                // Limpiar sesión de recuperación
                unset($_SESSION['recuperar_cuenta_id']);
                unset($_SESSION['recuperar_email']);
                
                $conn->close();
                
                // Redirigir al login con mensaje
                header("Location: login.php?password_changed=1");
                exit();
            } else {
                $error = "Error al restablecer la contraseña";
                $step = 2;
            }
            $stmt->close();
        }
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Recuperar contraseña - Tu Mercado SENA</title>
    <link rel="stylesheet" href="styles.css?v=<?= time(); ?>">
    <style>
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: bold;
            color: #666;
        }
        .step.active {
            background: var(--primary-color, #007bff);
            color: white;
        }
        .step.completed {
            background: #28a745;
            color: white;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1 class="auth-title">Recuperar contraseña</h1>
            
            <div class="step-indicator">
                <div class="step <?php echo $step >= 1 ? ($step > 1 ? 'completed' : 'active') : ''; ?>">1</div>
                <div class="step <?php echo $step >= 2 ? 'active' : ''; ?>">2</div>
            </div>

            <?php if (!empty($msg)): ?>
                <div class="success-message"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
            <!-- PASO 1: Ingresar correo -->
            <form method="POST" action="forgot_password.php">
                <p style="text-align: center; margin-bottom: 15px; color: #666;">
                    Ingresa tu correo electrónico para restablecer tu contraseña.
                </p>
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input id="email" type="email" name="email" placeholder="tu@soy.sena.edu.co" required>
                </div>
                <button type="submit" class="btn-primary">Verificar correo</button>
                <p class="auth-link"><a href="login.php">← Volver al login</a></p>
            </form>
            
            <?php else: ?>
            <!-- PASO 2: Nueva contraseña -->
            <form method="POST" action="forgot_password.php">
                <p style="text-align: center; margin-bottom: 15px; color: #666;">
                    Restableciendo contraseña para:<br>
                    <strong><?php echo htmlspecialchars($_SESSION['recuperar_email'] ?? ''); ?></strong>
                </p>
                <div class="form-group">
                    <label for="password">Nueva Contraseña</label>
                    <input id="password" type="password" name="password" minlength="8" required>
                    <small>Mínimo 8 caracteres</small>
                </div>
                <div class="form-group">
                    <label for="password_confirm">Confirmar Contraseña</label>
                    <input id="password_confirm" type="password" name="password_confirm" minlength="8" required>
                </div>
                <button type="submit" class="btn-primary">Cambiar contraseña</button>
                <p class="auth-link"><a href="forgot_password.php?cancelar=1">Cancelar</a></p>
            </form>
            <?php endif; ?>
            
        </div>
    </div>
</body>
</html>
