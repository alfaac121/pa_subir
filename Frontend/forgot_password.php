<?php
/**
 * Recuperación de Contraseña con Código por Email
 * RF01-003 - Tu Mercado SENA
 */

require_once 'config.php';
require_once 'includes/email_functions.php';
forceLightTheme();

$msg = '';
$error = '';
$step = 1; // 1: ingresar correo, 2: verificar código, 3: nueva contraseña

// Verificar si hay un proceso de recuperación en curso
if (isset($_SESSION['recuperar_cuenta_id']) && isset($_SESSION['recuperar_codigo'])) {
    $step = isset($_SESSION['codigo_verificado']) ? 3 : 2;
}

// Cancelar proceso
if (isset($_GET['cancelar'])) {
    unset($_SESSION['recuperar_cuenta_id']);
    unset($_SESSION['recuperar_email']);
    unset($_SESSION['recuperar_codigo']);
    unset($_SESSION['recuperar_expira']);
    unset($_SESSION['codigo_verificado']);
    header("Location: forgot_password.php");
    exit();
}

// Reenviar código
if (isset($_GET['reenviar']) && isset($_SESSION['recuperar_email'])) {
    $codigo = generateRecoveryCode();
    $_SESSION['recuperar_codigo'] = $codigo;
    $_SESSION['recuperar_expira'] = time() + (RECOVERY_CODE_EXPIRY * 60);
    
    $resultado = sendPasswordRecoveryEmail($_SESSION['recuperar_email'], $codigo);
    if ($resultado['success']) {
        $msg = "Código reenviado a tu correo.";
    } else {
        $error = "No se pudo reenviar el código: " . $resultado['message'];
    }
    $step = 2;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $conn = getDBConnection();
    
    // PASO 1: Validar correo y enviar código
    if (isset($_POST['email']) && !isset($_POST['codigo']) && !isset($_POST['password'])) {
        $email = trim($_POST['email']);
        
        if (empty($email)) {
            $error = "El correo es obligatorio";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Correo inválido";
        } else {
            // Buscar cuenta por email
            $stmt = $conn->prepare("SELECT c.id, u.nickname FROM cuentas c LEFT JOIN usuarios u ON u.cuenta_id = c.id WHERE c.email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $cuenta = $result->fetch_assoc();
                
                // Generar código de 6 dígitos
                $codigo = generateRecoveryCode();
                
                // Guardar en sesión
                $_SESSION['recuperar_cuenta_id'] = $cuenta['id'];
                $_SESSION['recuperar_email'] = $email;
                $_SESSION['recuperar_codigo'] = $codigo;
                $_SESSION['recuperar_expira'] = time() + (RECOVERY_CODE_EXPIRY * 60);
                $_SESSION['recuperar_nombre'] = $cuenta['nickname'] ?? 'Usuario';
                
                // Enviar correo
                $resultado = sendPasswordRecoveryEmail($email, $codigo, $cuenta['nickname'] ?? 'Usuario');
                
                if ($resultado['success']) {
                    $step = 2;
                    $msg = "Hemos enviado un código de 6 dígitos a tu correo.";
                } else {
                    $error = "No pudimos enviar el correo. " . $resultado['message'];
                    // Limpiar sesión si falla
                    unset($_SESSION['recuperar_codigo']);
                }
            } else {
                $error = "No existe una cuenta con ese correo.";
            }
            $stmt->close();
        }
    }
    
    // PASO 2: Verificar código
    elseif (isset($_POST['codigo']) && !isset($_POST['password'])) {
        $codigoIngresado = trim($_POST['codigo']);
        $codigoReal = $_SESSION['recuperar_codigo'] ?? '';
        $expira = $_SESSION['recuperar_expira'] ?? 0;
        
        if (empty($codigoReal)) {
            $error = "Sesión expirada. Inicia el proceso de nuevo.";
            $step = 1;
        } elseif (time() > $expira) {
            $error = "El código ha expirado. Solicita uno nuevo.";
            $step = 2;
        } elseif ($codigoIngresado !== $codigoReal) {
            $error = "Código incorrecto. Verifica e intenta de nuevo.";
            $step = 2;
        } else {
            $_SESSION['codigo_verificado'] = true;
            $step = 3;
            $msg = "¡Código verificado! Ahora crea tu nueva contraseña.";
        }
    }
    
    // PASO 3: Cambiar contraseña
    elseif (isset($_POST['password'])) {
        $password = $_POST['password'];
        $passwordConfirm = $_POST['password_confirm'];
        $cuentaId = $_SESSION['recuperar_cuenta_id'] ?? null;
        $verificado = $_SESSION['codigo_verificado'] ?? false;
        
        if (!$cuentaId || !$verificado) {
            $error = "Sesión expirada. Inicia el proceso de nuevo.";
            $step = 1;
        } elseif (empty($password) || strlen($password) < 8) {
            $error = "La contraseña debe tener al menos 8 caracteres";
            $step = 3;
        } elseif ($password !== $passwordConfirm) {
            $error = "Las contraseñas no coinciden";
            $step = 3;
        } else {
            // Actualizar contraseña
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE cuentas SET password = ?, clave = '' WHERE id = ?");
            $stmt->bind_param("si", $passwordHash, $cuentaId);
            
            if ($stmt->execute()) {
                // Limpiar sesión de recuperación
                unset($_SESSION['recuperar_cuenta_id']);
                unset($_SESSION['recuperar_email']);
                unset($_SESSION['recuperar_codigo']);
                unset($_SESSION['recuperar_expira']);
                unset($_SESSION['codigo_verificado']);
                unset($_SESSION['recuperar_nombre']);
                
                $conn->close();
                
                // Redirigir al login con mensaje
                header("Location: login.php?password_changed=1");
                exit();
            } else {
                $error = "Error al restablecer la contraseña";
                $step = 3;
            }
            $stmt->close();
        }
    }
    
    $conn->close();
}

