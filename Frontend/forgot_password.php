<?php
require_once 'config.php';
require_once 'api/api_client.php';
forceLightTheme();

$msg = '';
$error = '';
$step = 1; // 1: ingresar correo, 2: ingresar código, 3: nueva contraseña

// Verificar si hay un proceso de recuperación en curso
if (isset($_SESSION['recuperar_cuenta_id'])) {
    if (isset($_SESSION['recuperar_clave_verificada']) && $_SESSION['recuperar_clave_verificada']) {
        $step = 3;
    } else {
        $step = 2;
    }
}

// Cancelar proceso
if (isset($_GET['cancelar'])) {
    unset($_SESSION['recuperar_cuenta_id']);
    unset($_SESSION['recuperar_email']);
    unset($_SESSION['recuperar_clave_verificada']);
    header("Location: forgot_password.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // PASO 1: Validar correo y enviar código
    if (isset($_POST['email']) && !isset($_POST['clave']) && !isset($_POST['password'])) {
        $email = trim($_POST['email']);
        
        if (empty($email)) {
            $error = "El correo es obligatorio";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Correo inválido";
        } else {
            $response = apiValidarCorreo($email);
            
            if ($response['success'] && isset($response['data']['cuenta_id'])) {
                $_SESSION['recuperar_cuenta_id'] = $response['data']['cuenta_id'];
                $_SESSION['recuperar_email'] = $email;
                $_SESSION['recuperar_expira'] = $response['data']['expira_en'] ?? '';
                $step = 2;
                $msg = "Se ha enviado un código de verificación a tu correo";
            } else {
                if (isset($response['data']['message'])) {
                    $error = $response['data']['message'];
                } else {
                    $error = "Error al enviar el código. Verifica tu correo.";
                }
            }
        }
    }
    
    // PASO 2: Validar el código
    elseif (isset($_POST['clave']) && !isset($_POST['password'])) {
        $clave = strtoupper(trim($_POST['clave']));
        $cuentaId = $_SESSION['recuperar_cuenta_id'] ?? null;
        
        if (!$cuentaId) {
            $error = "Sesión expirada. Inicia el proceso de nuevo.";
            $step = 1;
        } elseif (empty($clave) || strlen($clave) !== 6) {
            $error = "El código debe tener 6 caracteres";
            $step = 2;
        } else {
            $response = apiValidarClaveRecuperacion($cuentaId, $clave);
            
            if ($response['success'] && isset($response['data']['clave_verificada']) && $response['data']['clave_verificada']) {
                $_SESSION['recuperar_clave_verificada'] = true;
                $step = 3;
                $msg = "Código verificado correctamente. Ingresa tu nueva contraseña.";
            } else {
                if (isset($response['data']['message'])) {
                    $error = $response['data']['message'];
                } else {
                    $error = "Código inválido o expirado";
                }
                $step = 2;
            }
        }
    }
    
    // PASO 3: Cambiar contraseña
    elseif (isset($_POST['password'])) {
        $password = $_POST['password'];
        $passwordConfirm = $_POST['password_confirm'];
        $cuentaId = $_SESSION['recuperar_cuenta_id'] ?? null;
        
        if (!$cuentaId || !isset($_SESSION['recuperar_clave_verificada'])) {
            $error = "Sesión expirada. Inicia el proceso de nuevo.";
            $step = 1;
        } elseif (empty($password) || strlen($password) < 8) {
            $error = "La contraseña debe tener al menos 8 caracteres";
            $step = 3;
        } elseif ($password !== $passwordConfirm) {
            $error = "Las contraseñas no coinciden";
            $step = 3;
        } else {
            $response = apiReestablecerPassword($cuentaId, $password, $passwordConfirm);
            
            if ($response['success']) {
                // Limpiar sesión de recuperación
                unset($_SESSION['recuperar_cuenta_id']);
                unset($_SESSION['recuperar_email']);
                unset($_SESSION['recuperar_clave_verificada']);
                unset($_SESSION['recuperar_expira']);
                
                // Redirigir al login con mensaje
                $_SESSION['login_message'] = "Contraseña restablecida correctamente. Ya puedes iniciar sesión.";
                header("Location: login.php");
                exit();
            } else {
                if (isset($response['data']['message'])) {
                    $error = $response['data']['message'];
                } else {
                    $error = "Error al restablecer la contraseña";
                }
                $step = 3;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Recuperar contraseña - Tu Mercado SENA</title>
    <link rel="stylesheet" href="styles.css">
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
        .verification-code {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
        }
        .verification-code input {
            width: 45px;
            height: 55px;
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            border: 2px solid #ddd;
            border-radius: 8px;
            text-transform: uppercase;
        }
        .verification-code input:focus {
            border-color: var(--primary-color, #007bff);
            outline: none;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1 class="auth-title">Recuperar contraseña</h1>
            
            <div class="step-indicator">
                <div class="step <?php echo $step >= 1 ? ($step > 1 ? 'completed' : 'active') : ''; ?>">1</div>
                <div class="step <?php echo $step >= 2 ? ($step > 2 ? 'completed' : 'active') : ''; ?>">2</div>
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
                <p style="text-align: center; margin-bottom: 15px; color: #666;">
                    Ingresa tu correo electrónico y te enviaremos un código de verificación.
                </p>
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input id="email" type="email" name="email" placeholder="tu@soy.sena.edu.co" required>
                </div>
                <button type="submit" class="btn-primary">Enviar código</button>
                <p class="auth-link"><a href="login.php">← Volver al login</a></p>
            </form>
            
            <?php elseif ($step === 2): ?>
            <!-- PASO 2: Ingresar código -->
            <form method="POST" action="forgot_password.php" id="codeForm">
                <p style="text-align: center; margin-bottom: 15px; color: #666;">
                    Ingresa el código de 6 caracteres enviado a:<br>
                    <strong><?php echo htmlspecialchars($_SESSION['recuperar_email'] ?? ''); ?></strong>
                </p>
                
                <div class="verification-code">
                    <input type="text" maxlength="1" class="code-input" autofocus>
                    <input type="text" maxlength="1" class="code-input">
                    <input type="text" maxlength="1" class="code-input">
                    <input type="text" maxlength="1" class="code-input">
                    <input type="text" maxlength="1" class="code-input">
                    <input type="text" maxlength="1" class="code-input">
                </div>
                
                <input type="hidden" name="clave" id="claveInput">
                
                <button type="submit" class="btn-primary">Verificar código</button>
                <p class="auth-link"><a href="forgot_password.php?cancelar=1">Cancelar</a></p>
            </form>
            
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const inputs = document.querySelectorAll('.code-input');
                    const claveInput = document.getElementById('claveInput');
                    const form = document.getElementById('codeForm');
                    
                    function updateHiddenInput() {
                        let code = '';
                        inputs.forEach(input => code += input.value.toUpperCase());
                        claveInput.value = code;
                    }
                    
                    inputs.forEach((input, index) => {
                        input.addEventListener('input', function() {
                            this.value = this.value.toUpperCase();
                            updateHiddenInput();
                            if (this.value && index < inputs.length - 1) {
                                inputs[index + 1].focus();
                            }
                        });
                        
                        input.addEventListener('keydown', function(e) {
                            if (e.key === 'Backspace' && !this.value && index > 0) {
                                inputs[index - 1].focus();
                            }
                        });
                        
                        input.addEventListener('paste', function(e) {
                            e.preventDefault();
                            const pastedData = e.clipboardData.getData('text').toUpperCase().slice(0, 6);
                            for (let i = 0; i < pastedData.length && i < inputs.length; i++) {
                                inputs[i].value = pastedData[i];
                            }
                            updateHiddenInput();
                        });
                    });
                    
                    form.addEventListener('submit', function(e) {
                        updateHiddenInput();
                        if (claveInput.value.length !== 6) {
                            e.preventDefault();
                            alert('Por favor ingresa el código completo');
                        }
                    });
                });
            </script>
            
            <?php else: ?>
            <!-- PASO 3: Nueva contraseña -->
            <form method="POST" action="forgot_password.php">
                <p style="text-align: center; margin-bottom: 15px; color: #666;">
                    Ingresa tu nueva contraseña
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