// Tiempo restante para el código
$tiempoRestante = 0;
if (isset($_SESSION['recuperar_expira'])) {
    $tiempoRestante = max(0, $_SESSION['recuperar_expira'] - time());
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
            align-items: center;
            margin-bottom: 25px;
        }
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #6c757d;
            transition: all 0.3s ease;
        }
        .step.active {
            background: var(--color-primary, #1a5f2a);
            color: white;
            transform: scale(1.1);
        }
        .step.completed {
            background: #28a745;
            color: white;
        }
        .step-line {
            width: 50px;
            height: 3px;
            background: #e9ecef;
            margin: 0 5px;
        }
        .step-line.completed {
            background: #28a745;
        }
        .code-input {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin: 20px 0;
        }
        .code-input input {
            width: 45px;
            height: 55px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            border: 2px solid #ddd;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .code-input input:focus {
            border-color: var(--color-primary);
            outline: none;
            transform: scale(1.05);
        }
        .timer {
            text-align: center;
            color: #666;
            margin: 15px 0;
        }
        .timer.expired {
            color: #dc3545;
        }
        .resend-link {
            text-align: center;
            margin-top: 15px;
        }
        .resend-link a {
            color: var(--color-primary);
            text-decoration: none;
        }
        .resend-link a:hover {
            text-decoration: underline;
        }
        .email-display {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
        }
        .email-display strong {
            color: var(--color-primary);
        }
    </style>
</head>
<body>
    <!-- Header superior -->
    <header class="header">
        <div class="header-content" style="max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: flex-start; gap: 20px; padding: 0 20px;">
            <img src="logo_new.png" alt="SENA" style="height: 70px; width: auto;">
            <span style="font-size: 1.5rem; font-weight: 800; color: white;">Tu Mercado SENA</span>
        </div>
    </header>

    <div class="auth-container" style="margin-top: 30px;">
        <div class="auth-box" style="max-width: 450px;">
            <h1 class="auth-title">🔐 Recuperar contraseña</h1>
            
            <div class="step-indicator">
                <div class="step <?php echo $step >= 1 ? ($step > 1 ? 'completed' : 'active') : ''; ?>">1</div>
                <div class="step-line <?php echo $step > 1 ? 'completed' : ''; ?>"></div>
                <div class="step <?php echo $step >= 2 ? ($step > 2 ? 'completed' : 'active') : ''; ?>">2</div>
                <div class="step-line <?php echo $step > 2 ? 'completed' : ''; ?>"></div>
                <div class="step <?php echo $step >= 3 ? 'active' : ''; ?>">3</div>
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
                <p style="text-align: center; margin-bottom: 20px; color: #666;">
                    Ingresa tu correo electrónico y te enviaremos un código de verificación.
                </p>
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input id="email" type="email" name="email" placeholder="tu@soy.sena.edu.co" required autofocus>
                </div>
                <button type="submit" class="btn-primary">
                    📧 Enviar código
                </button>
                <p class="auth-link"><a href="login.php">← Volver al login</a></p>
            </form>
            
            <?php elseif ($step === 2): ?>
            <!-- PASO 2: Verificar código -->
            <div class="email-display">
                Código enviado a:<br>
                <strong><?php echo htmlspecialchars($_SESSION['recuperar_email'] ?? ''); ?></strong>
            </div>
            
            <form method="POST" action="forgot_password.php" id="codeForm">
                <p style="text-align: center; margin-bottom: 10px; color: #666;">
                    Ingresa el código de 6 dígitos:
                </p>
                
                <div class="code-input">
                    <input type="text" maxlength="1" class="code-digit" data-index="0" inputmode="numeric" autofocus>
                    <input type="text" maxlength="1" class="code-digit" data-index="1" inputmode="numeric">
                    <input type="text" maxlength="1" class="code-digit" data-index="2" inputmode="numeric">
                    <input type="text" maxlength="1" class="code-digit" data-index="3" inputmode="numeric">
                    <input type="text" maxlength="1" class="code-digit" data-index="4" inputmode="numeric">
                    <input type="text" maxlength="1" class="code-digit" data-index="5" inputmode="numeric">
                </div>
                <input type="hidden" name="codigo" id="codigoCompleto">
                
                <div class="timer" id="timer">
                    Código válido por: <span id="tiempoRestante"><?= floor($tiempoRestante / 60) ?>:<?= str_pad($tiempoRestante % 60, 2, '0', STR_PAD_LEFT) ?></span>
                </div>
                
                <button type="submit" class="btn-primary" id="btnVerificar">
                    ✓ Verificar código
                </button>
                
                <div class="resend-link">
                    ¿No recibiste el código? <a href="forgot_password.php?reenviar=1">Reenviar</a>
                </div>
                
                <p class="auth-link"><a href="forgot_password.php?cancelar=1">Cancelar</a></p>
            </form>
            
            <script>
                // Manejo de inputs del código
                const inputs = document.querySelectorAll('.code-digit');
                const codigoCompleto = document.getElementById('codigoCompleto');
                
                inputs.forEach((input, index) => {
                    input.addEventListener('input', (e) => {
                        const value = e.target.value;
                        if (value.length === 1 && index < 5) {
                            inputs[index + 1].focus();
                        }
                        updateCodigoCompleto();
                    });
                    
                    input.addEventListener('keydown', (e) => {
                        if (e.key === 'Backspace' && !e.target.value && index > 0) {
                            inputs[index - 1].focus();
                        }
                    });
                    
                    input.addEventListener('paste', (e) => {
                        e.preventDefault();
                        const paste = (e.clipboardData || window.clipboardData).getData('text');
                        const digits = paste.replace(/\D/g, '').slice(0, 6);
                        digits.split('').forEach((digit, i) => {
                            if (inputs[i]) inputs[i].value = digit;
                        });
                        updateCodigoCompleto();
                        if (digits.length === 6) inputs[5].focus();
                    });
                });
                
                function updateCodigoCompleto() {
                    codigoCompleto.value = Array.from(inputs).map(i => i.value).join('');
                }
                
                // Timer countdown
                let segundosRestantes = <?= $tiempoRestante ?>;
                const timerSpan = document.getElementById('tiempoRestante');
                const timerDiv = document.getElementById('timer');
                
                const countdown = setInterval(() => {
                    segundosRestantes--;
                    if (segundosRestantes <= 0) {
                        clearInterval(countdown);
                        timerDiv.classList.add('expired');
                        timerSpan.textContent = 'Expirado';
                    } else {
                        const mins = Math.floor(segundosRestantes / 60);
                        const secs = segundosRestantes % 60;
                        timerSpan.textContent = mins + ':' + String(secs).padStart(2, '0');
                    }
                }, 1000);
            </script>
            
            <?php else: ?>
            <!-- PASO 3: Nueva contraseña -->
            <form method="POST" action="forgot_password.php">
                <div class="email-display">
                    ✓ Código verificado para:<br>
                    <strong><?php echo htmlspecialchars($_SESSION['recuperar_email'] ?? ''); ?></strong>
                </div>
                
                <div class="form-group">
                    <label for="password">Nueva Contraseña</label>
                    <input id="password" type="password" name="password" minlength="8" required autofocus>
                    <small>Mínimo 8 caracteres</small>
                </div>
                <div class="form-group">
                    <label for="password_confirm">Confirmar Contraseña</label>
                    <input id="password_confirm" type="password" name="password_confirm" minlength="8" required>
                </div>
                <button type="submit" class="btn-primary">
                    🔒 Cambiar contraseña
                </button>
                <p class="auth-link"><a href="forgot_password.php?cancelar=1">Cancelar</a></p>
            </form>
            <?php endif; ?>
            
        </div>
    </div>

    <!-- Footer -->
    <footer style="background-color: var(--color-primary); color: white; text-align: center; padding: 15px; font-size: 0.9rem; font-weight: 500; margin-top: 30px;">
        © <?= date('Y') ?> Tu Mercado SENA. Todos los derechos reservados.
    </footer>
</body>
</html>
